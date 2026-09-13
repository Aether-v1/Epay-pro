<?php
namespace lib;

use Exception;

/**
 * E3-A.2: Transfer / Payout Funds Safety Remediation
 * 
 * 三阶段安全模型：
 * Phase 1 - Reserve: 短事务验证+插入意图+冻结资金 → COMMIT
 * Phase 2 - External: 事务外调用Gateway（不持有数据库锁）
 * Phase 3 - Finalize: 短事务根据结果结算（SUCCESS/REJECTED/UNKNOWN）
 * 
 * 资金安全不变量：
 * - available = money - frozen_money
 * - 转账创建时立即冻结资金
 * - SUCCESS: 原子结算（money和frozen_money同时减少）
 * - REJECTED: 解冻资金
 * - UNKNOWN: 保持冻结，禁止自动重试
 * - 同一 biz_no 的资金事件 exactly-once
 */
class Transfer
{
    // E3-A.2: 细粒度转账状态（resolution 字段）
    const RES_CREATED = 'CREATED';           // 转账意图已创建，资金已冻结，尚未调用上游
    const RES_PROCESSING = 'PROCESSING';     // 正在调用上游
    const RES_SUCCESS = 'SUCCESS';           // 上游明确确认成功，本地已结算
    const RES_REJECTED = 'REJECTED';         // 上游明确拒绝，资金已解冻
    const RES_UNKNOWN = 'UNKNOWN';           // 结果不确定，资金保持冻结，需人工对账
    const RES_CANCELLED = 'CANCELLED';       // 已取消（仅CREATED状态可取消）

    // 兼容旧 status 字段（0=处理中,1=成功,2=失败,3=待审核,4=红包待领取）
    const STATUS_PROCESSING = 0;
    const STATUS_SUCCESS = 1;
    const STATUS_FAILED = 2;
    const STATUS_PENDING_REVIEW = 3;
    const STATUS_RED_PACKET = 4;

    static public $payee_err_code = [ //收款方原因导致的失败编码
		'PAYEE_NOT_EXIST','PAYEE_ACCOUNT_STATUS_ERROR','CARD_BIN_ERROR','PAYEE_CARD_INFO_ERROR','PERM_AML_NOT_REALNAME_REV','PAYEE_USER_INFO_ERROR','PAYEE_ACC_OCUPIED','PERMIT_NON_BANK_LIMIT_PAYEE','PAYEE_TRUSTEESHIP_ACC_OVER_LIMIT','PAYEE_ACCOUNT_NOT_EXSIT','PAYEE_USERINFO_STATUS_ERROR','TRUSTEESHIP_RECIEVE_QUOTA_LIMIT','EXCEED_LIMIT_UNRN_DM_AMOUNT','INVALID_CARDNO','RELEASE_USER_FORBBID_RECIEVE','PAYEE_USER_TYPE_ERROR','PAYEE_NOT_RELNAME_CERTIFY','PERMIT_LIMIT_PAYEE',

		'OPENID_ERROR','NAME_MISMATCH','V2_ACCOUNT_SIMPLE_BAN','MONEY_LIMIT','EXCEED_PAYEE_ACCOUNT_LIMIT','PAYEE_ACCOUNT_ABNORMAL','APPID_OR_OPENID_ERR',

		'REALNAME_CHECK_ERROR','RE_USER_NAME_CHECK_ERROR','ERR_TJ_BLACK','USER_FROZEN','TRANSFER_FAIL','TRANSFER_FEE_LIMIT_ERROR',

		'ACCOUNT_FROZEN','REAL_NAME_CHECK_FAIL','NAME_NOT_CORRECT','OPENID_INVALID','TRANSFER_QUOTA_EXCEED','DAY_RECEIVED_QUOTA_EXCEED','MONTH_RECEIVED_QUOTA_EXCEED','DAY_RECEIVED_COUNT_EXCEED','ID_CARD_NOT_CORRECT','ACCOUNT_NOT_EXIST','TRANSFER_RISK','REALNAME_ACCOUNT_RECEIVED_QUOTA_EXCEED','RECEIVE_ACCOUNT_NOT_PERMMIT','PAYEE_ACCOUNT_ABNORMAL','BLOCK_B2C_USERLIMITAMOUNT_BSRULE_MONTH','BLOCK_B2C_USERLIMITAMOUNT_MONTH',
	];

    /**
     * E3-A.2: 生成请求指纹，用于幂等冲突检测
     */
    private static function makeRequestHash($uid, $type, $channelid, $payee_account, $payee_real_name, $money){
        return md5(implode('|', [$uid, $type, $channelid, $payee_account, $payee_real_name, round((float)$money, 2)]));
    }

    /**
     * E3-A.2: resolution → status 兼容映射
     */
    private static function resolutionToStatus($resolution){
        switch($resolution){
            case self::RES_SUCCESS: return self::STATUS_SUCCESS;
            case self::RES_REJECTED:
            case self::RES_CANCELLED: return self::STATUS_FAILED;
            default: return self::STATUS_PROCESSING;
        }
    }

    //通用转账
    //type alipay:支付宝,wxpay:微信,qqpay:QQ钱包,bank:银行卡
    public static function submit($type, $channel, $out_biz_no, $payee_account, $payee_real_name, $money, $title = null, $desc = null){
        global $conf;

        $bizParam = [
            'type' => $type,
            'out_biz_no' => $out_biz_no,
            'payee_account' => $payee_account,
            'payee_real_name' => $payee_real_name,
            'money' => $money,
            'transfer_name' => $title?$title:$conf['transfer_name'],
            'transfer_desc' => $desc?$desc:$conf['transfer_desc'],
        ];
        return \lib\Plugin::call('transfer', $channel, $bizParam);
    }

