<?php

/*
Backend část stránky, která vypisuje SMS čekající na odeslání a umožnuje s nimi pracovat
*/

$startovniSqlOffset = 30;
$sqlLimit = (int) $startovniSqlOffset; // Nastavení počtu řádků tabulky v jeden okamžik, tedy za jeden SQL dotaz

if(!empty($_GET["p"])) {
	$sqlLimit .=" OFFSET ".((int) $startovniSqlOffset* (int) $_GET["p"]); // Zjištění, od kolikaté pozice donačíst další řádky
}

$sqlPodminka = "status = 'PENDING'";

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
Reakce na událost vyvolanou ve frontendu, zařadí do fronty testovací SMS
*/
if($_GET["case"] == "sendtest") {

	try {

		$core->sql->query("
			INSERT INTO sms_queue SET
				account = '".$core->sql->escape($core->conf["testAccountToken"])."',
				`created` = NOW(),
				`to` = '00420721125332',
				`body` = 'Test sms! ".time()."'
		");

		echo '<form>
		<div class="msgInfo">Testovací sms zařazena do fronty</div>
		<div class="buttons">
			<button type="button" onclick="window.location.reload();">Obnovit stránku</button>
		</div>
	</form>';

	} catch (Exception $e) {

		echo '<form>
		<div class="msgError">Něco se pokazilo</div>
		<div class="buttons">
			<button type="button" onclick="window.location.reload();">Obnovit stránku</button>
		</div>
	</form>';
	}

	$core->quit();

} else if ($_GET["case"] == "cron") { // Reakce na událost vyvolanou ve frontendu, spustí CRON (stará se o odesílání SMS)

	echo '
	<form>
		<div class="msgInfo">CRON se spustil</div>
		<div class="buttons">
			<button type="button" onclick="window.location.reload();">Obnovit stránku</button>
		</div>
	</form>
	<hr />';

	include_once 'cron.php';

	$core->quit();

} elseif($_GET["case"] == "cancel") { // Reakce na událost vyvolanou ve frontendu, Zruší vybranou SMS čekající na odeslání

	try {

		$core->sql->query("
			UPDATE sms_queue SET
				status = 'CANCELED'
			WHERE id = ".(int)$_GET["id"]."
		");

		echo json_encode(array("result"=>"ok"));

	} catch(Exception $e) {

		echo json_encode(array("error"=>$e->getMessage()));
	}

	$core->quit();
}
elseif($_GET["case"] == "errs") { // Reakce na událost vyvolanou ve frontendu, pro konkrétní SMS zobrazí logy (chyby při pokusech o odeslání)

	$chyby = $core->sql->toArray("
		SELECT *
		FROM sms_log
		WHERE id_sms = ".(int)$_GET["id"]."
		ORDER BY date DESC
	");

	alert($chyby);

	$core->quit();
}

// Získání zpráv čekajících na odeslání
$cekajiciZpravy = $core->sql->toArray("
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
	foreach($cekajiciZpravy as $polozka) {
		$vystup->assign("polozka", $polozka);
		$vystup->display("list_item.tpl");
	}

	$core->quit();
}
else {
	// Provede se při prvním načtění stránky pouze
	$vystup->assign("velikostFronty", $core->sql->fetchValue("
		SELECT COUNT(`id`)
		FROM sms_queue
		WHERE `status` = 'PENDING'
	"));

	$vystup->assign("posledniSpusteniCronu", $core->sql->fetchValue("
		SELECT `date`
		FROM sms_cron_log
		ORDER BY `date` DESC
		LIMIT 1
	"));

	$vystup->assign("cekajiciZpravy", $cekajiciZpravy);

	// Výpis obsahu šablony (frontendu)
	$vystup->display("queue.tpl");
}

?>
