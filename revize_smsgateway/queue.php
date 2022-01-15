<?php

// jiz existoval pred revizi, pouze revizovan a trochu vylepsen


$paginate = 30;
$limit = $paginate;

if(!empty($_GET["p"])) {
	$limit .=" OFFSET ".($paginate* (int) $_GET["p"]);
}

$where = "status = 'PENDING'";

if(!empty($_GET["q"])) {
	$where .= " AND (
		`to` LIKE '%".$core->sql->escape(str_replace("%", "", $_GET["q"]))."%'
		OR `body` LIKE '%".$core->sql->escape(str_replace("%", "", $_GET["q"]))."%'
		OR sms_account.label LIKE '%".$core->sql->escape(str_replace("%", "", $_GET["q"]))."%'
	)";
}

if($_GET["case"] == "sendtest") {

	try {

		$core->sql->query("
			INSERT INTO sms_queue SET
				account = '".$core->sql->escape($core->conf["testAccountToken"])."',
				`created` = NOW(),
				`to` = '00420721125332',
				`body` = 'Test sms! ".time()."'
		");

		echo '<form>
		<div class="msgInfo">Testovací sms zařazena do fronty</div>
		<div class="buttons">
			<button type="button" onclick="window.location.reload();">Obnovit stránku</button>
		</div>
	</form>';
	} catch (Exception $e) {

		echo '<form>
		<div class="msgError">Něco se pokazilo</div>
		<div class="buttons">
			<button type="button" onclick="window.location.reload();">Obnovit stránku</button>
		</div>
	</form>';
	}

	$core->quit();
} elseif($_GET["case"] == "cancel") {
	try {

		$core->sql->query("
			UPDATE sms_queue SET
				status = 'CANCELED'
			WHERE id = ".(int)$_GET["id"]."
		");
		echo json_encode(array("result"=>"ok"));
	} catch(Exception $e) {
		echo json_encode(array("error"=>$e->getMessage()));
	}
	$core->quit();
}
elseif($_GET["case"] == "errs") {
	$errs = $core->sql->toArray("
		SELECT *
		FROM sms_log
		WHERE id_sms = ".(int)$_GET["id"]."
		ORDER BY id DESC
	");
	alert($errs);
	$core->quit();
}


// $out = array();
// exec('gammu getallsms', $out);
// alert($out);

$list = $core->sql->toArray("
	SELECT sms_queue.*
		,(SELECT COUNT(*) FROM sms_log WHERE sms_log.id_sms = sms_queue.id) AS errCount
	FROM sms_queue
		LEFT JOIN sms_account ON sms_account.uuid = sms_queue.account
	WHERE ".$where."
	ORDER BY created DESC
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

	$out->assign("sizeOfQueue", $core->sql->fetchValue("
		SELECT COUNT(`id`)
		FROM sms_queue
		WHERE `status` = 'PENDING'
	"));

	$out->assign("lastCronDate", $core->sql->fetchValue("
		SELECT `date`
		FROM sms_cron_log
		ORDER BY `date` DESC
		LIMIT 1
	"));

	$out->assign("list", $list);
	$out->display("queue.tpl");
}

?>