    /**
     * E3-A.2: 创建转账（三阶段安全模型）
     * 
     * Phase 1 - Reserve: 短事务验证+插入意图+冻结资金
     * Phase 2 - External: 事务外调用Gateway
     * Phase 3 - Finalize: 短事务结算
     */
    public static function add($uid, $type, $out_biz_no, $payee_account, $payee_real_name, $money, $title = null, $desc = null, $bookid = null, $channelid = null){
        global $conf, $DB, $userrow, $siteurl;
        $biz_no = $out_biz_no;
        if(strlen($biz_no)!=19 || !is_numeric($biz_no)) $biz_no = date("YmdHis").rand(11111,99999);

        // 金额校验（E3-A.2: 严格金额验证）
        if(!is_numeric($money)){
            return ['code'=>-1, 'msg'=>'转账金额必须为数字'];
        }
        $money = round((float)$money, 2);
        if($money <= 0){
            return ['code'=>-1, 'msg'=>'转账金额必须大于0'];
        }

        if($uid > 0){
            if($conf['transfer_minmoney']>0 && $money<$conf['transfer_minmoney']) return ['code'=>-1, 'msg'=>'单笔最小代付金额限制为'.$conf['transfer_minmoney'].'元'];
            if($conf['transfer_maxmoney']>0 && $money>$conf['transfer_maxmoney']) return ['code'=>-1, 'msg'=>'单笔最大代付金额限制为'.$conf['transfer_maxmoney'].'元'];
            if($conf['transfer_maxlimit']>0){
                $a_count = $DB->getColumn('SELECT count(*) FROM pre_transfer WHERE uid=:uid AND type=:type AND account=:account AND paytime>=:paytime', [':uid'=>$uid, ':type'=>$type, ':account'=>$payee_account, ':paytime'=>date('Y-m-d').' 00:00:00']);
                if($a_count >= $conf['transfer_maxlimit']){
                    return ['code'=>-1, 'msg'=>'您今天向该账号的转账次数已达到上限'];
                }
            }
        }
        
        if(!$channelid){
            if($type=='alipay'){
                $channelid = $conf['transfer_alipay'];
            }elseif($type=='wxpay'){
                $channelid = $conf['transfer_wxpay'];
            }elseif($type=='qqpay'){
                if (!is_numeric($payee_account) || strlen($payee_account)<6 || strlen($payee_account)>10) return ['code'=>-1, 'msg'=>'QQ号码格式错误'];
                $channelid = $conf['transfer_qqpay'];
            }elseif($type=='bank'){
                $channelid = $conf['transfer_bank'];
            }else{
                return ['code'=>-1, 'msg'=>'type参数错误'];
            }
            if(!$channelid) return ['code'=>-1, 'msg'=>'未开启此转账方式'];
        }
        if($channelid > 0){
            $channel = \lib\Channel::get($channelid, $userrow['channelinfo']);
            if(!$channel) return ['code'=>-1, 'msg'=>'当前支付通道信息不存在'];
        }

        // AlipaySATF 特殊路径（保持原有逻辑）
        if($uid > 0){
            if(class_exists('\\lib\\AlipaySATF\\AlipaySATF') && $conf['alipay_satf']==1 && ($type=='alipay' || $type=='bank' && $conf['transfer_alipay']==$conf['transfer_bank'])){
                if(!$bookid) $bookid = $DB->findColumn('satf_account_book', 'id', ['uid'=>$uid, 'status'=>1], 'money DESC');
                $satf = new \lib\AlipaySATF\AlipaySATF();
                $params = [
                    'out_biz_no' => $out_biz_no,
                    'account' => $payee_account,
                    'name' => $payee_real_name,
                    'money' => $money,
                    'remark' => $desc,
                ];
                $result = $satf->transfer($bookid, $type=='bank' ? 2 : 1, $params, $uid);
                return $result;
            }
        }

        // 计算手续费（E3-A.2: 使用精确计算）
        $need_money = null;
        if($uid > 0){
            if(!$conf['transfer_rate'])$conf['transfer_rate'] = $conf['settle_rate'];
            $need_money = round($money + $money*$conf['transfer_rate']/100, 2);
        }

        // 生成请求指纹
        $request_hash = self::makeRequestHash($uid, $type, $channelid, $payee_account, $payee_real_name, $money);

        // ============================================================
        // Phase 1 - Reserve: 短事务验证+插入意图+冻结资金
        // ============================================================
        $DB->beginTransaction();
        try {
            // 检查幂等：相同 uid + out_biz_no 是否已存在
            $existing = $DB->getRow("SELECT * FROM pre_transfer WHERE uid=:uid AND out_biz_no=:out_biz_no FOR UPDATE", [
                ':uid' => $uid,
                ':out_biz_no' => $out_biz_no
            ]);
            
            if($existing){
                $DB->rollBack();
                // 幂等冲突检查
                if($existing['request_hash'] !== $request_hash){
                    return ['code'=>-1, 'msg'=>'IDEMPOTENCY_CONFLICT: 相同业务号对应不同请求参数'];
                }
                // 相同请求，返回已有状态
                return [
                    'code' => 0,
                    'status' => $existing['status'],
                    'resolution' => $existing['resolution'],
                    'biz_no' => $existing['biz_no'],
                    'out_biz_no' => $existing['out_biz_no'],
                    'orderid' => $existing['pay_order_no'],
                    'msg' => '重复请求，返回已有转账状态',
                    'duplicate' => true
                ];
            }

            // 验证商户余额并冻结资金
            if($uid > 0){
                $userrow = $DB->getRow('SELECT * FROM pre_user WHERE uid=:uid FOR UPDATE', [':uid'=>$uid]);
                if(!$userrow || $userrow['settle']==0){
                    $DB->rollback();
                    return ['code'=>-1, 'msg'=>'您的商户出现异常，无法使用代付功能'];
                }
                
                // 计算可用余额
                if($conf['settle_type']==1){
                    $today=date("Y-m-d").' 00:00:00';
                    $order_today=$DB->getColumn("SELECT SUM(realmoney) from pre_order where uid={$uid} and tid<>2 and status=1 and endtime>='$today'");
                    if(!$order_today) $order_today = 0;
                    $enable_money=round($userrow['money']-$order_today,2);
                    if($enable_money<0)$enable_money=0;
                }else{
                    $enable_money=$userrow['money'];
                }
                
                // 扣除已冻结金额（E3-A.2: 可用余额 = money - frozen_money）
                $frozen = isset($userrow['frozen_money']) ? (float)$userrow['frozen_money'] : 0;
                $available = round($enable_money - $frozen, 2);
                if($available < 0) $available = 0;
                
                if($need_money > $available){
                    $DB->rollback();
                    return ['code'=>-1, 'msg'=>'需支付金额大于可转账余额'];
                }

                // 冻结资金（E3-A.2: 增加 frozen_money）
                $newFrozen = round($frozen + $need_money, 2);
                $DB->exec("UPDATE pre_user SET frozen_money=:frozen WHERE uid=:uid", [
                    ':frozen' => $newFrozen,
                    ':uid' => $uid
                ]);
                
                // 记录冻结流水
                $DB->insert('record', ['uid'=>$uid, 'action'=>3, 'money'=>$need_money, 'oldmoney'=>$userrow['money'], 'newmoney'=>$userrow['money'], 'type'=>'转账冻结', 'trade_no'=>$biz_no, 'date'=>'NOW()']);
            }

            // 插入转账意图（E3-A.2: resolution=CREATED, status=0）
            $data = [
                'biz_no' => $biz_no,
                'out_biz_no' => $out_biz_no,
                'uid' => $uid,
                'type' => $type,
                'channel' => $channelid,
                'account' => $payee_account,
                'username' => $payee_real_name,
                'money' => $money,
                'costmoney' => $need_money ?? $money,
                'addtime' => 'NOW()',
                'status' => self::STATUS_PROCESSING,
                'resolution' => self::RES_CREATED,
                'request_hash' => $request_hash,
                'gateway_call_count' => 0,
                'desc' => $title ? $title : $desc
            ];
            $id = $DB->insert('transfer', $data);
            if($id === false){
                $DB->rollBack();
                return ['code'=>-1, 'msg'=>'创建转账记录失败'];
            }

            $DB->commit();
        } catch (Exception $e) {
            $DB->rollBack();
            safe_error_log('Transfer::add Phase1 failed: ' . $e->getMessage(), 'transfer');
            return ['code'=>-1, 'msg'=>'创建转账失败，请稍后重试'];
        }

        // ============================================================
        // Phase 2 - External: 事务外调用Gateway（不持有数据库锁）
        // ============================================================
        if($channelid == -1){
            // 待审核模式，不调用上游
            $result = ['code'=>0, 'status'=>self::STATUS_PENDING_REVIEW, 'orderid'=>null, 'biz_no'=>$biz_no, 'out_biz_no'=>$out_biz_no];
            $gatewayResult = 'pending_review';
        }else{
            // 更新为 PROCESSING 状态
            $DB->exec("UPDATE pre_transfer SET resolution=:res, gateway_call_count = gateway_call_count + 1 WHERE biz_no=:biz_no", [
                ':res' => self::RES_PROCESSING,
                ':biz_no' => $biz_no
            ]);
            
            try {
                $result = self::submit($type, $channel, $biz_no, $payee_account, $payee_real_name, $money, $title, $desc);
                $result['biz_no'] = $biz_no;
                $result['out_biz_no'] = $out_biz_no;
                $gatewayResult = self::classifyGatewayResult($result);
            } catch (Exception $e) {
                // 上游调用异常 → UNKNOWN（E3-A.2: 超时/异常不视为失败）
                safe_error_log('Transfer::add gateway exception: ' . $e->getMessage(), 'transfer');
                $result = ['code'=>-2, 'msg'=>'上游调用异常，结果未知', 'biz_no'=>$biz_no, 'out_biz_no'=>$out_biz_no];
                $gatewayResult = 'unknown';
            }
        }

        // ============================================================
        // Phase 3 - Finalize: 短事务结算
        // ============================================================
        return self::finalizeTransfer($biz_no, $gatewayResult, $result);
    }

