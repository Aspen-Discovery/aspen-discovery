{strip}
<div class="calendar-page">
{if !empty($headerImage) && !empty($loggedIn) && in_array('Print Calendars with Header Images', $userPermissions)}
	<div class="calendar-header-image">
		<img src="{$headerImage}" {if !empty($headerAlt)}alt="{translate text=$headerAlt inAttribute=true isPublicFacing=true}" title="{translate text=$headerAlt inAttribute=true isPublicFacing=true}"{/if} id="calendar-header">
	</div>
{/if}
<h1 class="calendar-event-h1">{translate text='Events Calendar' isPublicFacing=true}</h1>
	<div class="calendar {if $useWeek}week-view{/if}">
		<div class="row calendar-nav">
			<div class="calendar-nav-cell col-tn-2 col-sm-1 align"><a class="btn btn-default" href="{$prevLink}" style="position:absolute;left: 0;"><i class="fas fa-caret-left" role="presentation"></i> {translate text="Previous" isPublicFacing=true}</a></div>
			<div class="calendar-nav-cell col-tn-8 col-sm-10 text-center calendar-current-month">{$calendarMonth}</div>
			{if $useWeek}
				<div class="calendar-nav-cell col-tn-2 col-sm-1"><a class="btn btn-default" href="{$monthLink}" style="position:absolute;right: 0">{translate text="Show Month" isPublicFacing=true} </a></div>
			{else}
				<div class="calendar-nav-cell col-tn-2 col-sm-1"><a class="btn btn-default" href="{$weekLink}" style="position:absolute;right: 0">{translate text="Show Week" isPublicFacing=true} </a></div>
			{/if}
			<div class="calendar-nav-cell col-tn-2 col-sm-1"><a class="btn btn-default" href="{$nextLink}" style="position:absolute;right: 0">{translate text="Next" isPublicFacing=true} <i class="fas fa-caret-right"></i></a></div>
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
									<div class="calendar-event-time">
										{$event.formattedTime}
									</div>
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
	</div>
</div>
{/strip}
