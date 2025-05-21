{strip}
	{if count($libraryLocations) > 1 && !isset($useStaticLocation)}}
		<form role="form">
			<div class="form-group">
				<label for="selectLibraryHours">{translate text="Select a Location" isPublicFacing=true}</label>
				<select name="selectLibraryHours" id="selectLibraryHours"
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
			 {if empty($smarty.foreach.locationLoop.first)}style="display:none"{/if}>
			<div style="width: 100%; display: inline-flex; flex-direction: row; justify-content: space-around; flex-wrap: wrap; gap: 2rem">
				{if !empty($curLocation.image)}
					<div style="flex-grow: 1; max-width: 300px">
						<img src="{$curLocation.image}" alt="{$curLocation.name}" class="img-responsive"/>
					</div>
				{/if}
				<div style="flex-grow: 1">
					<h2 style="margin-top:0; padding-top: 0">{$curLocation.name}</h2>
					<address style="text-wrap: wrap; word-break: break-all; word-wrap: break-word;">
						{if !empty($curLocation.address)}
							{$curLocation.address}<br/>
						{/if}
						{if !empty($curLocation.phone)}
							<span>{translate text="Phone" isPublicFacing=true}:</span>&nbsp;<a href="tel:{$curLocation.phone}">{$curLocation.phone}</a><br/>
						{/if}
						{if !empty($curLocation.secondaryPhoneNumber)}
							<span>{translate text="Secondary Phone" isPublicFacing=true}:</span>&nbsp;<a href="tel:{$curLocation.secondaryPhoneNumber}">{$curLocation.secondaryPhoneNumber}</a><br/>
						{/if}
						{if !empty($curLocation.tty)}
							<span>{translate text="TTY" isPublicFacing=true}:</span>&nbsp;<a href="tel:{$curLocation.tty}">{$curLocation.tty}</a><br/>
						{/if}
						{if !empty($curLocation.email)}
							<span>{translate text="Email" isPublicFacing=true}:</span>&nbsp;<a href="mailto:{$curLocation.email}">{$curLocation.email}</a>
						{/if}
					</address>
					{if !empty($curLocation.hoursMessage)}
						<span class="label label-info" style="font-size: revert; white-space: normal">{$curLocation.hoursMessage}</span>
					{/if}
				</div>
			</div>
			{if !empty($curLocation.map_link) || !empty($curLocation.phone) || !empty($curLocation.email) || !empty($curLocation.homeLink)}
			<div class="row" style="padding-top: 1em">
				<div class="col-xs-12">
					<div style="width: 100%; display: inline-flex; flex-direction: row; justify-content: space-around; flex-wrap: wrap; gap: 1rem">
						{if !empty($curLocation.map_link)}
							<a class="btn btn-default btn-lg" style="flex-grow: 1" href="{$curLocation.map_link}" role="button"><i class="fas fa-directions" role="presentation"></i> {translate text="Visit Library" isPublicFacing=true}</a>
						{/if}
						{if !empty($curLocation.phone)}
							<a class="btn btn-default btn-lg" style="flex-grow: 1" href="tel:{$curLocation.phone}" role="button"><i class="fas fa-phone" role="presentation"></i> {translate text="Call Library" isPublicFacing=true}</a>
						{/if}
						{if !empty($curLocation.email)}
							<a class="btn btn-default btn-lg" style="flex-grow: 1" href="mailto:{$curLocation.email}" role="button"><i class="fas fa-envelope" role="presentation"></i> {translate text="Email Library" isPublicFacing=true}</a>
						{/if}
						{if !empty($curLocation.homeLink)}
							<a class="btn btn-default btn-lg" style="flex-grow: 1" href="{$curLocation.homeLink}" role="button" target="_blank" aria-label="{translate text="Visit Website" isPublicFacing=true inAttribute=true} ({translate text="opens in a new window" isPublicFacing=true inAttribute=true})"><i class="fas fa-external-link-alt" role="presentation"></i> {translate text="Visit Website" isPublicFacing=true}</a>
						{/if}
					</div>
				</div>
			</div>
			{/if}

			<div class="row" style="width: 100%; display: inline-flex; flex-direction: row; justify-content: stretch; flex-wrap: wrap; gap: 1rem">
			{if !empty($curLocation.hasValidHours)}
				<div style="flex-grow: 1; flex-basis: 300px; flex-shrink: 0; justify-self: stretch">
				<h3>{translate text="Hours" isPublicFacing=true}</h3>
				{assign var='lastDay' value="-1"}
				{foreach from=$curLocation.hours item=curHours}
					<div class="row">
						<div class="col-tn-5 result-label">
							{if $lastDay != $curHours->day}
								{if $curHours->day == 0}
									<span>{translate text="Sunday" isPublicFacing=true}</span>
								{elseif $curHours->day == 1}
									<span>{translate text="Monday" isPublicFacing=true}</span>
								{elseif $curHours->day == 2}
									<span>{translate text="Tuesday" isPublicFacing=true}</span>
								{elseif $curHours->day == 3}
									<span>{translate text="Wednesday" isPublicFacing=true}</span>
								{elseif $curHours->day == 4}
									<span>{translate text="Thursday" isPublicFacing=true}</span>
								{elseif $curHours->day == 5}
									<span>{translate text="Friday" isPublicFacing=true}</span>
								{elseif $curHours->day == 6}
									<span>{translate text="Saturday" isPublicFacing=true}</span>
								{/if}
							{/if}
							{assign var='lastDay' value=$curHours->day}
						</div>
						<div class="col-tn-7">
							{if $curHours->closed}
								<p class="text-right" style="margin: 0">{translate text="Closed" isPublicFacing=true}</p>
							{else}
								<p class="text-right" style="margin: 0">{$curHours->open} - {$curHours->close}</p>
							{/if}
						</div>
						<div class="col-tn-12">
							<span><em>{translate text=$curHours->notes isPublicFacing=true isAdminEnteredData=true}</em></span>
						</div>
					</div>
				{/foreach}
				</div>
			{/if}
				{if !empty($curLocation.latitude) && $curLocation.latitude !== 0}
				<div style="flex-grow: 1; flex-basis: 300px; flex-shrink: 0; justify-self: stretch">
					<iframe
							width="100%"
							height="250"
							style="border:0; padding-top:2em"
							allowfullscreen=""
							loading="lazy"
							referrerpolicy="no-referrer-when-downgrade"
							src="https://maps.google.com/?ie=UTF8&t=m&ll={$curLocation.latitude},{$curLocation.longitude}&spn=0.003381,0.017231&z=16&output=embed">
					</iframe>
				</div>
				{/if}
			</div>
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