    /**
     * E3-A.2: 分类上游返回结果
     * 
     * @return string success / rejected / unknown / pending_review / wxpackage
     */
    private static function classifyGatewayResult($result){
        if(!is_array($result)) return 'unknown';
        
        // 微信红包特殊处理
        if(isset($result['wxpackage'])) return 'wxpackage';
        
        // 待审核
        if(isset($result['status']) && $result['status'] == self::STATUS_PENDING_REVIEW) return 'pending_review';
        
        // 明确成功
        if(isset($result['code']) && $result['code'] === 0 && isset($result['status']) && $result['status'] == self::STATUS_SUCCESS){
            return 'success';
        }
        
        // 明确失败（code != 0 且有明确错误信息）
        if(isset($result['code']) && $result['code'] != 0 && isset($result['msg']) && !empty($result['msg'])){
            // 检查是否是"明确拒绝"还是"未知错误"
            // E3-A.2: 只有明确的业务拒绝才视为 REJECTED
            // 网络错误、超时、格式异常 → UNKNOWN
            $msg = isset($result['msg']) ? $result['msg'] : '';
            $unknownPatterns = ['超时', 'timeout', '连接', 'connection', '502', '504', '500', '解析失败', '格式异常', 'UNKNOWN', '未知'];
            foreach($unknownPatterns as $pattern){
                if(stripos($msg, $pattern) !== false) return 'unknown';
            }
            if(isset($result['code']) && $result['code'] == -2) return 'unknown'; // 上游响应格式异常
            return 'rejected';
        }
        
        // 处理中（status=0 但 code=0）
        if(isset($result['code']) && $result['code'] === 0 && isset($result['status']) && $result['status'] == self::STATUS_PROCESSING){
            return 'processing';
        }
        
        // 其他情况 → UNKNOWN（保守策略）
        return 'unknown';
    }

