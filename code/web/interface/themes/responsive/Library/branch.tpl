{strip}
	<h1 class="notranslate">
		{$location->displayName}
	</h1>

	<div class="row">
		<div class="col-xs-12 col-sm-3">
			<dl>
				{if !empty($locationInfo.address)}
					<dt>{translate text="Address" isPublicFacing=true}</dt>
					<dd>
						<address>{$locationInfo.address}</address>
					</dd>
				{/if}
				{if !empty($locationInfo.phone)}
					<dt>{translate text="Phone" isPublicFacing=true}</dt>
					<dd><a href="tel:{$locationInfo.phone}">{$locationInfo.phone}</a></dd>
				{/if}
			</dl>
		</div>
		{if !empty($locationInfo.address)}
			<div class="col-xs-12 col-sm-9">
				<a href="{$locationInfo.map_link}"><img src="{$locationInfo.map_image}" alt="{translate text="Map" isPublicFacing=true}"></a>
				<br><a href="{$locationInfo.map_link}">{translate text=Directions isPublicFacing=true}</a>
			</div>
		{/if}
	</div>
	{if !empty($locationInfo.hasValidHours)}
		<h4>{translate text="Hours" isPublicFacing=true}</h4>
		{foreach from=$locationInfo.hours item=curHours}
			<div class="row">
				<div class="col-xs-12 col-sm-4 result-label">
					{if $curHours->day == 0}
						{translate text="Sunday" isPublicFacing=true}
					{elseif $curHours->day == 1}
						{translate text="Monday" isPublicFacing=true}
					{elseif $curHours->day == 2}
						{translate text="Tuesday" isPublicFacing=true}
					{elseif $curHours->day == 3}
						{translate text="Wednesday" isPublicFacing=true}
					{elseif $curHours->day == 4}
						{translate text="Thursday" isPublicFacing=true}
					{elseif $curHours->day == 5}
						{translate text="Friday" isPublicFacing=true}
					{elseif $curHours->day == 6}
						{translate text="Saturday" isPublicFacing=true}
					{/if}
				</div>
				<div class="col-xs-12 col-sm-8 text-left">
					{if $curHours->closed}
						{translate text="Closed" isPublicFacing=true}
					{else}
						{$curHours->open} - {$curHours->close}
					{/if}
				</div>
			</div>
		{/foreach}
	{/if}
{/strip}