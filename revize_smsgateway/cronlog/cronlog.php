<?php

/*
Backend část stránky, která vypisuje všechny provedené spuštění CRONU
*/

$startovniSqlOffset = 30;
$sqlLimit = (int) $startovniSqlOffset; // Nastavení počtu řádků tabulky v jeden okamžik, tedy za jeden SQL dotaz

if(!empty($_GET["p"])) {
	$sqlLimit .=" OFFSET ".((int) $startovniSqlOffset * (int) $_GET["p"]); // Zjištění, od kolikaté pozice donačíst další řádky
}

// Získání všech logů spuštění CRONU
$cronLog = $core->sql->toArray("
	SELECT *
	FROM sms_cron_log
	ORDER BY `date` DESC
	LIMIT ".$sqlLimit."
");

// Logy jsou zakódované z bezpečnostních důvodů, proto se musí dekódovat zpět na podobu, se kterou se dá pracovat
foreach((array)$cronLog as $k=>$v) {
	$cronLog[$k]["log"] = unserialize(base64_decode($cronLog[$k]["log"]));
}

// Nastavení Smarty systému
$vystup = new smartyWrapper($core);

// Vybrání uložiště pro šablony
$vystup->setTemplateDir(__DIR__);

if($_GET["case"]=="getPage") {
	// Provede při žádosti o donačtění dalších řádků

	$vystup->display("listLog_head.tpl");

	foreach($cronLog as $polozka) {
		$vystup->assign("polozka", $polozka);
		$vystup->display("listLog_item.tpl");
	}

	$core->quit();
}
else {
	// Provede se při prvním načtění stránky pouze

	$vystup->assign("posledniSpusteniCronu", $core->sql->fetchValue("
		SELECT `date`
		FROM sms_cron_log
		ORDER BY `date` DESC
		LIMIT 1
	"));
	
	// Přenesení PHP proměnné do šablony (frontendu)
	$vystup->assign("cronLog", $cronLog);
	
	// Výpis obsahu šablony (frontendu)
	$vystup->display("cronlog.tpl");
}

?>
