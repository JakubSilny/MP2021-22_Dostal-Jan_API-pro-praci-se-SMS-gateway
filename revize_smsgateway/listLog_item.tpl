<tr>
	<td class="center">{$item.date}</td>
	<td class="num">{$item.log.queue}</td>
	<td class="num {if count($item.log.sent)>0}good{/if}">{count($item.log.sent)}</td>
	<td class="num {if count($item.log.err)>0}bad{/if}">{count($item.log.err)}</td>
	<td class="num {if $item.log.quotaState>2000}bad{else if $item.log.quotaState > 1500}yellow{/if}">{$item.log.quotaState}</td>
</tr>