    /**
     * E3-A.2: 结算转账（Phase 3）
     * 
     * 使用事务 + CAS 保证 exactly-once 资金事件
     */
    private static function finalizeTransfer($biz_no, $gatewayResult, $result){
        global $DB;
        
        $DB->beginTransaction();
        try {
            $order = $DB->getRow("SELECT * FROM pre_transfer WHERE biz_no=:biz_no FOR UPDATE", [':biz_no'=>$biz_no]);
            if(!$order){
                $DB->rollBack();
                return ['code'=>-1, 'msg'=>'转账记录不存在'];
            }
            
            // 已经是终态，直接返回（幂等）
            if(in_array($order['resolution'], [self::RES_SUCCESS, self::RES_REJECTED, self::RES_CANCELLED])){
                $DB->rollBack();
                
                // E3-A.3: Reconciliation Conflict 检测
                // 当本地终态与新的权威回调状态不一致时，记录安全审计日志
                $incomingResolution = null;
                if($gatewayResult == 'success') $incomingResolution = self::RES_SUCCESS;
                elseif($gatewayResult == 'rejected') $incomingResolution = self::RES_REJECTED;
                
                if($incomingResolution !== null && $incomingResolution !== $order['resolution']){
                    // 状态冲突：本地终态 != 回调状态
                    // 不自动修改状态，不自动修改资金，只记录审计日志
                    $conflictMsg = sprintf(
                        'TRANSFER_RECONCILIATION_CONFLICT biz_no=%s uid=%s local_resolution=%s incoming_resolution=%s channel=%s gateway_trade_no=%s',
                        $biz_no,
                        $order['uid'],
                        $order['resolution'],
                        $incomingResolution,
                        $order['channel'] ?? 'unknown',
                        $order['pay_order_no'] ?? 'unknown'
                    );
                    safe_error_log($conflictMsg, 'transfer_reconciliation');
                    
                    // 同时写入操作审计日志（如果存在）
                    if(function_exists('admin_audit_log')){
                        @admin_audit_log(0, 'transfer_reconciliation_conflict', $biz_no, $conflictMsg);
                    }
                }
                
                return [
                    'code' => 0,
                    'status' => $order['status'],
                    'resolution' => $order['resolution'],
                    'biz_no' => $order['biz_no'],
                    'out_biz_no' => $order['out_biz_no'],
                    'orderid' => $order['pay_order_no'],
                    'msg' => '转账已结算，返回当前状态'
                ];
            }
            
            $uid = $order['uid'];
            $costmoney = (float)$order['costmoney'];
            
            switch($gatewayResult){
                case 'success':
                    // E3-A.2: SUCCESS - 原子结算（同时减少 money 和 frozen_money）
                    $casResult = $DB->exec("UPDATE pre_transfer SET resolution=:res, status=:status, paytime=NOW(), pay_order_no=:orderid, result='' WHERE biz_no=:biz_no AND resolution IN (:created, :processing, :unknown)", [
                        ':res' => self::RES_SUCCESS,
                        ':status' => self::STATUS_SUCCESS,
                        ':orderid' => isset($result['orderid']) ? $result['orderid'] : null,
                        ':biz_no' => $biz_no,
                        ':created' => self::RES_CREATED,
                        ':processing' => self::RES_PROCESSING,
                        ':unknown' => self::RES_UNKNOWN
                    ]);
                    
                    if($casResult > 0 && $uid > 0 && $costmoney > 0){
                        // 原子结算：同时减少 money 和 frozen_money
                        $userRow = $DB->getRow("SELECT money, frozen_money FROM pre_user WHERE uid=:uid FOR UPDATE", [':uid'=>$uid]);
                        $oldmoney = (float)$userRow['money'];
                        $frozen = (float)$userRow['frozen_money'];
                        $newmoney = round($oldmoney - $costmoney, 2);
                        $newFrozen = round($frozen - $costmoney, 2);
                        if($newFrozen < 0) $newFrozen = 0;
                        
                        $DB->exec("UPDATE pre_user SET money=:money, frozen_money=:frozen WHERE uid=:uid", [
                            ':money' => $newmoney,
                            ':frozen' => $newFrozen,
                            ':uid' => $uid
                        ]);
                        
                        $DB->insert('record', ['uid'=>$uid, 'action'=>2, 'money'=>$costmoney, 'oldmoney'=>$oldmoney, 'newmoney'=>$newmoney, 'type'=>'代付', 'trade_no'=>$biz_no, 'date'=>'NOW()']);
                    }
                    
                    $result['resolution'] = self::RES_SUCCESS;
                    $result['status'] = self::STATUS_SUCCESS;
                    $result['msg'] = '转账成功！转账单据号:'.($result['orderid'] ?? '').' 支付时间:'.($result['paydate'] ?? date('Y-m-d H:i:s'));
                    break;
                    
                case 'rejected':
                    // E3-A.2: REJECTED - 解冻资金
                    $errmsg = isset($result['msg']) ? $result['msg'] : (isset($result['errcode']) ? $result['errcode'] : '转账失败');
                    $casResult = $DB->exec("UPDATE pre_transfer SET resolution=:res, status=:status, result=:errmsg WHERE biz_no=:biz_no AND resolution IN (:created, :processing, :unknown)", [
                        ':res' => self::RES_REJECTED,
                        ':status' => self::STATUS_FAILED,
                        ':errmsg' => $errmsg,
                        ':biz_no' => $biz_no,
                        ':created' => self::RES_CREATED,
                        ':processing' => self::RES_PROCESSING,
                        ':unknown' => self::RES_UNKNOWN
                    ]);
                    
                    if($casResult > 0 && $uid > 0 && $costmoney > 0){
                        // 解冻资金
                        $userRow = $DB->getRow("SELECT money, frozen_money FROM pre_user WHERE uid=:uid FOR UPDATE", [':uid'=>$uid]);
                        $frozen = (float)$userRow['frozen_money'];
                        $newFrozen = round($frozen - $costmoney, 2);
                        if($newFrozen < 0) $newFrozen = 0;
                        
                        $DB->exec("UPDATE pre_user SET frozen_money=:frozen WHERE uid=:uid", [
                            ':frozen' => $newFrozen,
                            ':uid' => $uid
                        ]);
                        
                        $DB->insert('record', ['uid'=>$uid, 'action'=>4, 'money'=>$costmoney, 'oldmoney'=>$userRow['money'], 'newmoney'=>$userRow['money'], 'type'=>'转账解冻', 'trade_no'=>$biz_no, 'date'=>'NOW()']);
                    }
                    
                    $result['resolution'] = self::RES_REJECTED;
                    $result['status'] = self::STATUS_FAILED;
                    $result['msg'] = '转账失败：' . $errmsg;
                    break;
                    
                case 'unknown':
                case 'processing':
                    // E3-A.2: UNKNOWN - 保持资金冻结，禁止自动重试
                    $casResult = $DB->exec("UPDATE pre_transfer SET resolution=:res, status=:status, result=:errmsg WHERE biz_no=:biz_no AND resolution IN (:created, :processing)", [
                        ':res' => self::RES_UNKNOWN,
                        ':status' => self::STATUS_PROCESSING,
                        ':errmsg' => isset($result['msg']) ? $result['msg'] : '结果未知，需人工对账',
                        ':biz_no' => $biz_no,
                        ':created' => self::RES_CREATED,
                        ':processing' => self::RES_PROCESSING
                    ]);
                    
                    $result['resolution'] = self::RES_UNKNOWN;
                    $result['status'] = self::STATUS_PROCESSING;
                    $result['msg'] = '转账结果未知，资金已冻结，请通过查询接口或人工对账确认结果。禁止重复提交！';
                    $result['unknown'] = true;
                    break;
                    
                case 'pending_review':
                    // 待审核，保持 CREATED 状态
                    $result['resolution'] = self::RES_CREATED;
                    $result['status'] = self::STATUS_PENDING_REVIEW;
                    $result['msg'] = '提交成功！请等待管理员审核转账。';
                    break;
                    
                case 'wxpackage':
                    // 微信红包，保持 PROCESSING
                    $DB->exec("UPDATE pre_transfer SET ext=:ext WHERE biz_no=:biz_no", [
                        ':ext' => isset($result['wxpackage']) ? $result['wxpackage'] : null,
                        ':biz_no' => $biz_no
                    ]);
                    $id = $DB->getColumn("SELECT id FROM pre_transfer WHERE biz_no=:biz_no", [':biz_no'=>$biz_no]);
                    $jumpurl = $GLOBALS['siteurl'].'paypage/wxtrans.php?type=transfer&id='.$id;
                    $result['resolution'] = self::RES_PROCESSING;
                    $result['status'] = self::STATUS_PROCESSING;
                    $result['msg'] = '提交成功！请在微信打开 '.$jumpurl.' 确认收款。转账单据号:'.($result['orderid'] ?? '');
                    $result['jumpurl'] = $jumpurl;
                    break;
            }
            
            $DB->commit();
            return $result;
        } catch (Exception $e) {
            $DB->rollBack();
            safe_error_log('Transfer::finalizeTransfer failed: ' . $e->getMessage(), 'transfer');
            return ['code'=>-1, 'msg'=>'结算转账失败，请稍后查询结果', 'biz_no'=>$biz_no, 'resolution'=>self::RES_UNKNOWN];
        }
    }

