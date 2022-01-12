{* jiz existoval pred revizi, pouze revizovan a trochu vylepsen *}

<div class="msgInfo">
	Množství zrušených zpráv je {$sizeOfCanceled}
</div>
<form action="" method="get" style="padding: 20px; margin-bottom: 10px; background: #eee; text-align: center;">
	<input type="text" name="q" value="{$_GET.q|htmlspecialchars}" placeholder="Vyhledat..." style="height: 30px; vertical-align: middle; padding: 0 8px; border: 1px solid #ccc; background: #fff;">
	<button type="submit" style="height: 32px; vertical-align: middle;">Hledat</button>
</form>

<table class="adminTable">
	<thead>
		{include file="list_head.tpl"}
	</thead>
	<tbody id="loadMore_target">
		{foreach $list as $item}
			{include file="list_item.tpl" type='trash'}
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