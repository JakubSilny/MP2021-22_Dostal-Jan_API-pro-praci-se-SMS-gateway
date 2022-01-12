<?php

// Kód se moc nevymýšlel, vzešel částečně z revize sms gateway

$paginate = 30;
$limit = $paginate;

if(!empty($_GET["p"])) {
	$limit .=" OFFSET ".($paginate* (int) $_GET["p"]);
}


$accounts = $core->sql->toArray("
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
	LIMIT ".$limit."
");


/*
$accounts = $core->sql->toArray("
	SELECT 
		a.`uuid` AS uuid,
		a.`label` AS label,
		COUNT(q.`id`) AS numberOfSentSmsTotally,
		SUM(IF(DATE(q.`sent`) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH), 1, 0)) AS numberOfSentSmsLastMonth
	FROM `sms_account` a 
		LEFT JOIN `sms_queue` q ON (a.`uuid` = q.`account` AND q.`status` = 'SENT')
	GROUP BY a.uuid
	ORDER BY a.`label`
	LIMIT ".$limit."
");
*/

/*
$accounts = $core->sql->toArray("
	SELECT 
		a.`uuid` AS uuid,
		a.`label` AS label,
		COUNT(q.`id`) AS numberOfSentSmsTotally,
		COUNT(q2.`id`) AS numberOfSentSmsLastMonth
	FROM `sms_account` a 
		LEFT JOIN `sms_queue` q ON (a.`uuid` = q.`account` AND q.`status` = 'SENT')
		LEFT JOIN `sms_queue` q2 ON (a.`uuid` = q2.`account` AND q2.`status` = 'SENT' AND DATE(q2.`sent`) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
	GROUP BY a.uuid
	ORDER BY a.`label`
	LIMIT ".$limit."
");
*/

$out = new smartyWrapper($core);
$out->setTemplateDir(__DIR__);
$out->assign("accounts", $accounts);

if($_GET["case"]=="getPage") {

	$out->display("accounts_head.tpl");
	foreach($accounts as $item) {
		$out->assign("item", $item);
		$out->display("accounts_item.tpl");
	}

	$core->quit();
}
else {

	$out->assign("accounts", $accounts);
	$out->display("accounts.tpl");
}

?>