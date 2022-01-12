<?php

try {

	
	if(!empty($_GET["uuid"])) {
		// konkretni klient

		$client = $core->sql->fetchArray("
			SELECT *
			FROM `sms_account` a
			WHERE a.`uuid` = '".$core->sql->escape($_GET["uuid"])."'
		");
		if(empty($client)) {
			throw new Exception("Nenalezeno");
		}

		$core->page["label"] .= " pro ".$client["label"];
		
	} else {
		// seznam klientu

		$sentSmsPerAccounts = $core->sql->toArray("
			SELECT 
				a.`uuid` AS uuid,
				a.`label` AS label,
				COUNT(q.`id`) AS numberOfSentSms
			FROM `sms_account` a
				LEFT JOIN `sms_queue` q ON (a.`uuid` = q.`account` AND q.`status` = 'SENT')
			GROUP BY a.`uuid`
			ORDER BY a.`label` ASC 
			LIMIT 100
		");
	}


	$sentSmsPerDays = $core->sql->toArray("
		SELECT
			DATE(`sent`) AS dateOfSending,
			COUNT(`id`) as numberOfSentSms
		FROM `sms_queue`
		WHERE `status` = 'SENT'
			AND DATE(`sent`) >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
			".(!empty($client) ? "AND account = '".$client["uuid"]."'" : "")."
		GROUP BY DATE(`sent`)
		LIMIT 100
	", "first");

	$t = strtotime("-1 year");
	while($t<=time()) {
		$d = date("Y-m-d", $t);
		if(!isset($sentSmsPerDays[$d])) {
			$sentSmsPerDays[$d] = array(
				"dateOfSending" => $d,
				"numberOfSentSms" => 0
			);
		}
		$t = strtotime("+1 day", $t);
	}

	krsort($sentSmsPerDays);

	$out = new smartyWrapper($core);
	$out->setTemplateDir(__DIR__);

	$out->assign("sentSmsPerAccounts", $sentSmsPerAccounts);
	$out->assign("sentSmsPerDays", $sentSmsPerDays);
	$out->assign("client", $client);

	$out->display("stats.tpl");


} catch(Exception $e) {
	echo $e->getMessage();
}

?>