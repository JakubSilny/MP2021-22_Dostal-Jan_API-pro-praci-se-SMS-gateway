<tr>
	<td class="center">{$polozka.date}</td>
	<td class="num">{$polozka.log.queue}</td>
	<td class="num {if count($polozka.log.sent)>0}good{/if}">{count($polozka.log.sent)}</td>
	<td class="num {if count($polozka.log.err)>0}bad{/if}">{count($polozka.log.err)}</td>
	<td class="num {if $polozka.log.quotaState>2000}bad{else if $polozka.log.quotaState > 1500}yellow{/if}">{$polozka.log.quotaState}</td>
</tr>