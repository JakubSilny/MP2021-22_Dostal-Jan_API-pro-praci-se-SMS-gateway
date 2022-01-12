<?php
// jiz existoval pred revizi, pouze revizovan a trochu vylepsen

$paginate = 30;
$limit = $paginate;
if(!empty($_GET["p"])) {
	$limit .=" OFFSET ".($paginate * (int) $_GET["p"]);
}


$where = "status = 'SENT'";
if(!empty($_GET["q"])) {
	$where .= " AND (
		`to` LIKE '%".$core->sql->escape(str_replace("%", "", $_GET["q"]))."%'
		OR `body` LIKE '%".$core->sql->escape(str_replace("%", "", $_GET["q"]))."%'
		OR sms_account.label LIKE '%".$core->sql->escape(str_replace("%", "", $_GET["q"]))."%'
	)";
}

$list = $core->sql->toArray("
	SELECT sms_queue.*
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

	$out->assign("list", $list);
	$out->display("sent.tpl");
}


?>