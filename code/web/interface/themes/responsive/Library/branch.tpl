{strip}
	<h2 class="notranslate">
		{$location->displayName}
	</h2>

	<div class="row">
		<div class="col-xs-12 col-sm-3">
			<dl>
				{if $locationInfo.address}
					<dt>Address</dt>
					<dd>
						<address>{$locationInfo.address}</address>
					</dd>
				{/if}
				{if $locationInfo.phone}
					<dt>Phone</dt>
					<dd><a href="tel:{$locationInfo.phone}">{$locationInfo.phone}</a></dd>
				{/if}
			</dl>
		</div>
		{if $locationInfo.address}
			<div class="col-xs-12 col-sm-9">
				<a href="{$locationInfo.map_link}"><img src="{$locationInfo.map_image}" alt="Map"></a>
				<br><a href="{$locationInfo.map_link}">Directions</a>
			</div>
		{/if}
	</div>
	{if $locationInfo.hours}
		<h4>Hours</h4>
		{foreach from=$locationInfo.hours item=curHours}
			<div class="row">
				<div class="col-xs-12 col-sm-4 result-label">
					{if $curHours->day == 0}
						Sunday
					{elseif $curHours->day == 1}
						Monday
					{elseif $curHours->day == 2}
						Tuesday
					{elseif $curHours->day == 3}
						Wednesday
					{elseif $curHours->day == 4}
						Thursday
					{elseif $curHours->day == 5}
						Friday
					{elseif $curHours->day == 6}
						Saturday
					{/if}
				</div>
				<div class="col-xs-12 col-sm-8 text-left">
					{if $curHours->closed}
						Closed
					{else}
						{$curHours->open} - {$curHours->close}
					{/if}
				</div>
			</div>
		{/foreach}
	{/if}
{/strip}