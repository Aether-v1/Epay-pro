<?php
//程序安装文件
error_reporting(0);
date_default_timezone_set("PRC");
$databaseFile = '../config.php';//数据库配额文件

@header('Content-Type: text/html; charset=UTF-8');
session_start();
$step=isset($_GET['step'])?$_GET['step']:1;
if(file_exists('install.lock')){
    exit('你已经成功安装，如需重新安装，请手动删除install目录下install.lock文件！');
}

function clearpack() {
	$array=glob('../epay_release*');
	foreach($array as $dir){
		unlink($dir);
	}
	$array=glob('../epay_update*');
	foreach($array as $dir){
		unlink($dir);
	}
}

function random($length, $numeric = 0) {
	$seed = base_convert(md5(microtime().$_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
	$seed = $numeric ? (str_replace('0', '', $seed).'012340567890') : ($seed.'zZ'.strtoupper($seed));
	$hash = '';
	$max = strlen($seed) - 1;
	for($i = 0; $i < $length; $i++) {
		$hash .= $seed[mt_rand(0, $max)];
	}
	return $hash;
}

if($step==3){
    if($_GET['jump']==1){
        include '../config.php';
        if(!$dbconfig['user']||!$dbconfig['pwd']||!$dbconfig['dbname']) {
            $errorMsg='请先填写好数据库并保存后再安装！';
        }
    }else{
        $host=isset($_POST['host'])?$_POST['host']:null;
        $port=isset($_POST['port'])?$_POST['port']:null;
        $user=isset($_POST['user'])?$_POST['user']:null;
        $pwd=isset($_POST['pwd'])?$_POST['pwd']:null;
        $database=isset($_POST['database'])?$_POST['database']:null;
        $dbqz=isset($_POST['dbqz'])?$_POST['dbqz']:null;
        if(empty($host) || empty($port) || empty($user) || empty($pwd) || empty($database) || empty($dbqz)){
            $errorMsg='请填写完整所有数据库信息！';
        }
        $dbconfig=array(
            'host' => $host,
            'port' => $port,
            'user' => $user,
            'pwd' => $pwd,
            'dbname' => $database,
            'dbqz' => $dbqz
        );
        $config="<?php
    /*数据库配置*/
    \$dbconfig=array(
        'host' => '{$host}', //数据库服务器
        'port' => {$port}, //数据库端口
        'user' => '{$user}', //数据库用户名
        'pwd' => '{$pwd}', //数据库密码
        'dbname' => '{$database}', //数据库名
        'dbqz' => '{$dbqz}' //数据表前缀
    );
    ";
    }
    if(empty($errorMsg)){
        try{
            $DB=new PDO("mysql:host=".$dbconfig['host'].";dbname=".$dbconfig['dbname'].";port=".$dbconfig['port'],$dbconfig['user'],$dbconfig['pwd']);
        }catch(Exception $e){
            if($e->getCode() == 2002){
                $errorMsg='连接数据库失败：数据库地址填写错误！';
            }elseif($e->getCode() == 1045){
                $errorMsg='连接数据库失败：数据库用户名或密码填写错误！';
            }elseif($e->getCode() == 1049){
                $errorMsg='连接数据库失败：数据库名不存在！';
            }else{
                $errorMsg='连接数据库失败：'.$e->getMessage();
            }
        }
        if(empty($errorMsg)){
            $DB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
            $DB->exec("set sql_mode = ''");
            $DB->exec("set names utf8");
            $mysqlversion = $DB->query("select version()")->fetchColumn();
            if(version_compare($mysqlversion, '5.5.3', '<')){
                $errorMsg='MySQL数据库版本太低，需要MySQL 5.6或以上版本！';
            }
            if(!$_GET['jump'] && !file_put_contents($databaseFile, $config)){
                $errorMsg='保存失败，请确保网站根目录有写入权限';
            }
        }
    }
}elseif($step==4){
    include '../config.php';
    if(!$dbconfig['user']||!$dbconfig['pwd']||!$dbconfig['dbname']) {
        $errorMsg='请先填写好数据库并保存后再安装！';
    }else{
        try{
            $DB=new PDO("mysql:host=".$dbconfig['host'].";dbname=".$dbconfig['dbname'].";port=".$dbconfig['port'],$dbconfig['user'],$dbconfig['pwd']);
        }catch(Exception $e){
            $errorMsg='连接数据库失败：'.$e->getMessage();
        }
        if(empty($errorMsg) && !$_GET['jump']){
            $dbqz = $dbconfig['dbqz'];
            $DB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
            $DB->exec("set sql_mode = ''");
            $DB->exec("set names utf8");
            $sqls=file_get_contents('install.sql');
            $sqls=explode(';', $sqls);
            $sqls[]="INSERT INTO `".$dbqz."_config` VALUES ('syskey', '".random(32)."')";
            $sqls[]="INSERT INTO `".$dbqz."_config` VALUES ('build', '".date("Y-m-d")."')";
            $sqls[]="INSERT INTO `".$dbqz."_config` VALUES ('cronkey', '".rand(111111,999999)."')";
            $success=0;$error=0;$errorMsg=null;
            foreach ($sqls as $value) {
                $value=trim($value);
                if(empty($value))continue;
                $value = str_replace('pre_',$dbqz.'_',$value);
                if($DB->exec($value)===false){
                    $error++;
                    $dberror=$DB->errorInfo();
                    $errorMsg.=$dberror[2]."<br>";
                }else{
                    $success++;
                }
            }

            // 设置默认管理员账号密码（使用 password_hash）
            if(empty($errorMsg)){
                $defaultPwdHash = password_hash('123456', PASSWORD_DEFAULT);
                $DB->exec("UPDATE `".$dbqz."_config` SET v='admin' WHERE k='admin_user'");
                $DB->exec("UPDATE `".$dbqz."_config` SET v='".$defaultPwdHash."' WHERE k='admin_pwd'");
            }

            // 执行安全数据库迁移
            if(empty($errorMsg)){
                $migrationResults = [];

                // === Batch A 迁移 ===
                // 1. pay_user.frozen_money
                $colExists = $DB->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema='".$dbconfig['dbname']."' AND table_name='".$dbqz."_user' AND column_name='frozen_money'")->fetchColumn();
                if(!$colExists){
                    $DB->exec("ALTER TABLE `".$dbqz."_user` ADD COLUMN `frozen_money` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '冻结余额（退款中占用）'");
                    $migrationResults[] = 'pay_user.frozen_money added';
                }
                // 2. pay_order.refund_reserved
                $colExists = $DB->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema='".$dbconfig['dbname']."' AND table_name='".$dbqz."_order' AND column_name='refund_reserved'")->fetchColumn();
                if(!$colExists){
                    $DB->exec("ALTER TABLE `".$dbqz."_order` ADD COLUMN `refund_reserved` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '退款额度预留'");
                    $migrationResults[] = 'pay_order.refund_reserved added';
                }
                // 3. pay_refundorder 新字段
                $refundCols = [
                    'resolution' => "varchar(20) NOT NULL DEFAULT 'CREATED' COMMENT '退款状态（CREATED/PROCESSING/SUCCESS/REJECTED/UNKNOWN）'",
                    'gateway_trade_no' => "varchar(64) DEFAULT NULL COMMENT '上游退款单号'",
                    'reason' => "text DEFAULT NULL COMMENT '退款原因'",
                    'evidence' => "text DEFAULT NULL COMMENT '上游证据'",
                    'admin_id' => "int(11) DEFAULT NULL COMMENT '操作管理员ID'",
                    'confirmed_at' => "datetime DEFAULT NULL COMMENT '确认时间'",
                ];
                foreach($refundCols as $col => $def){
                    $colExists = $DB->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema='".$dbconfig['dbname']."' AND table_name='".$dbqz."_refundorder' AND column_name='".$col."'")->fetchColumn();
                    if(!$colExists){
                        $DB->exec("ALTER TABLE `".$dbqz."_refundorder` ADD COLUMN `".$col."` ".$def);
                        $migrationResults[] = 'pay_refundorder.'.$col.' added';
                    }
                }
                // 4. pay_refundorder.uk_trade_out 唯一约束
                $idxExists = $DB->query("SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema='".$dbconfig['dbname']."' AND table_name='".$dbqz."_refundorder' AND index_name='uk_trade_out'")->fetchColumn();
                if(!$idxExists){
                    $DB->exec("ALTER TABLE `".$dbqz."_refundorder` ADD UNIQUE KEY `uk_trade_out` (`trade_no`, `out_refund_no`)");
                    $migrationResults[] = 'pay_refundorder.uk_trade_out added';
                }

                // === RBAC 迁移 ===
                $DB->exec("CREATE TABLE IF NOT EXISTS `".$dbqz."_admins` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `username` varchar(50) NOT NULL,
                    `password_hash` varchar(255) NOT NULL,
                    `status` tinyint(1) NOT NULL DEFAULT 1,
                    `created_at` datetime DEFAULT NULL,
                    `updated_at` datetime DEFAULT NULL,
                    `last_login_at` datetime DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `username` (`username`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                $DB->exec("CREATE TABLE IF NOT EXISTS `".$dbqz."_roles` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `name` varchar(50) NOT NULL,
                    `description` varchar(255) DEFAULT NULL,
                    `status` tinyint(1) NOT NULL DEFAULT 1,
                    `created_at` datetime DEFAULT NULL,
                    `updated_at` datetime DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `name` (`name`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                $DB->exec("CREATE TABLE IF NOT EXISTS `".$dbqz."_permissions` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `permission_key` varchar(100) NOT NULL,
                    `description` varchar(255) DEFAULT NULL,
                    `created_at` datetime DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `permission_key` (`permission_key`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                $DB->exec("CREATE TABLE IF NOT EXISTS `".$dbqz."_admin_roles` (
                    `admin_id` int(11) NOT NULL,
                    `role_id` int(11) NOT NULL,
                    PRIMARY KEY (`admin_id`,`role_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                $DB->exec("CREATE TABLE IF NOT EXISTS `".$dbqz."_role_permissions` (
                    `role_id` int(11) NOT NULL,
                    `permission_id` int(11) NOT NULL,
                    PRIMARY KEY (`role_id`,`permission_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                $migrationResults[] = 'RBAC tables created';

                // === Transfer Safety 迁移 ===
                // 1. pay_transfer.resolution
                $colExists = $DB->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema='".$dbconfig['dbname']."' AND table_name='".$dbqz."_transfer' AND column_name='resolution'")->fetchColumn();
                if(!$colExists){
                    $DB->exec("ALTER TABLE `".$dbqz."_transfer` ADD COLUMN `resolution` varchar(20) NOT NULL DEFAULT 'CREATED' COMMENT 'E3-A.2: 细粒度转账状态' AFTER `status`");
                    $migrationResults[] = 'pay_transfer.resolution added';
                }
                // 2. pay_transfer.request_hash
                $colExists = $DB->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema='".$dbconfig['dbname']."' AND table_name='".$dbqz."_transfer' AND column_name='request_hash'")->fetchColumn();
                if(!$colExists){
                    $DB->exec("ALTER TABLE `".$dbqz."_transfer` ADD COLUMN `request_hash` varchar(64) DEFAULT NULL COMMENT 'E3-A.2: 请求指纹用于幂等冲突检测' AFTER `resolution`");
                    $migrationResults[] = 'pay_transfer.request_hash added';
                }
                // 3. pay_transfer.gateway_call_count
                $colExists = $DB->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema='".$dbconfig['dbname']."' AND table_name='".$dbqz."_transfer' AND column_name='gateway_call_count'")->fetchColumn();
                if(!$colExists){
                    $DB->exec("ALTER TABLE `".$dbqz."_transfer` ADD COLUMN `gateway_call_count` int(11) NOT NULL DEFAULT 0 COMMENT 'E3-A.2: 上游调用次数' AFTER `request_hash`");
                    $migrationResults[] = 'pay_transfer.gateway_call_count added';
                }
                // 4. pay_transfer.uk_uid_outbizno 唯一约束
                $idxExists = $DB->query("SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema='".$dbconfig['dbname']."' AND table_name='".$dbqz."_transfer' AND index_name='uk_uid_outbizno'")->fetchColumn();
                if(!$idxExists){
                    $DB->exec("ALTER TABLE `".$dbqz."_transfer` ADD UNIQUE KEY `uk_uid_outbizno` (`uid`, `out_biz_no`)");
                    $migrationResults[] = 'pay_transfer.uk_uid_outbizno added';
                }
            }
        }
        if(empty($errorMsg)){
            $lock_status = file_put_contents("install.lock",'安装锁');
            clearpack();
            $step = 5;
        }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" name="viewport">
    <meta content="yes" name="apple-mobile-web-app-capable">
    <meta content="black" name="apple-mobile-web-app-status-bar-style">
    <title>彩虹易支付 - 安装程序</title>
    <link href="//lib.baomitu.com/twitter-bootstrap/3.4.1/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container"><br>
    <div class="row">
        <div class="col-xs-12 col-sm-10 col-md-8 center-block" style="float: none;">
            <pre><h4>彩虹易支付 - 安装程序（安全加固版）</h4></pre>
            <div class="panel panel-warning">
                <?php
                if($step==2){
                ?>
                <div class="panel-heading text-center">MYSQL数据库信息配置</div>
                <div class="panel-body">
                    <div class="list-group text-success">
                        <form class="form-horizontal" action="?step=3" method="post">
                            <div class="form-group">
                                <label class="col-sm-2 control-label">数据库地址</label>
                                <div class="col-sm-10">
                                    <input type="text" name="host" class="form-control" value="localhost" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">数据库端口</label>
                                <div class="col-sm-10">
                                    <input type="text" name="port" class="form-control" value="3306" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">数据库用户名</label>
                                <div class="col-sm-10">
                                    <input type="text" name="user" class="form-control" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 control-label">数据库密码</label>
                                <div class="col-sm-10">
                                    <input type="text" name="pwd" class="form-control" required>
                                </div>
                            </div>
							<div class="form-group">
                                <label class="col-sm-2 control-label">数据库名称</label>
                                <div class="col-sm-10">
                                    <input type="text" name="database" class="form-control" required>
                                </div>
                            </div>
							<div class="form-group">
                                <label class="col-sm-2 control-label">数据表前缀</label>
                                <div class="col-sm-10">
                                    <input type="text" name="dbqz" class="form-control" value="pay" readonly>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-sm-offset-2 col-sm-10">
                                    <button type="submit" class="btn btn-success btn-block">确认无误，下一步</button>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-sm-offset-2 col-sm-10">
                                （如果已事先填写好config.php相关数据库配置，请 <a href="?step=3&jump=1">点击此处</a> 跳过这一步！）
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <?php }elseif($step==3){ ?>
                <div class="panel-heading text-center">保存数据库配置</div>
                <div class="panel-body">
<?php
if(!empty($errorMsg)){
    echo '<div class="alert alert-danger text-center" role="alert">'.$errorMsg.'</div><div class="list-group-item"><a href="javascript:history.back(-1)" class="btn btn-block btn-info"><< 返回上一页</a></div>';
}else{
    echo '<div class="alert alert-success text-center" role="alert">数据库配置文件保存成功！</div>';
    if($DB->query("select * from ".$dbconfig['dbqz']."_config")){
?>
                <div class="list-group-item list-group-item-info text-center">系统检测到你已安装过彩虹易支付</div>
				<div class="list-group-item">
					<a href="?step=4&jump=1" class="btn btn-block btn-info">跳过安装数据表</a>
				</div>
				<div class="list-group-item">
					<a href="?step=4" onclick="if(!confirm('全新安装将会清空所有数据，是否继续？')){return false;}" class="btn btn-block btn-warning">强制全新安装</a>
				</div>
<?php }else{?>
                <div class="list-group-item">
					<a href="?step=4" class="btn btn-block btn-success">立即安装数据表 >></a>
				</div>
<?php }
}
?>
                </div>
                <?php }elseif($step==4){ ?>
                <div class="panel-heading text-center">安装数据表</div>
                <div class="panel-body">
                    <div class="alert alert-danger" role="alert"><?php echo $errorMsg?></div>
                    <div class="list-group-item"><a href="?step=4" class="btn btn-block btn-warning">点此进行重试</a></div>
                    <div class="list-group-item"><a href="javascript:history.back(-1)" class="btn btn-block btn-info"><< 返回上一页</a></div>
                </div>
                <?php }elseif($step==5){ ?>
                <div class="panel-heading text-center">安装完成</div>
                <div class="panel-body">
                    <?php if($success>0){?><div class="alert alert-success" role="alert">成功执行SQL语句<?php echo $success;?>条，失败<?php echo $error;?>条！</div><?php }?>
                    <div class="alert alert-info" role="alert">
                        <strong>安全加固版已自动执行：</strong><br>
                        ✓ 默认管理员账号：admin / 123456（password_hash加密）<br>
                        ✓ Batch A 数据库迁移（退款资金冻结）<br>
                        ✓ RBAC 数据库迁移（管理员角色权限）<br>
                        ✓ 转账安全迁移（状态机+唯一约束）
                    </div>
                    <ul class="list-group">
                        <li class="list-group-item">1、系统已成功安装完毕！</li>
                        <li class="list-group-item">2、后台地址：<a href="/admin/" target="_blank">/admin/</a> 账号:admin 密码:123456</li>
                        <li class="list-group-item">3、请及时修改后台管理员密码！</li>
                        <?php if(!$lock_status){?><li class="list-group-item">4、<font color="#FF0033">你的空间不支持本地文件读写，请自行在 /install/ 目录建立 install.lock 文件！</font></li><?php }?>
                        <li class="list-group-item"><a href="/" class="btn btn-block btn-default">进入网站首页</a></li>
                    </ul>
                </div>
                <?php }else{ ?>
                <div class="panel-heading text-center">安装环境检测</div>
                <div class="panel-body">
                    <?php
                    $install=true;
                    if(function_exists('curl_exec')){
                        $check[2]='<span class="pull-right label label-success">支持</span>';
                    }else{
                        $check[2]='<span class="pull-right label label-danger">不支持</span>';
                        $install=false;
                    }
                    if(class_exists("PDO")){
                        $check[0]='<span class="pull-right label label-success">支持</span>';
                    }else{
                        $check[0]='<span class="pull-right label label-danger">不支持</span>';
                        $install=false;
                    }
                    // 修复：文件不存在时检查目录是否可写
                    if(file_exists($databaseFile)) {
                        $writable = is_writable($databaseFile);
                    } else {
                        $writable = is_writable(dirname($databaseFile));
                    }
                    if($writable) {
                        $check[1]='<span class="pull-right label label-success">支持</span>';
                    }else{
                        $check[1]='<span class="pull-right label label-danger">不支持</span>';
                    }
                    if(version_compare(PHP_VERSION,'7.4.0','<')){
                        $check[3]='<span class="pull-right label label-danger">不支持</span>';
                        $install=false;
                    }else{
                        $check[3]='<span class="pull-right label label-success">支持</span>';
                    }
                    if(function_exists('password_hash')){
                        $check[4]='<span class="pull-right label label-success">支持</span>';
                    }else{
                        $check[4]='<span class="pull-right label label-danger">不支持</span>';
                        $install=false;
                    }

                    ?>
                    <ul class="list-group">
                        <li class="list-group-item">PHP版本>=7.4 <?php echo $check[3];?></li>
                        <li class="list-group-item">PDO_MYSQL组件 <?php echo $check[0];?></li>
                        <li class="list-group-item">CURL组件 <?php echo $check[2];?></li>
                        <li class="list-group-item">主目录写入权限 <?php echo $check[1];?></li>
                        <li class="list-group-item">password_hash支持 <?php echo $check[4];?></li>
                        <li class="list-group-item">成功安装后安装文件就会锁定，如需重新安装，请手动删除install目录下install.lock配置文件！</li>
                        <?php
                        if($install) echo'<li class="list-group-item"><a href="?step=2" class="btn btn-block btn-default">检测通过，下一步</a></li>';
                        ?>
                    </ul>
                </div>
                <?php } ?>
            </div>
            <footer class="footer">
            <pre><center>Powered by <a href="#">彩虹</a> !</center></pre>
            </footer>
        </div>  
    </div>
</div>
</body>
</html>
