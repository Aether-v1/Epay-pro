<?php
$clientip=real_ip($conf['ip_type']?$conf['ip_type']:0);

if(isset($_COOKIE["admin_token"]))
{
	$token=authcode(daddslashes($_COOKIE['admin_token']), 'DECODE', SYS_KEY);
	list($user, $sid, $expiretime) = explode("\t", $token);
	$session=md5($conf['admin_user'].substr($conf['admin_pwd'],0,20).$password_hash); // A1: 不使用明文密码
	if($session==$sid && $expiretime>time()) {
		// Batch D Phase1: 验证管理员状态（新 RBAC 模型）
		$admin_status_ok = true;
		try {
			$admin_row = $DB->getRow("SELECT id, status FROM pre_admins WHERE username = ?", [$user]);
			if ($admin_row && $admin_row['status'] != 1) {
				$admin_status_ok = false;
			}
		} catch (Exception $e) {
			// pre_admins 表不存在时使用旧模型
		}
		if ($admin_status_ok) {
			$islogin=1;
		}
	}
}
if(isset($_COOKIE["user_token"]))
{
	$token=authcode(daddslashes($_COOKIE['user_token']), 'DECODE', SYS_KEY);
	list($uid, $sid, $expiretime) = explode("\t", $token);
	$uid = intval($uid);
	$userrow=$DB->getRow("SELECT * FROM pre_user WHERE uid=:uid limit 1", [':uid'=>$uid]);
	$session=md5($userrow['uid'].$userrow['key'].$password_hash);
	if($userrow && $session==$sid && $expiretime>time()) {
		$islogin2=1;
	}
}
?>