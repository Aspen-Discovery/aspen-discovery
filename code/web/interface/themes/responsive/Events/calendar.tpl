{strip}
<div class="calendar-page">
{if !empty($headerImage) && !empty($loggedIn) && in_array('Print Calendars with Header Images and Footer', $userPermissions)}
	<div class="calendar-header-image">
		<img src="{$headerImage}" {if !empty($headerAlt)}alt="{translate text=$headerAlt inAttribute=true isPublicFacing=true}" title="{translate text=$headerAlt inAttribute=true isPublicFacing=true}"{/if} id="calendar-header">
	</div>
{/if}
<h1 class="calendar-event-h1">{translate text=($calendarTitle) isPublicFacing=true}</h1>
	<div class="row" style="display: flex; align-items: center; flex-wrap: wrap;">
		<div class="col-xs-12 col-sm-6 calendar-nav-cell">
			<a class="btn btn-default" href="" onclick='return AspenDiscovery.Events.getPrintListOptions({if !empty($weekNumber)}{$weekNumber}{else}""{/if}, {if !empty($monthNumber)}{$monthNumber}{else}""{/if}, {$yearNumber})'>{translate text="Print Options" isPublicFacing=true}</a>
		</div>
		<div class="col-xs-12 col-sm-6 text-right calendar-nav-cell calendar-location-filter">
			<form method="get" id="locationFilter" style="display: inline-block; margin: 0;">
				<label for="location" style="display: inline-block; margin-right: 5px; margin-bottom: 0;">{translate text="Filter by location" isPublicFacing=true}</label>
				<select name="location" id="location" class="form-control" onchange="document.getElementById('locationFilter').submit()" style="display: inline-block; width: auto;">
					<option value="all"{if $selectedLocation == 'all'} selected{/if}>{translate text="All Locations" isPublicFacing=true}</option>
					{foreach from=$locations key=code item=name}
						<option value="{$code}"{if $selectedLocation == $code} selected{/if}>{$name}</option>
					{/foreach}
				</select>
				<input type="hidden" name="month" value="{$monthNumber}">
				<input type="hidden" name="year" value="{$yearNumber}">
				{if $useWeek}<input type="hidden" name="week" value="{$weekNumber}">{/if}
			</form>
		</div>
	</div>
	<div class="calendar {if $useWeek}week-view{/if}">
		<div class="row calendar-nav" id="fullScreenCalendar">
			<div class="calendar-nav-cell col-tn-2 col-sm-1 align"><a class="btn btn-default" href="{$prevLink}" style="position:absolute;left: 0;"><i class="fas fa-caret-left {if $isRTL}fa-flip-horizontal{/if}" role="presentation"></i> {translate text="Previous" isPublicFacing=true}</a></div>
			<div class="calendar-nav-cell col-tn-8 col-sm-10 text-center calendar-current-month">{$calendarMonth}</div>
			{if $useWeek}
				<div class="calendar-nav-cell col-tn-2 col-sm-1"><a class="btn btn-default" href="{$monthLink}" style="position:absolute;right: 0">{translate text="Show Month" isPublicFacing=true} </a></div>
			{else}
				<div class="calendar-nav-cell col-tn-2 col-sm-1"><a class="btn btn-default" href="{$weekLink}" style="position:absolute;right: 0">{translate text="Show Week" isPublicFacing=true} </a></div>
			{/if}
			<div class="calendar-nav-cell col-tn-2 col-sm-1"><a class="btn btn-default" href="{$nextLink}" style="position:absolute;right: 0">{translate text="Next" isPublicFacing=true} <i class="fas fa-caret-right {if $isRTL}fa-flip-horizontal{/if}"></i></a></div>
		</div>
		<div id="smallScreenCalendar">
			<div class="row calendar-nav">
				<div class="calendar-nav-cell col-tn-12 text-center calendar-current-month">{$calendarMonth}</div>
			</div>
			<div class="row calendar-nav">
				<div class="calendar-nav-cell col-tn-4"><a class="btn btn-default" href="{$prevLink}"><i class="fas fa-caret-left {if $isRTL}fa-flip-horizontal{/if}" role="presentation"></i> {translate text="Previous" isPublicFacing=true}</a></div>
				{if $useWeek}
					<div class="calendar-nav-cell col-tn-4"><a class="btn btn-default" href="{$monthLink}">{translate text="Show Month" isPublicFacing=true} </a></div>
				{else}
					<div class="calendar-nav-cell col-tn-4"><a class="btn btn-default" href="{$weekLink}">{translate text="Show Week" isPublicFacing=true} </a></div>
				{/if}
				<div class="calendar-nav-cell col-tn-4"><a class="btn btn-default" href="{$nextLink}">{translate text="Next" isPublicFacing=true} <i class="fas fa-caret-right {if $isRTL}fa-flip-horizontal{/if}"></i></a></div>
			</div>
		</div>

		<div class="calendar-header">
			<div class="calendar-header-cell">
				{translate text=Sunday isPublicFacing=true}
			</div>
			<div class="calendar-header-cell">
				{translate text=Monday isPublicFacing=true}
			</div>
			<div class="calendar-header-cell">
				{translate text=Tuesday isPublicFacing=true}
			</div>
			<div class="calendar-header-cell">
				{translate text=Wednesday isPublicFacing=true}
			</div>
			<div class="calendar-header-cell">
				{translate text=Thursday isPublicFacing=true}
			</div>
			<div class="calendar-header-cell">
				{translate text=Friday isPublicFacing=true}
			</div>
			<div class="calendar-header-cell">
				{translate text=Saturday isPublicFacing=true}
			</div>
		</div>
		{foreach from=$weeks item=week}
			<div class="calendar-row">
				{foreach from=$week.days item=day}
					<div class="calendar-day-cell {if empty($day.day)}hidden-xs{/if} {if empty($day.events)}hide-for-agenda-view{/if}">
						<div class="calendar-day-date">
							<span class="visible-xs">{$day.fullDate}</span><span class="hidden-xs">{$day.day}</span>
						</div>
						<div class="calendar-events {if count($day.events) < 5}wrap-title{/if}">
							{foreach from=$day.events item=event}
								<div class="calendar-event" data-event_id="{$event.id}">
									<div class="calendar-event-title">
										<a href="{$event.link}" target="_blank" aria-label="{translate text=$event.title isPublicFacing=true inAttribute=true} ({translate text="opens in a new window" isPublicFacing=true inAttribute=true})">{$event.title}</a>
									</div>
									<div class="calendar-event-location">
										{$event.location}
									</div>
									{if !$event.hiddenTimestamps}
										<div class="calendar-event-time {if $printEndTime}show-end-time{else}can-hide-end-time{/if}">
											{$event.formattedTime}
										</div>
									{/if}
									{if !empty($event.eventFields)}
										{foreach from=$event.eventFields key=eventFieldName item=eventField}
											{if $eventFieldName == 'description'}
												<div class="calendar-event-field calendar-event-description {if $eventField.settings->displayedOnline}display-online{else}hide-online{/if} {if $eventField.settings->printedCalendar}display-print-calendar{else}hide-print-calendar{/if} {if $eventField.settings->printedAgenda}display-print-agenda{else}hide-print-agenda{/if}">
													{$eventField.value}
												</div>
											{else}
												<div class="calendar-event-field {if $eventField.settings->displayedOnline}display-online{else}hide-online{/if} {if $eventField.settings->printedCalendar}display-print-calendar{else}hide-print-calendar{/if} {if $eventField.settings->printedAgenda}display-print-agenda{else}hide-print-agenda{/if}" id="calendar-event-{$eventFieldName}">
													{foreach from=$eventField.value item=value}
														{str_replace(',',', ',$value)}&nbsp;
													{/foreach}
												</div>
											{/if}
										{/foreach}
									{/if}
									{if !empty($event.isCancelled)}
										<div class="label label-danger calendar-event-state">
											{translate text="Cancelled" isPublicFacing=true}
										</div>
									{/if}
								</div>
							{/foreach}
						</div>
					</div>
				{/foreach}
			</div>
		{/foreach}

		{if !empty($footer) && !empty($loggedIn) && in_array('Print Calendars with Header Images and Footer', $userPermissions)}
			<div class="calendar-footer">
				{$footer}
			</div>
		{/if}
	</div>
</div>
{/strip}
