<?php

/*
Backend část stránky pro výpis uživatelských účtů
*/

$startovniSqlOffset = 30; 
$sqlLimit = (int) $startovniSqlOffset; // Nastavení počtu řádků tabulky v jeden okamžik, tedy za jeden SQL dotaz

if(!empty($_GET["p"])) {
	$sqlLimit .=" OFFSET ".((int) $startovniSqlOffset* (int) $_GET["p"]); // Zjištění, od kolikaté pozice donačíst další řádky
}

// Získání účtů
$ucty = $core->sql->toArray("
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
	LIMIT ".$sqlLimit."
");

// Nastavení Smarty systému
$vystup = new smartyWrapper($core);
// Vybrání uložiště pro šablony
$vystup->setTemplateDir(__DIR__);

if($_GET["case"]=="getPage") {
	// Provede při žádosti o donačtění dalších řádků
	$vystup->display("accounts_head.tpl");
	foreach($ucty as $polozka) {
		$vystup->assign("polozka", $polozka);
		$vystup->display("accounts_item.tpl");
	}

	$core->quit();
}
else {
	// Provede se při prvním načtění stránky pouze
	$vystup->assign("pocetUzivatelu", $core->sql->fetchValue("
		SELECT COUNT(*)
		FROM sms_account
	"));
	
	// Přenesení PHP proměnné do šablony
	$vystup->assign("ucty", $ucty);
	// Výpis obsahu šablony
	$vystup->display("accounts.tpl");
}

?>
