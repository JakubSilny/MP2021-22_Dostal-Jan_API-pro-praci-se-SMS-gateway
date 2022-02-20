{* 
Šablona používaná jako frontend pro stránku, která vypisuje SMS čekající na odeslání a umožnuje s nimi pracovat
*}

{* Představuje události, na které reaguje backend (PHP skript) *}
<div class="toolbar">
	<button type="button" href="?case=cron" onclick="$.colorbox({ innerWidth:600, innerHeight:500, href:$(this).attr('href') });"><i class="fas fa-play"></i> CRON</button>
	<button type="button" href="?case=sendtest" onclick="$.colorbox({ innerWidth:600, innerHeight:500, href:$(this).attr('href') });"><i class="fas fa-envelope-open-text"></i> Testovací SMS do fronty</button>
</div>

{* 
Zobrazuje chybovou zprávu o posledním spuštění CRONU, v případě, že poslední spuštění CRONU proběhlo před nestandardní dobou
*}
{if time()-strtotime($posledniSpusteniCronu) > 600}
<div class="msgError">
	CRON se naposledy spustil: {$posledniSpusteniCronu|date:"diff"}
</div>
{/if}

<div class="msgInfo">
	Velikost fronty je {$velikostFronty}
</div>

{* 
Formulář používající metodu GET pro vyfiltrování tabulky, backend (PHP skript) reaguje
*}
<form action="" method="get" style="padding: 20px; margin-bottom: 10px; background: #eee; text-align: center;">
	<input type="text" name="q" value="{$_GET.q|htmlspecialchars}" placeholder="Vyhledat..." style="height: 30px; vertical-align: middle; padding: 0 8px; border: 1px solid #ccc; background: #fff;">
	<button type="submit" style="height: 32px; vertical-align: middle;">Hledat</button>
</form>

<table class="adminTable">
	{* 
	Používají se šablony (jiné soubory)
	*}
	<thead>
		{include file="list_head.tpl"}
	</thead>
	<tbody id="nacistDalsi_cil">
		{foreach $cekajiciZpravy as $polozka}
			{include file="list_item.tpl" type='pending'}
		{/foreach}
	</tbody>
</table>
<button type="button" id="nacistDalsi_tlacitko" onclick="nacistDalsi();">Načíst další</button>

{* 
Skript používající se k postupnému donačítání dalších řádků tabulky, aby se nenačetly všechny řádky naráz, když to není potřeba
*}
<script type="text/javascript">
	var nacistDalsi_p = 1;
	var nacistDalsi_cekajici = false;
	function nacistDalsi(){
		if(nacistDalsi_cekajici) {
			return;
		}

		nacistDalsi_cekajici = true;
		$("#nacistDalsi_tlacitko").append('<i class="fas fa-spinner fa-spin"></i>');
		{* 
		Pomocí ajaxu se vytváří Http get požadavek na backend část (PHP skript)
		*}
		$.ajax({
			url: window.location.href,
			type: "get",
			data: {
				case: "getPage",
				p: nacistDalsi_p
			},
			success: function(ret) {
				if($(ret).find("td").length == 0) {
					$("#nacistDalsi_tlacitko").remove();
					return;
				}
				$("#nacistDalsi_cil").append(ret);
				nacistDalsi_cekajici = false;
				$("#nacistDalsi_tlacitko i").remove();
				nacistDalsi_p++;

				$(window).scroll();
			}
		});
	}
	
	{* 
	Kontroluji, jestli se na stránce scrollovalo až úplně na konec stránky, kde se nachází tlačítko pro načtení dalších řádků
	*}
	$(window).scroll(function(){
		if($("#nacistDalsi_tlacitko").length==0) {
			return;
		}
		if($(window).scrollTop()+$(window).height() > $("#nacistDalsi_tlacitko").offset().top) {
			$("#nacistDalsi_tlacitko").click();
		}
	});
	$(window).scroll();
</script>
