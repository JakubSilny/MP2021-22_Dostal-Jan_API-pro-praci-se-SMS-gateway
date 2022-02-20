{* 
Šablona používaná jako definování obecné struktury řádků v tabulce (thead) při výpisu frontendů (šablon) určitých stránek (sent, queue, gaveup, trash), používá Smarty 
*}

<tr>
	<td class="num">{$polozka.id}</td>
	<td>{$ucty[$polozka.account]}</td>
	<td class="center">{$polozka.created}</td>
	<td class="center">{$polozka.sent}</td>
	<td>{$polozka.to|htmlspecialchars}</td>
	<td>{$polozka.body|htmlspecialchars}</td>
	<td class="tools center">
		{* Představuje událost, na kterou reaguje backend daných stránek (PHP skript) *}
		{if $polozka.errCount>0}
			<button class="buton" href="#" onclick="event.preventDefault(); $.colorbox({ iframe:true, innerWidth:600, innerHeight:500, href:'{$baseUrl}?case=errs&id={$polozka.id}' });" title="Zobrazit chyby"><i class="icon-Actions-13"></i> {$polozka.errCount}</button>
		{else}
			{$polozka.errCount}
		{/if}
	</td>
	<td class="tools">
		{* Představuje události, na které reaguje backend daných stránek (PHP skript) *}
		{if $polozka.status=='PENDING'}
		<button type="button" href="?case=cancel&amp;id={$polozka.id}" title="Zrušit odeslání a odebrat z fronty" onclick="if(confirm('Opravdu?')){ admin.ajaxCall($(this).attr('href')); }"><i class="fas fa-trash-alt"></i> zrušit</button>
		{/if}
		{if $polozka.status=='ERROR' || $polozka.status=='CANCELED'}
		<button type="button" href="?case=repeat&amp;id={$polozka.id}" title="Znovu se pokusit o odeslání" onclick="if(confirm('Opravdu?')){ admin.ajaxCall($(this).attr('href')); }"><i class="fas fa-redo-alt"></i> opakovat</button>
		{/if}
	</td>
</tr>
