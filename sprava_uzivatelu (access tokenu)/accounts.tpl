{* 
Šablona používaná jako frontend pro stránku, která vypisuje uživatelské účty z databáze a umožnuje je spravovat
*}
<div class="toolbar">
	{* Pomocí ajaxu zavolá na doméně firmy lokální univerzální API (není myšleno API pro práci s SMS), které provede požadovanou akci *}
	<button type="button" onclick="admin.cbox('/ajax/register/sms_account/insert', 'Přidat');">
		<i class="fas fa-plus"></i> Přidat
	</button>
</div>
<div class="msgInfo">
	Počet uživatelů je {$pocetUzivatelu}
</div> 

<table class="adminTable">
	{* 
	Používají se šablony (jiné soubory)
	*}
	<thead>
		{include file="accounts_head.tpl"}
	</thead>
	<tbody id="nacistDalsi_cil">
		{foreach $ucty as $polozka}
			{include file="accounts_item.tpl"}
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
