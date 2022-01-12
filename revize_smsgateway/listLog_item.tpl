<tr>
	<td class="center">{$item.date}</td>
	<td class="num">{$item.log.queue}</td>
	<td class="num {if count($item.log.sent)>0}good{/if}">{count($item.log.sent)}</td>
	<td class="num {if count($item.log.err)>0}bad{/if}">{count($item.log.err)}</td>
</tr>