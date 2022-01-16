{* Kód se moc vymýšlet nemusel, některé věci se přebraly z revize sms gateway a z existujících endpointů lokálního API v rámci CLIQUO pro CRUD operace pro práci s databázi  *}
<div class="toolbar">
	<button type="button" onclick="admin.cbox('/ajax/register/sms_account/insert', 'Přidat');">
		<i class="fas fa-plus"></i> Přidat
	</button>
</div>
<div class="msgInfo">
	Počet uživatelů je {$countOfUsers}
</div>

<table class="adminTable">
	<thead>
		{include file="accounts_head.tpl"}
	</thead>
	<tbody id="loadMore_target"	>
		{foreach $accounts as $item}
			{include file="accounts_item.tpl"}
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
