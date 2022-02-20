<?php

$startovniSqlOffset = 30;
$sqlLimit = (int) $startovniSqlOffset;

if(!empty($_GET["p"])) {
	$sqlLimit .=" OFFSET ".((int) $startovniSqlOffset * (int) $_GET["p"]);
}

$cronLog = $core->sql->toArray("
	SELECT *
	FROM sms_cron_log
	ORDER BY `date` DESC
	LIMIT ".$sqlLimit."
");

foreach((array)$cronLog as $k=>$v) {
	$cronLog[$k]["log"] = unserialize(base64_decode($cronLog[$k]["log"]));
}

$vystup = new smartyWrapper($core);
$vystup->setTemplateDir(__DIR__);

if($_GET["case"]=="getPage") {

	$vystup->display("listLog_head.tpl");

	foreach($cronLog as $polozka) {
		$vystup->assign("polozka", $polozka);
		$vystup->display("listLog_item.tpl");
	}

	$core->quit();
}
else {

	$vystup->assign("posledniSpusteniCronu", $core->sql->fetchValue("
		SELECT `date`
		FROM sms_cron_log
		ORDER BY `date` DESC
		LIMIT 1
	"));

	$vystup->assign("cronLog", $cronLog);
	$vystup->display("cronlog.tpl");
}

?>