<?php

/*
Představuje skript, který se automaticky spouští každých 5 minut v plánovači (CRON), pokouší se postupně odeslat SMS z fronty (queue)
*/
	
$chyby = array(); // obsahuje seznam SMS, které se nepovedlo odeslat
$odeslane = array(); // obsahuje seznam SMS, které se povedlo odeslat

$endpoint = "https://http-api.smsmanager.cz/Send"; // endpoint na API záložního systému pro odesílání SMS
$log = ""; // Obsahuje log každé jednotlivé zprávy, obsahuje výstup dané metody (Gammu, API) a informuje o úspěchu či neúspěchu odeslání

// Z fronty (queue) vybere náhodně několik čekajících SMS
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

// Spočítá počet odeslaných SMS pomocí primárního systému (Gammu) za poslední měsíc
$pocetOdeslanychZpravZaPosledniMesic = $core->sql->fetchValue("
	SELECT COUNT(*)
	FROM `sms_log` l
	WHERE l.`status` = 'SENT' 
		AND DATE(l.`date`) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) 
		AND l.`gateway` = 'INTERNAL'
");

// Na spočítaného počtu se rozhodne, jaký systém se použije
if ($pocetOdeslanychZpravZaPosledniMesic > 2000) {
	$brana = "BACKUP";
} else {
	$brana = "INTERNAL";
}

foreach((array)$fronta as $k=>$v) {

	try {

		if ($brana == "BACKUP") {
			/*
			Vytvoří Http request za pomocí CURL a pošle ho na endpoint API záložního systému
			*/
			$dotaz = curl_init();
			curl_setopt($dotaz, CURLOPT_USERAGENT, "myAgent");
			curl_setopt($dotaz, CURLOPT_URL, $endpoint); // Zadám endpoint
			curl_setopt($dotaz, CURLOPT_POST, 1); // Nastavím jako POST metodu
			curl_setopt($dotaz, CURLOPT_POSTFIELDS, // Nastavím payload requestu (obsah)
			"apikey=".$core->conf["smsManagerToken"]."&number=".urlencode($fronta[$k]["to"])."&message=".urlencode($fronta[$k]["body"])."&gateway=high&sender=info-sms");
			curl_setopt($dotaz, CURLOPT_RETURNTRANSFER, 1); // Nastavím, že očekávám odpověď (response)
			curl_setopt($dotaz, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($dotaz, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($dotaz, CURLOPT_TIMEOUT, 15); // Nastavím maximální dobu (15 sekund), jak dlouho se čeká na odpověď od API
			$log = curl_exec($dotaz); // Http response payload (odpověď)
			$statusChyby = curl_errno($dotaz); // status kód chyby
			$obsahChyby = curl_error($dotaz); // obsah chyby
			curl_close($dotaz);

			// Nastane např. když vyprší limit 15 sekund, nedostane odpověď, tak CURL vygeneruje chybu
			if(!empty($statusChyby)) {
				throw new Exception("Backup gateway connection error....".$statusChyby.", ".$obsahChyby);
			}

			// Pokud odpověď API říká, že odeslání SMS se nepovedlo
			if(strpos($log, "OK") !==0) {
  				throw new Exception("Backup gateway error....".$log);
			}

		} else {

			// Zkontroluji správný formát telefonního čísla
			$prijemce = preg_replace("|[^0-9+]|", "", $fronta[$k]["to"]);

			$vystup = array();
			exec('gammu --sendsms TEXT '.$prijemce.' -text "'.addslashes($fronta[$k]["body"]).'"', $vystup); // Spustí se Gammu příkaz
			$log = implode("\n", $vystup); // Výstup příkazu

			// Pokud odpověď Gammu říká, že odeslání SMS se nepovedlo
			if(strpos($log, "answer..OK") === false) {  
				throw new Exception($log);
			}

		}

		// V případě odeslání vložím log o odeslání k SMS
		$core->sql->query("
			INSERT INTO sms_log SET
				id_sms = ".(int)$fronta[$k]["id"].",
				date = NOW(),
				status = 'SENT',
				gateway = '".$core->sql->escape($brana)."',
				log = '".$core->sql->escape($log)."'
		");

		// Změním stav SMS na odesláno
		$core->sql->query("
			UPDATE sms_queue SET
				status = 'SENT',
				sent = NOW()
			WHERE id = ".(int)$fronta[$k]["id"]."
		");
		
		// Přidám SMS do seznamu SMS, které se povedlo odeslat
		$odeslane[] = array(
			"id" => $fronta[$k]["id"]
		);

	} catch(Exception $e) {
		
		// V případě chyby vložím log o neúspěchu k SMS
		$core->sql->query("
			INSERT INTO sms_log SET
				id_sms = ".(int)$fronta[$k]["id"].",
				date = NOW(),
				status = 'ERROR',
				gateway = '".$core->sql->escape($brana)."',
				log = '".$core->sql->escape($e->getMessage())."'
		");

		// Změním stav SMS na vzdané (ERROR)
		$core->sql->query("
			UPDATE sms_queue SET
				status = 'ERROR'
			WHERE id = ".(int)$fronta[$k]["id"]."
		");
		
		// Přidám SMS do seznamu SMS, které se nepovedlo odeslat
		$chyby[] = array(
			"id" => $fronta[$k]["id"],
			"msg" => $e->getMessage()
		);
	}
}

// Vygeneruji nový log tohoto konkrétního spuštění CRONU
$cronLog = array(
	"queue" => $velikostFronty, // Použiji velikost fronty jako užitečnou informaci (Debugging, diagnostika)
	"sent" => $odeslane, // Použiji odeslané SMS jako užitečnou informaci (Debugging, diagnostika)
	"err" => $chyby, // Použiji neodeslané SMS jako užitečnou informaci (Debugging, diagnostika)
	"quotaState" => $pocetOdeslanychZpravZaPosledniMesic // Dává přehled o stavu kvóty, tedy jak moc se toto číslo blíží k limitu 2000
);

// Vložím log do databáze
$core->sql->query("
	INSERT INTO sms_cron_log SET
		`date` = NOW(),
		log = '".base64_encode(serialize($cronLog))."'
");

// Vypíšu obsah logu v JSON formátu
echo json_encode($cronLog, JSON_PRETTY_PRINT);

$core->quit();

?>
