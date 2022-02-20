<tr>
	<td><code>{$polozka.uuid}</code></td>
	<td>{$polozka.label}</td>
	<td class="center {if $polozka.active=='Y'}good{else}bad{/if}">{$polozka.active}</td>
	<td class="num">{$polozka.numberOfSentSmsTotally}</td>
	<td class="num">{$polozka.numberOfSentSmsLastMonth}</td>
	<td class="tools">
		<button type="button" onclick="admin.cbox('/ajax/register/sms_account/update?uuid={$polozka.uuid}', 'Upravit');">
			<i class="fas fa-pencil-alt"></i>
		</button>
	</td>
</tr>