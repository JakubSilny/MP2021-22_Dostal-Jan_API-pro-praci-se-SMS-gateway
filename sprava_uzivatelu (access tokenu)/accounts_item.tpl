<tr>
	<td><code>{$item.uuid}</code></td>
	<td>{$item.label}</td>
	<td class="center {if $item.active=='Y'}good{else}bad{/if}">{$item.active}</td>
	<td class="num">{$item.numberOfSentSmsTotally}</td>
	<td class="num">{$item.numberOfSentSmsLastMonth}</td>
	<td class="tools">
		<button type="button" onclick="admin.cbox('/ajax/register/sms_account/update?uuid={$item.uuid}', 'Upravit');">
			<i class="fas fa-pencil-alt"></i>
		</button>
	</td>
</tr>