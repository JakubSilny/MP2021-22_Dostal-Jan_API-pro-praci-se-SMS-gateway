<?php
// cron už předtím existoval, jen se upravil a modifikoval z me strany a jeste bude modifikovan kvuli implementaci zalozniho systemu
		
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

foreach((array)$queue as $k=>$v) {

	try {


		$out = array();
		exec('gammu --sendsms TEXT '.$queue[$k]["to"].' -text "'.addslashes($queue[$k]["body"]).'"', $out);
		$result = implode("\n", $out);

		
		$core->sql->query("
			INSERT INTO sms_log SET
				id_sms = ".(int)$queue[$k]["id"].",
				date = NOW(),
				log = '".$core->sql->escape($result)."'
		");

		if(strpos($result, "answer..OK")===false) {  
			throw new Exception($result);
		} else {
			$core->sql->query("
				UPDATE sms_queue SET
					status = 'SENT',
					sent = NOW()
				WHERE id = ".(int)$queue[$k]["id"]."
			");
			$sent[] = array(
				"id" => $queue[$k]["id"]
			);
		}
	} catch(Exception $e) {
		$core->sql->query("
			UPDATE sms_queue SET
				status = 'ERROR',
				sent = NOW()
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
);
$core->sql->query("
	INSERT INTO sms_cron_log SET
		`date` = NOW(),
		log = '".base64_encode(serialize($ret))."'
");

echo json_encode($ret, JSON_PRETTY_PRINT);

$core->quit();
// prida se odbocovaci blok pro aktivaci sluzby zalozni pri prekroceni odeslanych sms 2000 za měsíc a dat do ret sentOverBackup, aby se vedelo kolik sms se odeslalo pres backup kvuli uctovani
?>
