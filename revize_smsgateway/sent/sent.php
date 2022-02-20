<?php

/*
Backend část stránky, která vypisuje odeslané SMS
*/

$startovniSqlOffset = 30;
$sqlLimit = (int) $startovniSqlOffset; // Nastavení počtu řádků tabulky v jeden okamžik, tedy za jeden SQL dotaz

if(!empty($_GET["p"])) {
	$sqlLimit .=" OFFSET ".((int)$startovniSqlOffset * (int) $_GET["p"]); // Zjištění, od kolikaté pozice donačíst další řádky
}


$sqlPodminka = "status = 'SENT'";

/*
Reakce na formulář ze šablony, dle obsahu formuláře vyfiltruje tabulku
*/
if(!empty($_GET["q"])) {
	$sqlPodminka .= " AND (
		`to` LIKE '%".$core->sql->escape(str_replace("%", "", $_GET["q"]))."%'
		OR `body` LIKE '%".$core->sql->escape(str_replace("%", "", $_GET["q"]))."%'
		OR sms_account.label LIKE '%".$core->sql->escape(str_replace("%", "", $_GET["q"]))."%'
	)";
}

// Získání odeslaných zpráv
$odeslaneZpravy = $core->sql->toArray("
	SELECT sms_queue.*
	FROM sms_queue
		LEFT JOIN sms_account ON sms_account.uuid = sms_queue.account
	WHERE ".$sqlPodminka."
	ORDER BY sent DESC
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

// Přenesení PHP proměnné do šablony
$vystup->assign("ucty", $ucty);

if($_GET["case"]=="getPage") {

	// Provede při žádosti o donačtění dalších řádků
	$vystup->display("list_head.tpl");
	foreach($odeslaneZpravy as $polozka) {
		$vystup->assign("polozka", $polozka);
		$vystup->display("list_item.tpl");
	}

	$core->quit();
}
else {

	// Provede se při prvním načtění stránky pouze
	$vystup->assign("odeslaneZpravy", $odeslaneZpravy);
	
	// Výpis obsahu šablony
	$vystup->display("sent.tpl");
}


?>
