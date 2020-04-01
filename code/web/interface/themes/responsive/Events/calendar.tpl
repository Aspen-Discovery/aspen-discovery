{strip}
<h1>{translate text=calendar_heading defaultText="Events Calendar for %1%" 1=$calendarMonth}</h1>

	<div class="calendar">
		<div class="row calendar-nav">
			<div class="calendar-nav-cell col-tn-1"><a class="btn btn-default" href="{$prevLink}">&laquo; Prev</a></div>
			<div class="calendar-nav-cell col-tn-10 text-center calendar-current-month">{$calendarMonth}</div>
			<div class="calendar-nav-cell col-tn-1"><a class="btn btn-default" href="{$nextLink}">Next &raquo;</a></div>
		</div>

		<div class="row calendar-header">
			<div class="col-sm-2 calendar-header-cell">
				{translate text=Sunday}
			</div>
			<div class="col-sm-2 calendar-header-cell">
	            {translate text=Monday}
			</div>
			<div class="col-sm-2 calendar-header-cell">
	            {translate text=Tuesday}
			</div>
			<div class="col-sm-2 calendar-header-cell">
	            {translate text=Wednesday}
			</div>
			<div class="col-sm-2 calendar-header-cell">
	            {translate text=Thursday}
			</div>
			<div class="col-sm-2 calendar-header-cell">
	            {translate text=Friday}
			</div>
			<div class="col-sm-2 calendar-header-cell">
	            {translate text=Saturday}
			</div>
		</div>
		{foreach from=$weeks item=week}
			<div class="row calendar-row">
				{foreach from=$week.days item=day}
					<div class="col-sm-2 calendar-day-cell">
						<div class="calendar-day-date">
							{$day.day}
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