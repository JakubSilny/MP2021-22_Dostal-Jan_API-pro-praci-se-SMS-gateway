<?php
// jiz existoval pred revizi, pouze revizovan a trochu vylepsen

$paginate = 30;
$limit = $paginate;

if(!empty($_GET["p"])) {
	$limit .=" OFFSET ".($paginate * (int) $_GET["p"]);
}

$where = "status = 'ERROR'";

if(!empty($_GET["q"])) {
	$where .= " AND (
		`to` LIKE '%".$core->sql->escape(str_replace("%", "", $_GET["q"]))."%'
		OR `body` LIKE '%".$core->sql->escape(str_replace("%", "", $_GET["q"]))."%'
		OR sms_account.label LIKE '%".$core->sql->escape(str_replace("%", "", $_GET["q"]))."%'
	)";
}


if($_GET["case"] == "errs") {
	$errs = $core->sql->toArray("
		SELECT *
		FROM sms_log
		WHERE id_sms = ".(int)$_GET["id"]."
		ORDER BY id DESC
	");
	alert($errs);
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



$list = $core->sql->toArray("
	SELECT sms_queue.*
		,(SELECT COUNT(*) FROM sms_log WHERE sms_log.id_sms = sms_queue.id) AS errCount
	FROM sms_queue
		LEFT JOIN sms_account ON sms_account.uuid = sms_queue.account
	WHERE ".$where."
	ORDER BY sent DESC
	LIMIT ".$limit."
");


$accounts = $core->sql->toArray("
	SELECT uuid, label
	FROM sms_account
", "dual");


$out = new smartyWrapper($core);
$out->setTemplateDir(__DIR__);
$out->assign("accounts", $accounts);

if($_GET["case"]=="getPage") {

	$out->display("list_head.tpl");
	foreach($list as $item) {
		$out->assign("item", $item);
		$out->display("list_item.tpl");
	}

	$core->quit();
}
else {

	$out->assign("sizeOfGaveUp", $core->sql->fetchValue("
		SELECT COUNT(`id`)
		FROM sms_queue
		WHERE `status` = 'ERROR'
	"));

	$out->assign("list", $list);
	$out->display("gaveup.tpl");
}

?>