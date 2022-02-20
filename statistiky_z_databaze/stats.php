<?php

try {

	if(!empty($_GET["uuid"])) {

		$uzivatel = $core->sql->fetchArray("
			SELECT *
			FROM `sms_account` a
			WHERE a.`uuid` = '".$core->sql->escape($_GET["uuid"])."'
		");

		if(empty($uzivatel)) {
			throw new Exception("Nenalezeno");
		}

		$core->page["label"] .= " pro ".$uzivatel["label"];
		
	} else {

		$odeslaneSmsDleUzivatelu = $core->sql->toArray("
			SELECT 
				a.`uuid` AS uuid,
				a.`label` AS label,
				COUNT(q.`id`) AS numberOfSentSms
			FROM `sms_account` a
				LEFT JOIN `sms_queue` q ON (a.`uuid` = q.`account` AND q.`status` = 'SENT')
			GROUP BY a.`uuid`
			ORDER BY a.`label` ASC
		");
	}


	$odeslaneSmsDleDnu = $core->sql->toArray("
		SELECT
			DATE(`sent`) AS dateOfSending,
			COUNT(`id`) as numberOfSentSms
		FROM `sms_queue`
		WHERE `status` = 'SENT'
			AND DATE(`sent`) >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
			".(!empty($uzivatel) ? "AND account = '".$core->sql->escape($uzivatel["uuid"])."'" : "")."
		GROUP BY DATE(`sent`)
	", "first");

	$casPredJednymRokem = strtotime("-1 year");
	while($casPredJednymRokem <= time()) {
		$datum = date("Y-m-d", $casPredJednymRokem);
		if(!isset($odeslaneSmsDleDnu[$datum])) {
			$odeslaneSmsDleDnu[$datum] = array(
				"dateOfSending" => $datum,
				"numberOfSentSms" => 0
			);
		}
		$casPredJednymRokem = strtotime("+1 day", $casPredJednymRokem);
	}

	krsort($odeslaneSmsDleDnu);

	$vystup = new smartyWrapper($core);
	$vystup->setTemplateDir(__DIR__);

	$vystup->assign("odeslaneSmsDleUzivatelu", $odeslaneSmsDleUzivatelu);
	$vystup->assign("odeslaneSmsDleDnu", $odeslaneSmsDleDnu);
	$vystup->assign("uzivatel", $uzivatel);

	$vystup->display("stats.tpl");


} catch(Exception $e) {
	echo $e->getMessage();
}

?>