    /**
     * E3-A.2: 查询并结算 UNKNOWN 状态的转账（用于恢复）
     */
    public static function queryAndFinalize($biz_no){
        global $DB;
        
        $order = $DB->find('transfer', '*', ['biz_no' => $biz_no]);
        if(!$order) return ['code'=>-1, 'msg'=>'转账记录不存在'];
        
        // 只有 UNKNOWN/PROCESSING/CREATED 状态需要查询
        if(!in_array($order['resolution'], [self::RES_UNKNOWN, self::RES_PROCESSING, self::RES_CREATED])){
            return ['code'=>0, 'resolution'=>$order['resolution'], 'msg'=>'转账已是终态，无需查询'];
        }
        
        $channelinfo = null;
        if($order['uid'] > 0){
            $channelinfo = $DB->findColumn('user', 'channelinfo', ['uid'=>$order['uid']]);
        }
        $channel = \lib\Channel::get($order['channel'], $channelinfo);
        if(!$channel) return ['code'=>-1, 'msg'=>'支付通道不存在'];
        
        $result = self::query($order['type'], $channel, $biz_no, $order['pay_order_no']);
        
        if($result['code'] == 0){
            if($result['status'] == self::STATUS_SUCCESS){
                return self::finalizeTransfer($biz_no, 'success', ['code'=>0, 'status'=>1, 'orderid'=>$order['pay_order_no']]);
            }elseif($result['status'] == self::STATUS_FAILED){
                return self::finalizeTransfer($biz_no, 'rejected', ['code'=>-1, 'status'=>2, 'msg'=>isset($result['errmsg']) ? $result['errmsg'] : '上游查询确认失败']);
            }
        }
        
        // 查询失败或仍在处理中 → 保持 UNKNOWN
        return ['code'=>0, 'resolution'=>self::RES_UNKNOWN, 'msg'=>'查询结果不确定，保持UNKNOWN状态，请稍后重试或人工对账'];
    }

