{* // jiz existoval pred revizi, pouze revizovan a trochu vylepsen*}

{if time()-strtotime($lastCronDate) > 600}
<div class="msgError">
	CRON se naposledy spustil: {$lastCronDate|date:"diff"}
</div>
{/if}
<table class="adminTable">
	<thead>
		{include file="listLog_head.tpl"}
	</thead>
	<tbody id="loadMore_target">
		{foreach $listLog as $item}
			{include file="listLog_item.tpl"}
		{/foreach}
	</tbody>
</table>

<button type="button" id="loadMore_btn" onclick="loadMore();">Načíst další</button>

<script type="text/javascript">
	var loadMore_p = 1;
	var loadMore_pending = false;
	function loadMore(){
		if(loadMore_pending) {
			return;
		}

		loadMore_pending = true;
		$("#loadMore_btn").append('<i class="fas fa-spinner fa-spin"></i>');
		$.ajax({
			url: window.location.href,
			type: "get",
			data: {
				case: "getPage",
				p: loadMore_p
			},
			success: function(ret) {
				if($(ret).find("td").length == 0) {
					$("#loadMore_btn").remove();
					return;
				}
				$("#loadMore_target").append(ret);
				loadMore_pending = false;
				$("#loadMore_btn i").remove();
				loadMore_p++;

				$(window).scroll();
			}
		});
	}

	$(window).scroll(function(){
		if($("#loadMore_btn").length==0) {
			return;
		}
		if($(window).scrollTop()+$(window).height() > $("#loadMore_btn").offset().top) {
			$("#loadMore_btn").click();
		}
	});
	$(window).scroll();
</script>