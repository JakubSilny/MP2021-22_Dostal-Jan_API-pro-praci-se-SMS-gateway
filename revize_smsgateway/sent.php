<?php

$startovniSqlOffset = 30;
$sqlLimit = (int) $startovniSqlOffset;

if(!empty($_GET["p"])) {
	$sqlLimit .=" OFFSET ".((int)$startovniSqlOffset * (int) $_GET["p"]);
}


$sqlPodminka = "status = 'SENT'";
if(!empty($_GET["q"])) {
	$sqlPodminka .= " AND (
		`to` LIKE '%".$core->sql->escape(str_replace("%", "", $_GET["q"]))."%'
		OR `body` LIKE '%".$core->sql->escape(str_replace("%", "", $_GET["q"]))."%'
		OR sms_account.label LIKE '%".$core->sql->escape(str_replace("%", "", $_GET["q"]))."%'
	)";
}

$odeslaneZpravy = $core->sql->toArray("
	SELECT sms_queue.*
	FROM sms_queue
		LEFT JOIN sms_account ON sms_account.uuid = sms_queue.account
	WHERE ".$sqlPodminka."
	ORDER BY sent DESC
	LIMIT ".$sqlLimit."
");


$ucty = $core->sql->toArray("
	SELECT uuid, label
	FROM sms_account
", "dual");


$vystup = new smartyWrapper($core);
$vystup->setTemplateDir(__DIR__);
$vystup->assign("ucty", $ucty);

if($_GET["case"]=="getPage") {

	$vystup->display("list_head.tpl");
	foreach($odeslaneZpravy as $polozka) {
		$vystup->assign("polozka", $polozka);
		$vystup->display("list_item.tpl");
	}

	$core->quit();
}
else {

	$vystup->assign("odeslaneZpravy", $odeslaneZpravy);
	$vystup->display("sent.tpl");
}


?>