    //转账状态刷新
    public static function status($biz_no){
        global $DB;
        $order = $DB->find('transfer', '*', ['biz_no' => $biz_no]);
        if(!$order) return ['code'=>-1, 'msg'=>'付款记录不存在'];
        
        // E3-A.2: 如果是 UNKNOWN 状态，尝试查询上游
        if($order['resolution'] == self::RES_UNKNOWN || $order['resolution'] == self::RES_PROCESSING){
            $result = self::queryAndFinalize($biz_no);
            return $result;
        }
        
        return ['code'=>0, 'status'=>$order['status'], 'resolution'=>$order['resolution'], 'msg'=>'当前状态: '.$order['resolution']];
    }

    //转账查询
    //status 0:处理中 1:成功 2:失败
    public static function query($type, $channel, $biz_no, $pay_order_no){
        $bizParam = [
            'type' => $type,
            'out_biz_no' => $biz_no,
            'orderid' => $pay_order_no
        ];
        return \lib\Plugin::call('transfer_query', $channel, $bizParam);
    }

    /**
     * E3-A.2: 撤销转账（仅 CREATED 状态可安全取消）
     * 
     * PROCESSING/UNKNOWN 状态禁止本地直接取消并释放资金，
     * 因为第三方可能已经转账。
     */
    public static function cancel($biz_no){
        global $DB;
        $order = $DB->find('transfer', '*', ['biz_no' => $biz_no]);
        if(!$order) return ['code'=>-1, 'msg'=>'付款记录不存在'];

        // E3-A.2: 只有 CREATED 状态可以安全取消
        if($order['resolution'] != self::RES_CREATED){
            return ['code'=>-1, 'msg'=>'当前状态('.$order['resolution'].')不允许取消。PROCESSING/UNKNOWN状态需通过查询或人工对账处理，禁止直接释放资金！'];
        }

        // E3-A.3: CREATED 状态尚未调用上游，不需要调用上游 transfer_cancel
        // 只有 PROCESSING 状态才需要尝试上游取消（但当前 cancel 只允许 CREATED）
        $result = ['code' => 0, 'msg' => 'CREATED state, no upstream call needed'];
        
        // 如果 channel 有效且已调用过上游，尝试调用上游取消
        if($order['channel'] > 0){
            $channelinfo = null;
            if($order['uid'] > 0){
                $channelinfo = $DB->findColumn('user', 'channelinfo', ['uid'=>$order['uid']]);
            }
            $channel = \lib\Channel::get($order['channel'], $channelinfo);
            if($channel){
                $bizParam = [
                    'type' => $order['type'],
                    'out_biz_no' => $order['biz_no'],
                    'orderid' => $order['pay_order_no'],
                ];
                $result = \lib\Plugin::call('transfer_cancel', $channel, $bizParam);
            }
        }
        
        // E3-A.2: 使用事务 + CAS 取消
        $DB->beginTransaction();
        try {
            $casResult = $DB->exec("UPDATE pre_transfer SET resolution=:res, status=:status, result='转账已撤销' WHERE biz_no=:biz_no AND resolution=:created", [
                ':res' => self::RES_CANCELLED,
                ':status' => self::STATUS_FAILED,
                ':biz_no' => $biz_no,
                ':created' => self::RES_CREATED
            ]);
            
            if($casResult > 0 && $order['uid'] > 0 && (float)$order['costmoney'] > 0){
                // 解冻资金
                $userRow = $DB->getRow("SELECT money, frozen_money FROM pre_user WHERE uid=:uid FOR UPDATE", [':uid'=>$order['uid']]);
                $frozen = (float)$userRow['frozen_money'];
                $costmoney = (float)$order['costmoney'];
                $newFrozen = round($frozen - $costmoney, 2);
                if($newFrozen < 0) $newFrozen = 0;
                
                $DB->exec("UPDATE pre_user SET frozen_money=:frozen WHERE uid=:uid", [
                    ':frozen' => $newFrozen,
                    ':uid' => $order['uid']
                ]);
                
                $DB->insert('record', ['uid'=>$order['uid'], 'action'=>4, 'money'=>$costmoney, 'oldmoney'=>$userRow['money'], 'newmoney'=>$userRow['money'], 'type'=>'转账解冻', 'trade_no'=>$biz_no, 'date'=>'NOW()']);
            }
            
            $DB->commit();
            $result['code'] = 0;
            $result['msg'] = '转账已撤销，资金已解冻';
            $result['resolution'] = self::RES_CANCELLED;
            return $result;
        } catch (Exception $e) {
            $DB->rollBack();
            return ['code'=>-1, 'msg'=>'取消转账失败: '.$e->getMessage()];
        }
    }

