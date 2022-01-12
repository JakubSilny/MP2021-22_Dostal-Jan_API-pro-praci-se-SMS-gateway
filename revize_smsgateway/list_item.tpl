<tr>
	<td class="num">{$item.id}</td>
	<td>{$accounts[$item.account]}</td>
	<td class="center">{$item.created}</td>
	<td class="center">{$item.sent}</td>
	<td>{$item.to|htmlspecialchars}</td>
	<td>{$item.body|htmlspecialchars}</td>
	<td class="tools center">
		{if $item.errCount>0}
			<button class="buton" href="#" onclick="event.preventDefault(); $.colorbox({ iframe:true, innerWidth:600, innerHeight:500, href:'{$baseUrl}?case=errs&id={$item.id}' });" title="Zobrazit chyby"><i class="icon-Actions-13"></i> {$item.errCount}</button>
		{else}
			{$item.errCount}
		{/if}
	</td>
	<td class="tools">
		{if $item.status=='PENDING'}
		<button type="button" href="?case=cancel&amp;id={$item.id}" title="Zrušit odeslání a odebrat z fronty" onclick="if(confirm('Opravdu?')){ admin.ajaxCall($(this).attr('href')); }"><i class="fas fa-trash-alt"></i> zrušit</button>
		{/if}
		{if $item.status=='ERROR' || $item.status=='CANCELED'}
		<button type="button" href="?case=repeat&amp;id={$item.id}" title="Znovu se pokusit o odeslání" onclick="if(confirm('Opravdu?')){ admin.ajaxCall($(this).attr('href')); }"><i class="fas fa-redo-alt"></i> opakovat</button>
		{/if}
	</td>
</tr>