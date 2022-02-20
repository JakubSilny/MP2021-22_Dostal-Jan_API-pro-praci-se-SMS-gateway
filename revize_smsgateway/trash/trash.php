<?php

/*
Backend část stránky, která vypisuje zrušené SMS a umožnuje s nimi pracovat
*/

$startovniSqlOffset = 30;
$sqlLimit = (int) $startovniSqlOffset; // Nastavení počtu řádků tabulky v jeden okamžik, tedy za jeden SQL dotaz

if(!empty($_GET["p"])) {
	$sqlLimit .=" OFFSET ".((int) $startovniSqlOffset* (int) $_GET["p"]); // Zjištění, od kolikaté pozice donačíst další řádky
}

$sqlPodminka = "status = 'CANCELED'";

/*
Reakce na formulář z frontendu, dle obsahu formuláře vyfiltruje tabulku
*/
if(!empty($_GET["q"])) {
	$sqlPodminka .= " AND (
		`to` LIKE '%".$core->sql->escape(str_replace("%", "", $_GET["q"]))."%'
		OR `body` LIKE '%".$core->sql->escape(str_replace("%", "", $_GET["q"]))."%'
		OR sms_account.label LIKE '%".$core->sql->escape(str_replace("%", "", $_GET["q"]))."%'
	)";
}

/*
Reakce na událost vyvolanou ve frontendu, pro konkrétní SMS zobrazí logy (chyby při pokusech o odeslání)
*/
if($_GET["case"] == "errs") {

	$chyby = $core->sql->toArray("
		SELECT *
		FROM sms_log
		WHERE id_sms = ".(int)$_GET["id"]."
		ORDER BY date DESC
	");

	alert($chyby);

	$core->quit();

} elseif($_GET["case"] == "repeat") { // Reakce na událost vyvolanou ve frontendu, pokusí se znovu odeslat zrušenou SMS umístěním do fronty

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

// Získání zrušených zpráv
$zruseneZpravy = $core->sql->toArray("
	SELECT sms_queue.*
		,(SELECT COUNT(*) FROM sms_log WHERE sms_log.id_sms = sms_queue.id) AS errCount
	FROM sms_queue
		LEFT JOIN sms_account ON sms_account.uuid = sms_queue.account
	WHERE ".$sqlPodminka."
	ORDER BY created DESC
	LIMIT ".$sqlLimit."
");


$ucty = $core->sql->toArray("
	SELECT uuid, label
	FROM sms_account
", "dual");

// Nastavení Smarty systému
$vystup = new smartyWrapper($core);

// Vybrání uložiště pro šablony
$vystup->setTemplateDir(__DIR__);

// Přenesení PHP proměnné do šablony (frontendu)
$vystup->assign("ucty", $ucty);

if($_GET["case"]=="getPage") {

	// Provede při žádosti o donačtění dalších řádků
	$vystup->display("list_head.tpl");
	foreach($zruseneZpravy as $polozka) {
		$vystup->assign("polozka", $polozka);
		$vystup->display("list_item.tpl");
	}

	$core->quit();
}
else {
	// Provede se při prvním načtění stránky pouze
	$vystup->assign("pocetZrusenychZprav", $core->sql->fetchValue("
		SELECT COUNT(`id`)
		FROM sms_queue
		WHERE `status` = 'CANCELED'
	"));

	$vystup->assign("zruseneZpravy", $zruseneZpravy);

	// Výpis obsahu šablony (frontendu)
	$vystup->display("trash.tpl");
}

?>
