{if time()-strtotime($posledniSpusteniCronu) > 600}
<div class="msgError">
	CRON se naposledy spustil: {$posledniSpusteniCronu|date:"diff"}
</div>
{/if}
<table class="adminTable">
	<thead>
		{include file="listLog_head.tpl"}
	</thead>
	<tbody id="nacistDalsi_cil">
		{foreach $cronLog as $polozka}
			{include file="listLog_item.tpl"}
		{/foreach}
	</tbody>
</table>

<button type="button" id="nacistDalsi_tlacitko" onclick="nacistDalsi();">Načíst další</button>

<script type="text/javascript">
	var nacistDalsi_p = 1;
	var nacistDalsi_cekajici = false;
	function nacistDalsi(){
		if(nacistDalsi_cekajici) {
			return;
		}

		nacistDalsi_cekajici = true;
		$("#nacistDalsi_tlacitko").append('<i class="fas fa-spinner fa-spin"></i>');
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