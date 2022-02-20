<?php
$startovniSqlOffset = 30;
$sqlLimit = (int) $startovniSqlOffset;

if(!empty($_GET["p"])) {
	$sqlLimit .=" OFFSET ".((int) $startovniSqlOffset* (int) $_GET["p"]);
}


$ucty = $core->sql->toArray("
	SELECT 
		a.*,
		(
			SELECT COUNT(*)
			FROM `sms_queue`
			WHERE `account` = a.uuid
				AND `status` = 'SENT'
		) AS numberOfSentSmsTotally,
		(
			SELECT COUNT(*)
			FROM `sms_queue`
			WHERE `account` = a.uuid
				AND `status` = 'SENT'
				AND DATE(`sent`) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
		) AS numberOfSentSmsLastMonth
	FROM `sms_account` a 
	ORDER BY a.`label`
	LIMIT ".$sqlLimit."
");

$vystup = new smartyWrapper($core);
$vystup->setTemplateDir(__DIR__);

if($_GET["case"]=="getPage") {

	$vystup->display("accounts_head.tpl");
	foreach($ucty as $polozka) {
		$vystup->assign("polozka", $polozka);
		$vystup->display("accounts_item.tpl");
	}

	$core->quit();
}
else {

	$vystup->assign("pocetUzivatelu", $core->sql->fetchValue("
		SELECT COUNT(*)
		FROM sms_account
	"));

	$vystup->assign("ucty", $ucty);
	$vystup->display("accounts.tpl");
}

?>