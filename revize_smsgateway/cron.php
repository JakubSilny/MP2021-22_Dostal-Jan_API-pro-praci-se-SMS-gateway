<?php

// cron už předtím existoval, jen se upravil a modifikoval z me strany
		
$err = array();
$sent = array();
$queue = $core->sql->toArray("
	SELECT *
	FROM sms_queue
	WHERE status = 'PENDING'
	ORDER BY RAND()
	LIMIT 5
");

$queueSize = $core->sql->fetchValue("
	SELECT COUNT(id)
	FROM sms_queue
	WHERE status = 'PENDING'
");

$countOfSentSmsForLastMonth = $core->sql->fetchValue("
	SELECT COUNT(*)
	FROM `sms_log` l
	WHERE l.`status` = 'SENT' 
		AND DATE(l.`date`) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) 
		AND l.`gateway` = 'INTERNAL'
");
if ($countOfSentSmsForLastMonth > 2000) {
	$gateway = "BACKUP";
} else {
	$gateway = "INTERNAL";
}

foreach((array)$queue as $k=>$v) {

	try {

		if ($gateway == "BACKUP") {

			$url = "https://http-api.smsmanager.cz/Send";
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_USERAGENT, "myAgent");
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS,
			"apikey=".$core->conf["smsManagerToken"]."&number=".urlencode($queue[$k]["to"])."&message=".urlencode($queue[$k]["body"])."&gateway=high&sender=info-sms");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_TIMEOUT, 15);
			$result = curl_exec($ch);
			$errStatus = curl_errno($ch);
			$errmsg = curl_error($ch);
			curl_close($ch);

			if(!empty($errStatus)) {
				throw new Exception("Chyba spojení se záložní bránou: ".$errStatus.": ".$errmsg);
			}

			if(strpos($result, "OK")!==0) {
  				throw new Exception("Chyba záložní brány: ".$result);
			}

		} else {

			$to = preg_replace("|[^0-9+]|", "", $queue[$k]["to"]);

			$out = array();
			exec('gammu --sendsms TEXT '.$to.' -text "'.addslashes($queue[$k]["body"]).'"', $out);
			$result = implode("\n", $out);

			if(strpos($result, "answer..OK")===false) {  
				throw new Exception($result);
			}

		}

		$core->sql->query("
			INSERT INTO sms_log SET
				id_sms = ".(int)$queue[$k]["id"].",
				date = NOW(),
				status = 'SENT',
				gateway = '".$core->sql->escape($gateway)."',
				log = '".$core->sql->escape($result)."'
		");
		$core->sql->query("
			UPDATE sms_queue SET
				status = 'SENT',
				sent = NOW()
			WHERE id = ".(int)$queue[$k]["id"]."
		");
		$sent[] = array(
			"id" => $queue[$k]["id"]
		);

	} catch(Exception $e) {
		$core->sql->query("
			INSERT INTO sms_log SET
				id_sms = ".(int)$queue[$k]["id"].",
				date = NOW(),
				status = 'ERROR',
				gateway = '".$core->sql->escape($gateway)."',
				log = '".$core->sql->escape($e->getMessage())."'
		");
		$core->sql->query("
			UPDATE sms_queue SET
				status = 'ERROR'
			WHERE id = ".(int)$queue[$k]["id"]."
		");
		$err[] = array(
			"id" => $queue[$k]["id"],
			"msg" => $e->getMessage()
		);
	}
}

$ret = array(
	"queue" => $queueSize,
	"sent" => $sent,
	"err" => $err,
	"quotaState" => $countOfSentSmsForLastMonth
);
$core->sql->query("
	INSERT INTO sms_cron_log SET
		`date` = NOW(),
		log = '".base64_encode(serialize($ret))."'
");

echo json_encode($ret, JSON_PRETTY_PRINT);

$core->quit();

?>