    //账户余额查询
    public static function balance($type, $channel, $user_id = null){
        $bizParam = [
            'type' => $type,
            'user_id' => $user_id
        ];
        return \lib\Plugin::call('balance_query', $channel, $bizParam);
    }

    //转账凭证查询
    public static function proof($biz_no){
        global $DB;
        $order = $DB->find('transfer', '*', ['biz_no' => $biz_no]);
        if(!$order) return ['code'=>-1, 'msg'=>'付款记录不存在'];
        
        $channelinfo = null;
        if($order['uid'] > 0){
            $channelinfo = $DB->findColumn('user', 'channelinfo', ['uid'=>$order['uid']]);
        }
        $channel = \lib\Channel::get($order['channel'], $channelinfo);
        if(!$channel) return ['code'=>-1, 'msg'=>'支付通道不存在'];

        $bizParam = [
            'type' => $order['type'],
            'out_biz_no' => $biz_no,
            'orderid' => $order['pay_order_no']
        ];
        return \lib\Plugin::call('transfer_proof', $channel, $bizParam);
    }

    /**
     * E3-A.2: 转账回调处理（使用事务 + CAS 保证 exactly-once）
     * 
     * status: 1=成功, 2=失败
     */
    public static function processNotify($biz_no, $status, $errmsg = null){
        global $DB;
        
        // 兼容 settle 表的转账回调
        $order = $DB->find('transfer', '*', ['biz_no' => $biz_no]);
        if(!$order) {
            $order = $DB->find('settle', '*', ['transfer_no' => $biz_no]);
            if(!$order) return;
            // settle 表的转账回调（保持原有逻辑）
            if($status == 2 && $order['transfer_status'] == 1){
                $DB->update('settle', ['transfer_status'=>2, 'transfer_result'=>$errmsg, 'status'=>3, 'result'=>$errmsg], ['id' => $order['id']]);
            }elseif($status == 1 && $order['transfer_status'] == 2){
                $DB->update('settle', ['transfer_status'=>1, 'status'=>1, 'result'=>''], ['biz_no' => $biz_no]);
            }
            return;
        }

        // E3-A.2: 使用 finalizeTransfer 统一处理（事务 + CAS）
        if($status == self::STATUS_SUCCESS){
            self::finalizeTransfer($biz_no, 'success', ['code'=>0, 'status'=>1, 'orderid'=>$order['pay_order_no']]);
        }elseif($status == self::STATUS_FAILED){
            self::finalizeTransfer($biz_no, 'rejected', ['code'=>-1, 'status'=>2, 'msg'=>$errmsg ?? '上游回调确认失败']);
        }
    }

