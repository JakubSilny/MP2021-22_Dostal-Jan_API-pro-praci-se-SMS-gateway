{if !empty($client)}
	<p>Klient: {$client.label}</p>
	<button onclick="location.href='/smsgateway/stats'" type="button">Zpět</button>
{/if}

{if !empty($sentSmsPerAccounts)}
	<h2>Uživatelé</h2>
	<table class="adminTable" style="width: auto;">
		<thead>
			<tr>
				<th>Zákazník</th>
				<th class="num">Počet</th>
			</tr>
		</thead>
		<tbody>
			{foreach $sentSmsPerAccounts as $item}
				<tr>
					<td><a href="?uuid={$item.uuid}">{$item.label}</a></td>
					<td class="num">{$item.numberOfSentSms}</td>
				</tr>
			{/foreach}
		</tbody>
	</table>
{/if}

<h2>Dny</h2>

<table class="adminTable" style="width: auto;">
	<thead>
		<tr>
			<th class="center">Datum</th>
			<th class="num">Počet</th>
		</tr>
	</thead>
	<tbody>
		{foreach $sentSmsPerDays as $item}
			<tr>
				<td class="center">{$item.dateOfSending}</td>
				<td class="num">{$item.numberOfSentSms}</td>
			</tr>
		{/foreach}
	</tbody>
</table>
