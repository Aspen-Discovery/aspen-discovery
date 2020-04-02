{strip}
<h1>{translate text=calendar_heading defaultText="Events Calendar for %1%" 1=$calendarMonth}</h1>

	<div class="calendar">
		<div class="row calendar-nav">
			<div class="calendar-nav-cell col-tn-2 col-sm-1"><a class="btn btn-default" href="{$prevLink}">&laquo; Prev</a></div>
			<div class="calendar-nav-cell col-tn-8 col-sm-10 text-center calendar-current-month">{$calendarMonth}</div>
			<div class="calendar-nav-cell col-tn-2 col-sm-1"><a class="btn btn-default" href="{$nextLink}">Next &raquo;</a></div>
		</div>

		<div class="calendar-header">
			<div class="calendar-header-cell">
				{translate text=Sunday}
			</div>
			<div class="calendar-header-cell">
	            {translate text=Monday}
			</div>
			<div class="calendar-header-cell">
	            {translate text=Tuesday}
			</div>
			<div class="calendar-header-cell">
	            {translate text=Wednesday}
			</div>
			<div class="calendar-header-cell">
	            {translate text=Thursday}
			</div>
			<div class="calendar-header-cell">
	            {translate text=Friday}
			</div>
			<div class="calendar-header-cell">
	            {translate text=Saturday}
			</div>
		</div>
		{foreach from=$weeks item=week}
			<div class="calendar-row">
				{foreach from=$week.days item=day}
					<div class="calendar-day-cell {if empty($day.day)}hidden-xs{/if}">
						<div class="calendar-day-date">
							<span class="visible-xs">{$day.fullDate}</span><span class="hidden-xs">{$day.day}</span>
						</div>
						<div class="calendar-events">
							{foreach from=$day.events item=event}
								<div class="calendar-event" data-event_id="{$event.id}">
									<div class="calendar-event-title">
										<a href="{$event.link}">{$event.title}</a>
									</div>
									<div class="calendar-event-time">
										{$event.formattedTime}
									</div>
								</div>
							{/foreach}
						</div>
					</div>
				{/foreach}
			</div>
		{/foreach}
	</div>
{/strip}