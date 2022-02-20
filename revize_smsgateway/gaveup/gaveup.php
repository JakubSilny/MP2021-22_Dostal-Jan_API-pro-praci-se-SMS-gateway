<?php

$startovniSqlOffset = 30;
$sqlLimit = (int) $startovniSqlOffset;

if(!empty($_GET["p"])) {
	$sqlLimit .=" OFFSET ".((int) $startovniSqlOffset * (int) $_GET["p"]);
}

$sqlPodminka = "status = 'ERROR'";

if(!empty($_GET["q"])) {

	$sqlPodminka .= " AND (
		`to` LIKE '%".$core->sql->escape(str_replace("%", "", $_GET["q"]))."%'
		OR `body` LIKE '%".$core->sql->escape(str_replace("%", "", $_GET["q"]))."%'
		OR sms_account.label LIKE '%".$core->sql->escape(str_replace("%", "", $_GET["q"]))."%'
	)";
}


if($_GET["case"] == "errs") {

	$chyby = $core->sql->toArray("
		SELECT *
		FROM sms_log
		WHERE id_sms = ".(int)$_GET["id"]."
		ORDER BY date DESC
	");
	alert($chyby);
	$core->quit();

} elseif($_GET["case"] == "repeat") {

	try {

		$core->sql->query("
			UPDATE sms_queue SET
				status = 'PENDING'
			WHERE id = ".(int)$_GET["id"]."
		");

		echo json_encode(array("result"=>"ok"));
	} catch(Exception $e) {

		echo json_encode(array("error"=>$e->getMessage()));
	}

	$core->quit();
}



$vzdaneZpravy = $core->sql->toArray("
	SELECT sms_queue.*
		,(SELECT COUNT(*) FROM sms_log WHERE sms_log.id_sms = sms_queue.id) AS errCount
	FROM sms_queue
		LEFT JOIN sms_account ON sms_account.uuid = sms_queue.account
	WHERE ".$sqlPodminka."
	ORDER BY created DESC
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
	foreach($vzdaneZpravy as $polozka) {
		$vystup->assign("polozka", $polozka);
		$vystup->display("list_item.tpl");
	}

	$core->quit();
}
else {

	$vystup->assign("pocetVzdanychZprav", $core->sql->fetchValue("
		SELECT COUNT(`id`)
		FROM sms_queue
		WHERE `status` = 'ERROR'
	"));

	$vystup->assign("vzdaneZpravy", $vzdaneZpravy);
	$vystup->display("gaveup.tpl");
}

?>