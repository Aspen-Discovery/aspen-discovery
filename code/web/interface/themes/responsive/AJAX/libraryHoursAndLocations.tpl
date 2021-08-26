{strip}
	{if count($libraryLocations) > 1}
		<form role="form">
			<div class="form-group">
				<label for="selectLibrary">{translate text="Select a Location" isPublicFacing=true}</label>
				<select name="selectLibrary" id="selectLibrary"
						onchange="return AspenDiscovery.showLocationHoursAndMap();" class="form-control">
					{foreach from=$libraryLocations item=curLocation}
						<option value="{$curLocation.id}">{$curLocation.name}</option>
					{/foreach}
				</select>
			</div>
		</form>
	{/if}
	{foreach from=$libraryLocations item=curLocation name=locationLoop}
		<div class="locationInfo" id="locationAddress{$curLocation.id}"
			 {if !$smarty.foreach.locationLoop.first}style="display:none"{/if}>
			<div class="row">
				<div class="col-xs-12">
					<h2>{$curLocation.name}</h2>
				</div>
			</div>
			{if !empty($curLocation.address)}
				<div class="row">
					<div class="col-tn-12 col-xs-3 result-label">
						{translate text="Address" isPublicFacing=true}
					</div>
					<div class="col-tn-12 col-xs-9">
						<address>{$curLocation.address}
							{if !empty($curLocation.map_link)}
								<br/>
							<a href="{$curLocation.map_link}">{translate text="Directions" isPublicFacing=true}</a>{/if}
						</address>
					</div>
				</div>
			{/if}
			{if !empty($curLocation.phone)}
				<div class="row">
					<div class="col-tn-12 col-xs-3 result-label">
						{translate text="Phone" isPublicFacing=true}
					</div>
					<div class="col-tn-12 col-xs-9">
						<a href="tel:{$curLocation.phone}">{$curLocation.phone}</a>
					</div>
				</div>
			{/if}
			{if !empty($curLocation.tty)}
				<div class="row">
					<div class="col-tn-12 col-xs-3 result-label">
						{translate text="TTY" isPublicFacing=true}
					</div>
					<div class="col-tn-12 col-xs-9">
						<a href="tel:{$curLocation.tty}">{$curLocation.tty}</a>
					</div>
				</div>
			{/if}
			{if $curLocation.hasValidHours}
				<h3>{translate text="Hours" isPublicFacing=true}</h3>
				{assign var='lastDay' value="-1"}
				{foreach from=$curLocation.hours item=curHours}
					<div class="row">
						<div class="col-tn-4 col-md-2 result-label">
							{if $lastDay != $curHours->day}
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
							{/if}
							{assign var='lastDay' value=$curHours->day}
						</div>
						<div class="col-tn-8 col-md-4">
							{if $curHours->closed}
								{translate text="Closed" isPublicFacing=true}
							{else}
								{$curHours->open} - {$curHours->close}
							{/if}
						</div>
						<div class="col-tn-8 col-tn-offset-4 col-md-6 col-md-offset-0 ">
							<em>{translate text=$curHours->notes isPublicFacing=true isAdminEnteredData=true}</em>
						</div>
					</div>
				{/foreach}
			{/if}
			{if !empty($curLocation.description)}
				<h3>{translate text="Additional information" isPublicFacing=true}</h3>
				<div class="row">
					<div class="col-xs-12">
						{translate text=$curLocation.description isPublicFacing=true isAdminEnteredData=true}
					</div>
				</div>
			{/if}
		</div>
	{/foreach}
{/strip}