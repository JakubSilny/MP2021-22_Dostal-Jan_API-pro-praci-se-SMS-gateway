{* 
Šablona používaná jako frontend pro stránku, která zobrazuje souhrnné statistiky z databáze 
*}

{if !empty($uzivatel)}
	<p>Klient: {$uzivatel.label}</p>
	{* 
	Nastaví url zpátky na výchozí, backend patřičně reaguje
	*}
	<button onclick="location.href='/smsgateway/stats'" type="button">Zpět</button>
{/if}

{if !empty($odeslaneSmsDleUzivatelu)}
	<h2>Uživatelé</h2>
	<table class="adminTable" style="width: auto;">
		<thead>
			<tr>
				<th>Zákazník</th>
				<th class="num">Počet</th>
			</tr>
		</thead>
		<tbody>
			{foreach $odeslaneSmsDleUzivatelu as $polozka}
				<tr>
					{* 
					Rozšíří URL o nový query parametr, backend patřičně reaguje
					*}
					<td><a href="?uuid={$polozka.uuid}">{$polozka.label}</a></td>
					<td class="num">{$polozka.numberOfSentSms}</td>
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
		{foreach $odeslaneSmsDleDnu as $polozka}
			<tr>
				<td class="center">{$polozka.dateOfSending}</td>
				<td class="num">{$polozka.numberOfSentSms}</td>
			</tr>
		{/foreach}
	</tbody>
</table>
