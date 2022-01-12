<?php
// jiz existoval pred revizi, pouze revizovan a trochu vylepsen
$paginate = 30;
$limit = $paginate;

if(!empty($_GET["p"])) {
	$limit .=" OFFSET ".($paginate * (int) $_GET["p"]);
}

$listLog = $core->sql->toArray("
	SELECT *
	FROM sms_cron_log
	ORDER BY `date` DESC
	LIMIT ".$limit."
");

foreach((array)$listLog as $k=>$v) {
	$listLog[$k]["log"] = unserialize(base64_decode($listLog[$k]["log"]));
}

$out = new smartyWrapper($core);
$out->setTemplateDir(__DIR__);

if($_GET["case"]=="getPage") {

	$out->display("listLog_head.tpl");

	foreach($listLog as $item) {
		$out->assign("item", $item);
		$out->display("listLog_item.tpl");
	}

	$core->quit();
}
else {

	$out->assign("lastCronDate", $core->sql->fetchValue("
		SELECT `date`
		FROM sms_cron_log
		ORDER BY `date` DESC
		LIMIT 1
	"));

	$out->assign("listLog", $listLog);
	$out->display("cronlog.tpl");
}

?>