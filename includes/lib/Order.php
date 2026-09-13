<?php
namespace lib;

class Order
{
    // A4: 退款五态状态机
    const REFUND_CREATED = 10;     // 退款意图已持久化，尚未调用上游
    const REFUND_PROCESSING = 20;  // 正在调用上游
    const REFUND_SUCCESS = 30;     // 上游明确确认成功且本地已结算
    const REFUND_REJECTED = 40;    // 上游明确业务拒绝
    const REFUND_UNKNOWN = 50;      // 结果未知，可能已到达上游

    public static function freeze($trade_no){
        global $DB;
        $row = $DB->find('order', 'uid,getmoney,status,channel', ['trade_no'=>$trade_no]);
        if(!$row)
            return ['code'=>-1, 'msg'=>'当前订单不存在！'];
        if($row['status']!=1)
            return ['code'=>-1, 'msg'=>'只支持冻结已支付状态的订单'];
        $channel = \lib\Channel::get($row['channel']);
        if($channel['mode']==1)
            return ['code'=>-1, 'msg'=>'当前支付通道为商户直清，不支持冻结'];
        if($row['getmoney']>0){
            changeUserMoney($row['uid'], $row['getmoney'], false, '订单冻结', $trade_no);
            $DB->update('order', ['status'=>3], ['trade_no'=>$trade_no]);
        }
        return ['code'=>0, 'msg'=>'已成功从UID:'.$row['uid'].'冻结'.$row['getmoney'].'元余额'];
    }

    public static function unfreeze($trade_no){
        global $DB;
        $row = $DB->find('order', 'uid,getmoney,status,channel', ['trade_no'=>$trade_no]);
        if(!$row)
            return ['code'=>-1, 'msg'=>'当前订单不存在！'];
        if($row['status']!=3)
            return ['code'=>-1, 'msg'=>'只支持解冻已冻结状态的订单'];
        $channel = \lib\Channel::get($row['channel']);
        if($channel['mode']==1)
            return ['code'=>-1, 'msg'=>'当前支付通道为商户直清，不支持冻结'];
        if($row['getmoney']>0){
            changeUserMoney($row['uid'], $row['getmoney'], true, '订单解冻', $trade_no);
            $DB->update('order', ['status'=>1], ['trade_no'=>$trade_no]);
        }
        return ['code'=>0, 'msg'=>'已成功为UID:'.$row['uid'].'恢复'.$row['getmoney'].'元余额'];
    }

    public static function refund_info($trade_no, $api = 0, $uid = 0){
        global $DB;
        $where = ['trade_no'=>$trade_no];
        if($uid > 0) $where['uid'] = $uid;
        $order = $DB->find('order', '*', $where);
        if(!$order)
            return ['code'=>-1, 'msg'=>'当前订单不存在！'];
        if(!in_array($order['status'], [1,2,3]))
            return ['code'=>-1, 'msg'=>'该订单状态不支持退款！'];
        if($order['status'] == 2 && empty($order['refundmoney'])) return ['code'=>-1, 'msg'=>'该订单已退款！'];
        if($order['status'] == 2 && $order['refundmoney'] > 0 && $order['refundmoney'] >= $order['realmoney']) return ['code'=>-1, 'msg'=>'该订单已全额退款！'];
        $money = !empty($order['refundmoney']) ? round($order['realmoney'] - $order['refundmoney'], 2) : $order['realmoney'];

        if($api==1){
            if(!$order['api_trade_no']) return ['code'=>-1, 'msg'=>'接口订单号不存在'];
            $channel = \lib\Channel::get($order['channel']);
            if(!$channel) return ['code'=>-1, 'msg'=>'当前支付通道信息不存在'];
            if(\lib\Plugin::isrefund($channel['plugin'])==false){
                return ['code'=>-1, 'msg'=>'当前支付通道不支持API退款'];
            }
        }

        return ['code'=>0, 'money'=>$money];
    }

