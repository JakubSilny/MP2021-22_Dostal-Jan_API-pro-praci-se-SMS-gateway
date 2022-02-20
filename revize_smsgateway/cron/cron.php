<?php
	
$chyby = array();
$odeslane = array();

$endpoint = "https://http-api.smsmanager.cz/Send";
$log = "";

$fronta = $core->sql->toArray("
	SELECT *
	FROM sms_queue
	WHERE status = 'PENDING'
	ORDER BY RAND()
	LIMIT 5
");

$velikostFronty = $core->sql->fetchValue("
	SELECT COUNT(id)
	FROM sms_queue
	WHERE status = 'PENDING'
");

$pocetOdeslanychZpravZaPosledniMesic = $core->sql->fetchValue("
	SELECT COUNT(*)
	FROM `sms_log` l
	WHERE l.`status` = 'SENT' 
		AND DATE(l.`date`) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) 
		AND l.`gateway` = 'INTERNAL'
");

if ($pocetOdeslanychZpravZaPosledniMesic > 2000) {
	$brana = "BACKUP";
} else {
	$brana = "INTERNAL";
}

foreach((array)$fronta as $k=>$v) {

	try {

		if ($brana == "BACKUP") {

			$dotaz = curl_init();
			curl_setopt($dotaz, CURLOPT_USERAGENT, "myAgent");
			curl_setopt($dotaz, CURLOPT_URL, $endpoint);
			curl_setopt($dotaz, CURLOPT_POST, 1);
			curl_setopt($dotaz, CURLOPT_POSTFIELDS,
			"apikey=".$core->conf["smsManagerToken"]."&number=".urlencode($fronta[$k]["to"])."&message=".urlencode($fronta[$k]["body"])."&gateway=high&sender=info-sms");
			curl_setopt($dotaz, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($dotaz, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($dotaz, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($dotaz, CURLOPT_TIMEOUT, 15);
			$log = curl_exec($dotaz);
			$statusChyby = curl_errno($dotaz);
			$obsahChyby = curl_error($dotaz);
			curl_close($dotaz);

			if(!empty($statusChyby)) {
				throw new Exception("Backup gateway connection error....".$statusChyby.", ".$obsahChyby);
			}

			if(strpos($log, "OK") !==0) {
  				throw new Exception("Backup gateway error....".$log);
			}

		} else {

			$prijemce = preg_replace("|[^0-9+]|", "", $fronta[$k]["to"]);

			$vystup = array();
			exec('gammu --sendsms TEXT '.$prijemce.' -text "'.addslashes($fronta[$k]["body"]).'"', $vystup);
			$log = implode("\n", $vystup);

			if(strpos($log, "answer..OK") === false) {  
				throw new Exception($log);
			}

		}

		$core->sql->query("
			INSERT INTO sms_log SET
				id_sms = ".(int)$fronta[$k]["id"].",
				date = NOW(),
				status = 'SENT',
				gateway = '".$core->sql->escape($brana)."',
				log = '".$core->sql->escape($log)."'
		");

		$core->sql->query("
			UPDATE sms_queue SET
				status = 'SENT',
				sent = NOW()
			WHERE id = ".(int)$fronta[$k]["id"]."
		");

		$odeslane[] = array(
			"id" => $fronta[$k]["id"]
		);

	} catch(Exception $e) {

		$core->sql->query("
			INSERT INTO sms_log SET
				id_sms = ".(int)$fronta[$k]["id"].",
				date = NOW(),
				status = 'ERROR',
				gateway = '".$core->sql->escape($brana)."',
				log = '".$core->sql->escape($e->getMessage())."'
		");

		$core->sql->query("
			UPDATE sms_queue SET
				status = 'ERROR'
			WHERE id = ".(int)$fronta[$k]["id"]."
		");

		$chyby[] = array(
			"id" => $fronta[$k]["id"],
			"msg" => $e->getMessage()
		);
	}
}

$cronLog = array(
	"queue" => $velikostFronty,
	"sent" => $odeslane,
	"err" => $chyby,
	"quotaState" => $pocetOdeslanychZpravZaPosledniMesic
);

$core->sql->query("
	INSERT INTO sms_cron_log SET
		`date` = NOW(),
		log = '".base64_encode(serialize($cronLog))."'
");

echo json_encode($cronLog, JSON_PRETTY_PRINT);

$core->quit();

?>