    public static function red_add($uid, $type, $out_biz_no, $money, $desc = null, $channelid = null){
        global $conf, $DB, $userrow;
        $biz_no = $out_biz_no;
        if(strlen($biz_no)!=19 || !is_numeric($biz_no)) $biz_no = date("YmdHis").rand(11111,99999);

        if($uid > 0){
            if($conf['transfer_minmoney']>0 && $money<$conf['transfer_minmoney']) return ['code'=>-1, 'msg'=>'单笔最小代付金额限制为'.$conf['transfer_minmoney'].'元'];
            if($conf['transfer_maxmoney']>0 && $money>$conf['transfer_maxmoney']) return ['code'=>-1, 'msg'=>'单笔最大代付金额限制为'.$conf['transfer_maxmoney'].'元'];
        }
        
        if(!$channelid){
            if($type=='alipay'){
                $channelid = $conf['transfer_alipay'];
            }elseif($type=='wxpay'){
                $channelid = $conf['transfer_wxpay'];
            }else{
                return ['code'=>-1, 'msg'=>'type参数错误'];
            }
            if(!$channelid) return ['code'=>-1, 'msg'=>'未开启此转账方式'];
        }
        $channel = \lib\Channel::get($channelid, $userrow['channelinfo']);
        if(!$channel) return ['code'=>-1, 'msg'=>'当前支付通道信息不存在'];

        // E3-A.2: 红包也使用三阶段模型
        $need_money = null;
        if($uid > 0){
            if(!$conf['transfer_rate'])$conf['transfer_rate'] = $conf['settle_rate'];
            $need_money = round($money + $money*$conf['transfer_rate']/100,2);
        }
        
        $request_hash = self::makeRequestHash($uid, $type, $channelid, '', '', $money);

        // Phase 1: Reserve
        $DB->beginTransaction();
        try {
            $existing = $DB->getRow("SELECT * FROM pre_transfer WHERE uid=:uid AND out_biz_no=:out_biz_no FOR UPDATE", [':uid'=>$uid, ':out_biz_no'=>$out_biz_no]);
            if($existing){
                $DB->rollBack();
                return ['code'=>0, 'status'=>$existing['status'], 'biz_no'=>$existing['biz_no'], 'duplicate'=>true];
            }

            if($uid > 0){
                $userrow = $DB->getRow('SELECT * FROM pre_user WHERE uid=:uid FOR UPDATE', [':uid'=>$uid]);
                if(!$userrow || $userrow['settle']==0){
                    $DB->rollBack();
                    return ['code'=>-1, 'msg'=>'您的商户出现异常，无法使用代付功能'];
                }
                $frozen = isset($userrow['frozen_money']) ? (float)$userrow['frozen_money'] : 0;
                $available = round($userrow['money'] - $frozen, 2);
                if($need_money > $available){
                    $DB->rollBack();
                    return ['code'=>-1, 'msg'=>'需支付金额大于可转账余额'];
                }
                $newFrozen = round($frozen + $need_money, 2);
                $DB->exec("UPDATE pre_user SET frozen_money=:frozen WHERE uid=:uid", [':frozen'=>$newFrozen, ':uid'=>$uid]);
                $DB->insert('record', ['uid'=>$uid, 'action'=>3, 'money'=>$need_money, 'oldmoney'=>$userrow['money'], 'newmoney'=>$userrow['money'], 'type'=>'转账冻结', 'trade_no'=>$biz_no, 'date'=>'NOW()']);
            }

            $jumpurl = self::red_url($biz_no);
            $data = ['biz_no'=>$biz_no, 'out_biz_no'=>$out_biz_no, 'uid'=>$uid, 'type'=>$type, 'channel'=>$channelid, 'account'=>'', 'username'=>'', 'money'=>$money, 'costmoney'=>$need_money??$money, 'addtime'=>'NOW()', 'status'=>self::STATUS_RED_PACKET, 'resolution'=>self::RES_CREATED, 'request_hash'=>$request_hash, 'gateway_call_count'=>0, 'desc'=>$desc];
            $id = $DB->insert('transfer', $data);
            $DB->commit();
        } catch (Exception $e) {
            $DB->rollBack();
            return ['code'=>-1, 'msg'=>'创建红包失败'];
        }

        $result = ['code'=>0, 'status'=>self::STATUS_RED_PACKET, 'biz_no'=>$biz_no, 'out_biz_no'=>$out_biz_no, 'jumpurl'=>$jumpurl, 'resolution'=>self::RES_CREATED];
        $typename = $type == 'alipay' ? '支付宝' : ($type == 'wxpay' ? '微信' : '未知');
        $result['msg']='红包创建成功！请在'.$typename.'打开 '.$jumpurl.' 确认收款。';
        return $result;
    }

    public static function red_receive($biz_no, $openid){
        global $conf, $DB;

        $func = function() use ($biz_no, $openid){
            global $DB, $userrow;
            $trans = $DB->getRow("SELECT * FROM pre_transfer WHERE biz_no=:biz_no FOR UPDATE", [':biz_no'=>$biz_no]);
            if(!$trans) return ['code'=>-1, 'msg'=>'红包不存在'];
            if($trans['status'] != self::STATUS_RED_PACKET) return ['code'=>-1, 'msg'=>$trans['status']==1?'红包已领取':'红包状态异常，无法领取'];
            $channel = \lib\Channel::get($trans['channel'], $userrow['channelinfo']);
            if(!$channel) return ['code'=>-1, 'msg'=>'当前支付通道信息不存在'];

            // E3-A.2: 更新为 PROCESSING
            $DB->exec("UPDATE pre_transfer SET resolution=:res, gateway_call_count = gateway_call_count + 1 WHERE biz_no=:biz_no", [':res'=>self::RES_PROCESSING, ':biz_no'=>$biz_no]);

            $result = self::submit($trans['type'], $channel, $biz_no, $openid, '', $trans['money'], $trans['desc'], $trans['type']=='alipay'?null:$trans['desc']);
            if($result['code']==0){
                $gatewayResult = self::classifyGatewayResult($result);
                // E3-A.2: 使用 finalizeTransfer 结算
                $finalResult = self::finalizeTransfer($biz_no, $gatewayResult, $result);
                if(isset($result['wxpackage'])){
                    $wxinfo = \lib\Channel::getWeixin($channel['appwxmp']);
                    $finalResult['wxtransfer'] = [
                        'mchId' => $channel['appmchid'],
                        'appId' => $wxinfo['appid'],
                        'package' => $result['wxpackage'],
                    ];
                }
                return $finalResult;
            }
            return $result;
        };

        $DB->beginTransaction();
        $result = $func();
        if($result['code'] == 0){
            $DB->commit();
        }else{
            $DB->rollBack();
        }
        return $result;
    }

    public static function red_url($biz_no){
        global $siteurl;
        $t = time().'';
        $s = md5(SYS_KEY.$biz_no.$t.SYS_KEY);
        return $siteurl.'paypage/red.php?n='.$biz_no.'&t='.$t.'&s='.$s;
    }
}