    public static function refund($refund_no, $trade_no, $money, $api = 0, $uid = 0, $out_refund_no = null){
    global $DB, $order, $conf;

    $where = ['trade_no'=>$trade_no];
    if($uid > 0) $where['uid'] = $uid;
    $order = $DB->find('order', '*', $where);
    if(!$order) return ['code'=>-1, 'msg'=>'当前订单不存在！'];
    if(!in_array($order['status'], [1,2,3])) return ['code'=>-1, 'msg'=>'该订单状态不支持退款！'];

    // A4: 金额校验（不使用 empty()，避免 empty(0)=true）
    if($money === null || $money === '') $money = $order['realmoney'];
    $money = round((float)$money, 2);
    if($money <= 0) return ['code'=>-1, 'msg'=>'退款金额必须大于0'];
    if($money > $order['realmoney']) return ['code'=>-1, 'msg'=>'退款金额不能大于订单金额'];
    if($api == 1 && !$order['api_trade_no']) return ['code'=>-1, 'msg'=>'接口订单号不存在'];
    if($order['status'] == 2 && $order['refundmoney'] > 0 && $order['refundmoney'] >= $order['realmoney']) return ['code'=>-1, 'msg'=>'该订单已全额退款！'];

    if(!$out_refund_no) $out_refund_no = $refund_no;

    // 计算实际扣减商户金额
    $mode = $DB->findColumn('channel', 'mode', ['id'=>$order['channel']]);
    if($order['status'] == 3 || $mode == 1){
        $reducemoney = 0;
    }elseif(isset($conf['refund_fee_type']) && $conf['refund_fee_type']==1 && $money == $order['realmoney']){
        $reducemoney = $order['realmoney'];
    }elseif(!isset($conf['refund_fee_type']) && ($money == $order['realmoney'] || $money >= $order['getmoney'])){
        $reducemoney = $order['getmoney'];
    }else{
        $reducemoney = $money;
    }

    // ============================================================
    // 阶段一：短事务 - 持久化退款意图 + 冻结商户资金 + 预留退款额度
    // ============================================================
    try {
        $DB->beginTransaction();

        // 锁定订单行
        $lockOrder = $DB->getRow("SELECT * FROM pre_order WHERE trade_no=:tn FOR UPDATE", [':tn'=>$trade_no]);
        if(!$lockOrder) { $DB->rollBack(); return ['code'=>-1, 'msg'=>'订单不存在']; }

        // 重新计算剩余可退（锁定后）
        $lockReserved = isset($lockOrder['refund_reserved']) ? (float)$lockOrder['refund_reserved'] : 0;
        $lockRemaining = round((float)$lockOrder['realmoney'] - (float)$lockOrder['refundmoney'] - $lockReserved, 2);
        if($money > $lockRemaining) { $DB->rollBack(); return ['code'=>-1, 'msg'=>'退款金额不能超过剩余可退款金额（剩余：'.$lockRemaining.'元）']; }

        // 幂等检查：相同 out_refund_no 已有非终态退款单
        $existing = $DB->getRow("SELECT * FROM pre_refundorder WHERE trade_no=:tn AND out_refund_no=:orn LIMIT 1", [':tn'=>$trade_no, ':orn'=>$out_refund_no]);
        if($existing) {
            $DB->rollBack();
            return ['code'=>self::mapCode((int)$existing['status']), 'msg'=>'退款单已存在，状态：'.self::statusText($existing['status']), 'refund_no'=>$existing['refund_no']];
        }

        // A4: 冻结商户余额（frozen_money += reducemoney）
        if($uid > 0 && $reducemoney > 0){
            $userRow = $DB->getRow("SELECT money, frozen_money FROM pre_user WHERE uid=:uid FOR UPDATE", [':uid'=>$uid]);
            $available = (float)$userRow['money'] - (float)$userRow['frozen_money'];
            if($reducemoney > $available){ $DB->rollBack(); return ['code'=>-1, 'msg'=>'商户可用余额不足（可用：'.$available.'元）']; }
            $DB->exec("UPDATE pre_user SET frozen_money=frozen_money+:rm WHERE uid=:uid", [':rm'=>$reducemoney, ':uid'=>$uid]);
        }

        // 插入退款意图记录（CREATED）
        $now = date('Y-m-d H:i:s');
        $DB->insert('refundorder', [
            'refund_no'=>$refund_no,
            'out_refund_no'=>$out_refund_no,
            'trade_no'=>$trade_no,
            'uid'=>$order['uid'],
            'money'=>$money,
            'reducemoney'=>$reducemoney,
            'channel'=>$order['channel'],
            'reserved_money'=>$money,
            'status'=>self::REFUND_CREATED,
            'addtime'=>$now,
            'created_at'=>$now,
        ]);

        // 预留退款额度（refund_reserved += money）
        $DB->exec("UPDATE pre_order SET refund_reserved=refund_reserved+:m WHERE trade_no=:tn", [':m'=>$money, ':tn'=>$trade_no]);

        $DB->commit();
    } catch (\Exception $e) {
        $DB->rollBack();
        // 唯一约束冲突：并发重复提交
        if(strpos($e->getMessage(), 'uk_trade_out') !== false || strpos($e->getMessage(), 'Duplicate entry') !== false) {
            $existing = $DB->getRow("SELECT * FROM pre_refundorder WHERE trade_no=:tn AND out_refund_no=:orn LIMIT 1", [':tn'=>$trade_no, ':orn'=>$out_refund_no]);
            if($existing) return ['code'=>self::mapCode((int)$existing['status']), 'msg'=>'退款单已存在（并发幂等），状态：'.self::statusText($existing['status'])];
        }
        return ['code'=>-1, 'msg'=>'退款意图创建失败：'.$e->getMessage()];
    }

    // api=0：不调用上游，保持 CREATED 状态（手动退款意图）
    if($api == 0){
        return ['code'=>self::mapCode(self::REFUND_CREATED), 'msg'=>'退款意图已创建，等待处理', 'refund_no'=>$refund_no];
    }

    // ============================================================
    // 阶段二：事务外调用上游
    // ============================================================
    try {
        // 更新状态为 PROCESSING
        $DB->exec("UPDATE pre_refundorder SET status=".self::REFUND_PROCESSING.", processing_at=NOW() WHERE refund_no=:rn AND status=".self::REFUND_CREATED, [':rn'=>$refund_no]);

        $message = null;
        $upstreamResult = \lib\Plugin::refund($refund_no, $trade_no, $money, $message);

        // 解析上游结果（Plugin::refund 返回数组 ['code'=>..., 'msg'=>...]）
        if(is_array($upstreamResult) && isset($upstreamResult['code'])){
            if($upstreamResult['code'] == 0){
                $settleStatus = self::REFUND_SUCCESS;
                $settleMsg = isset($upstreamResult['msg']) ? $upstreamResult['msg'] : '上游退款成功';
            } elseif($upstreamResult['code'] == -1){
                $settleStatus = self::REFUND_REJECTED;
                $settleMsg = isset($upstreamResult['msg']) ? $upstreamResult['msg'] : '上游明确拒绝';
            } else {
                $settleStatus = self::REFUND_UNKNOWN;
                $settleMsg = isset($upstreamResult['msg']) ? $upstreamResult['msg'] : '退款结果未知，请人工对账';
            }
        } else {
            $settleStatus = self::REFUND_UNKNOWN;
            $settleMsg = '退款结果未知（上游返回格式异常），请人工对账';
        }
    } catch (\Exception $e) {
        // 网络异常、超时等：UNKNOWN（不自动重试）
        $settleStatus = self::REFUND_UNKNOWN;
        $settleMsg = '调用上游异常：'.$e->getMessage().'，请人工对账';
    }

    // ============================================================
    // 阶段三：短事务 - 按明确结果结算
    // ============================================================
    return self::settleRefund($refund_no, $trade_no, $money, $reducemoney, $settleStatus, $settleMsg);
}

/**
 * A4: 结算退款结果（阶段三，短事务）
 */
private static function settleRefund($refund_no, $trade_no, $money, $reducemoney, $status, $msg, $upstreamRefundNo=null){
    global $DB;

    try {
        $DB->beginTransaction();

        // 锁定退款记录
        $refundRow = $DB->getRow("SELECT * FROM pre_refundorder WHERE refund_no=:rn FOR UPDATE", [':rn'=>$refund_no]);
        if(!$refundRow) { $DB->rollBack(); return ['code'=>-1, 'msg'=>'退款记录不存在']; }

        // CAS：只允许从 CREATED 或 PROCESSING 转出
        $currentStatus = (int)$refundRow['status'];
        if(!in_array($currentStatus, [self::REFUND_CREATED, self::REFUND_PROCESSING])) {
            $DB->rollBack();
            return ['code'=>self::mapCode($currentStatus), 'msg'=>'退款单已结算，状态：'.self::statusText($currentStatus)];
        }

        $now = date('Y-m-d H:i:s');
        $uid = $refundRow['uid'];

        if($status == self::REFUND_SUCCESS){
            // SUCCESS：冻结转为实际扣减 + 更新订单退款金额 + 释放预留
            if($reducemoney > 0){
                $DB->exec("UPDATE pre_user SET frozen_money=frozen_money-:rm WHERE uid=:uid", [':rm'=>$reducemoney, ':uid'=>$uid]);
                $userRow = $DB->getRow("SELECT money FROM pre_user WHERE uid=:uid", [':uid'=>$uid]);
                $oldMoney = (float)$userRow['money'];
                $DB->exec("UPDATE pre_user SET money=money-:rm WHERE uid=:uid", [':rm'=>$reducemoney, ':uid'=>$uid]);
                $DB->exec("INSERT INTO pre_record (uid, action, money, oldmoney, newmoney, type, trade_no, date) VALUES (:uid, 2, :rm, :oldm, :newm, '订单退款', :tn, NOW())", [':uid'=>$uid, ':rm'=>$reducemoney, ':oldm'=>$oldMoney, ':newm'=>$oldMoney-$reducemoney, ':tn'=>$trade_no]);
            }
            $DB->exec("UPDATE pre_order SET refund_reserved=refund_reserved-:m, refundmoney=refundmoney+:m, status=2 WHERE trade_no=:tn", [':m'=>$money, ':tn'=>$trade_no]);
            $DB->exec("UPDATE pre_refundorder SET status=".self::REFUND_SUCCESS.", reserved_money=0, upstream_refund_no=".($upstreamRefundNo?$DB->quote($upstreamRefundNo):'NULL').", error_msg=NULL, settled_at='{$now}', endtime='{$now}' WHERE refund_no=:rn", [':rn'=>$refund_no]);

        } elseif($status == self::REFUND_REJECTED){
            // REJECTED：释放冻结余额 + 释放预留额度
            if($reducemoney > 0){
                $DB->exec("UPDATE pre_user SET frozen_money=frozen_money-:rm WHERE uid=:uid", [':rm'=>$reducemoney, ':uid'=>$uid]);
            }
            $DB->exec("UPDATE pre_order SET refund_reserved=refund_reserved-:m WHERE trade_no=:tn", [':m'=>$money, ':tn'=>$trade_no]);
            $DB->exec("UPDATE pre_refundorder SET status=".self::REFUND_REJECTED.", reserved_money=0, error_msg=".$DB->quote($msg).", settled_at='{$now}' WHERE refund_no=:rn", [':rn'=>$refund_no]);

        } else {
            // UNKNOWN：保留冻结余额 + 保留预留额度，不自动重试
            $DB->exec("UPDATE pre_refundorder SET status=".self::REFUND_UNKNOWN.", error_msg=".$DB->quote($msg).", settled_at='{$now}' WHERE refund_no=:rn", [':rn'=>$refund_no]);
        }

        $DB->commit();
        return ['code'=>self::mapCode($status), 'msg'=>$msg, 'refund_no'=>$refund_no, 'out_refund_no'=>$refundRow['out_refund_no'], 'trade_no'=>$trade_no, 'uid'=>$uid, 'money'=>$money, 'reducemoney'=>$reducemoney];

    } catch (\Exception $e) {
        $DB->rollBack();
        // 结算异常：保持 PROCESSING，可人工恢复，绝不自动重试上游
        return ['code'=>self::mapCode(self::REFUND_PROCESSING), 'msg'=>'结算异常（保持处理中，请人工对账）：'.$e->getMessage()];
    }
}

/**
 * A4: 人工确认 UNKNOWN 退款
 * 仅管理员可调用，必须填写原因和上游证据
 */
public static function confirmUnknown($refund_no, $confirmStatus, $reason, $evidence, $adminUser){
    global $DB;

    if(!in_array($confirmStatus, [self::REFUND_SUCCESS, self::REFUND_REJECTED])){
        return ['code'=>-1, 'msg'=>'无效的确认状态'];
    }
    if(empty($reason) || empty($evidence)){
        return ['code'=>-1, 'msg'=>'必须填写处置原因和上游对账证据'];
    }

    try {
        $DB->beginTransaction();

        $refundRow = $DB->getRow("SELECT * FROM pre_refundorder WHERE refund_no=:rn FOR UPDATE", [':rn'=>$refund_no]);
        if(!$refundRow){ $DB->rollBack(); return ['code'=>-1, 'msg'=>'退款记录不存在']; }
        if((int)$refundRow['status'] != self::REFUND_UNKNOWN){
            $DB->rollBack(); return ['code'=>-1, 'msg'=>'仅 UNKNOWN 状态的退款单可人工确认'];
        }

        $trade_no = $refundRow['trade_no'];
        $money = (float)$refundRow['money'];
        $reducemoney = (float)$refundRow['reducemoney'];
        $uid = $refundRow['uid'];
        $now = date('Y-m-d H:i:s');

        // 审计信息
        $audit = json_encode([
            'action'=>'manual_confirm',
            'admin'=>$adminUser,
            'from_status'=>self::REFUND_UNKNOWN,
            'to_status'=>$confirmStatus,
            'reason'=>$reason,
            'evidence'=>$evidence,
            'time'=>$now,
        ], JSON_UNESCAPED_UNICODE);

        if($confirmStatus == self::REFUND_SUCCESS){
            // 人工确认成功：冻结转为实际扣减
            if($reducemoney > 0){
                $DB->exec("UPDATE pre_user SET frozen_money=frozen_money-:rm WHERE uid=:uid", [':rm'=>$reducemoney, ':uid'=>$uid]);
                $userRow = $DB->getRow("SELECT money FROM pre_user WHERE uid=:uid", [':uid'=>$uid]);
                $oldMoney = (float)$userRow['money'];
                $DB->exec("UPDATE pre_user SET money=money-:rm WHERE uid=:uid", [':rm'=>$reducemoney, ':uid'=>$uid]);
                $DB->exec("INSERT INTO pre_record (uid, action, money, oldmoney, newmoney, type, trade_no, date) VALUES (:uid, 2, :rm, :oldm, :newm, '订单退款(人工确认)', :tn, NOW())", [':uid'=>$uid, ':rm'=>$reducemoney, ':oldm'=>$oldMoney, ':newm'=>$oldMoney-$reducemoney, ':tn'=>$trade_no]);
            }
            $DB->exec("UPDATE pre_order SET refund_reserved=refund_reserved-:m, refundmoney=refundmoney+:m, status=2 WHERE trade_no=:tn", [':m'=>$money, ':tn'=>$trade_no]);
            $DB->exec("UPDATE pre_refundorder SET status=".self::REFUND_SUCCESS.", reserved_money=0, error_msg='人工确认成功：{$reason}', audit_info=".$DB->quote($audit).", settled_at='{$now}' WHERE refund_no=:rn", [':rn'=>$refund_no]);
        } else {
            // 人工确认拒绝：释放冻结余额 + 释放预留额度
            if($reducemoney > 0){
                $DB->exec("UPDATE pre_user SET frozen_money=frozen_money-:rm WHERE uid=:uid", [':rm'=>$reducemoney, ':uid'=>$uid]);
            }
            $DB->exec("UPDATE pre_order SET refund_reserved=refund_reserved-:m WHERE trade_no=:tn", [':m'=>$money, ':tn'=>$trade_no]);
            $DB->exec("UPDATE pre_refundorder SET status=".self::REFUND_REJECTED.", reserved_money=0, error_msg='人工确认拒绝：{$reason}', audit_info=".$DB->quote($audit).", settled_at='{$now}' WHERE refund_no=:rn", [':rn'=>$refund_no]);
        }

        $DB->commit();
        return ['code'=>self::mapCode($confirmStatus), 'msg'=>'人工确认成功，状态已更新为：'.self::statusText($confirmStatus)];

    } catch (\Exception $e) {
        $DB->rollBack();
        return ['code'=>-1, 'msg'=>'人工确认失败：'.$e->getMessage()];
    }
}

/**
 * A4: 五态状态码 → 旧版兼容码
 * 30(SUCCESS)→0, 40(REJECTED)→-1, 50(UNKNOWN)→-2, 10/20→-3
 */
public static function mapCode($code){
    $map = [
        self::REFUND_SUCCESS => 0,
        self::REFUND_REJECTED => -1,
        self::REFUND_UNKNOWN => -2,
        self::REFUND_PROCESSING => -3,
        self::REFUND_CREATED => -3,
    ];
    return isset($map[$code]) ? $map[$code] : -3;
}

/**
 * A4: 状态码 → 文本描述
 */
public static function statusText($code){
    $map = [
        self::REFUND_CREATED => '已创建（待处理）',
        self::REFUND_PROCESSING => '处理中',
        self::REFUND_SUCCESS => '退款成功',
        self::REFUND_REJECTED => '上游拒绝',
        self::REFUND_UNKNOWN => '结果未知（待人工对账）',
    ];
    return isset($map[$code]) ? $map[$code] : '未知状态';
}
public static function close($trade_no, $uid = 0){
        global $DB, $order, $conf;

        $where = ['trade_no'=>$trade_no];
        if($uid > 0) $where['uid'] = $uid;
        $order = $DB->find('order', '*', $where);
        if(!$order)
            return ['code'=>-1, 'msg'=>'当前订单不存在！'];
        if($order['status'] != 0)
            return ['code'=>-1, 'msg'=>'该订单状态不支持关闭！'];

        if(!\lib\Plugin::close($trade_no, $message)){
            return ['code'=>-1, 'msg'=>$message];
        }
        return ['code'=>0];
    }
}