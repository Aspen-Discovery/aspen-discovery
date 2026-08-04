{*Title Div*}
<div class="col-xs-12">
	<div class="row">
		<div class="col-sm-12">
			<h1>{$recordDriver->getTitle()}</h1>
		</div>
	</div>
</div>
{*Content Div*}
<div class="row">
	{*Left Panel Content*}
	<div class="col-tn-12 col-xs-12 col-sm-4 col-md-3 col-lg-3">
		{if !empty($recordDriver->getEventCoverUrl())}
			<div class="panel">
				<div class="panel-body" style="display:flex; justify-content:center">
					<a href="{$recordDriver->getLinkUrl()}"><img class="img-responsive img-thumbnail {$coverStyle}" src="{$recordDriver->getEventCoverUrl()}" alt="{$recordDriver->getTitle()|escape}" style="max-height: 280px; width: auto"></a>
				</div>
			</div>
		{/if}
		{if !empty($recordDriver->getAudiences())}
			<div class="panel active">
				<div class="panel-heading">
					{translate text="Audience" isPublicFacing=true}
				</div>
				<div class="panel-body">
					{foreach from=$recordDriver->getAudiences() item=audience}
						<div class="col-xs-12">
							<a href='/Events/Results?filter[]=age_group_facet%3A"{$audience|escape:'url'}"'>{$audience}</a>
						</div>
					{/foreach}
				</div>
			</div>
		{/if}
		{if !empty($recordDriver->getProgramTypes())}
			<div class="panel active">
				<div class="panel-heading">
					{translate text="Program Type" isPublicFacing=true}
				</div>
				<div class="panel-body">
					{foreach from=$recordDriver->getProgramTypes() item=type}
						<div class="col-xs-12">
							<a href='/Events/Results?filter[]=program_type_facet%3A"{$type|escape:'url'}"'>{$type}</a>
						</div>
					{/foreach}
				</div>
			</div>
		{/if}
		{if !empty($recordDriver->getOtherEventsInSeries())}
			<div class="panel active">
				<div class="panel-heading">
					{translate text="Other Dates in this Series" isPublicFacing=true}
				</div>
				<div class="panel-body">
					{foreach from=$recordDriver->getOtherEventsInSeries() item=event key=key name="eventDateLoop"}
						{if $smarty.foreach.eventDateLoop.iteration == 6}
							<div class="col-xs-12">
								<a href="#" id="moreEventDates" onclick="AspenDiscovery.Events.moreEventDates(); return false;">{translate text='more' isPublicFacing=true} ...</a>
							</div>
							<div class="narrowGroupHidden" id="narrowGroupHidden_eventDates" style="display:none">
						{/if}
						<div class="col-xs-12">
							<a href='/AspenEvents/{$key|escape:'url'}/Event'>{$event|format_date_locale:'medium'}</a>
						</div>
					{/foreach}
					{if $smarty.foreach.eventDateLoop.total > 5}
						<div class="col-xs-12">
							<a href="#" onclick="AspenDiscovery.Events.lessEventDates(); return false;">{translate text='less' isPublicFacing=true} ...</a>
						</div>
						</div>{* closes narrowGroupHidden div *}
					{/if}
				</div>
			</div>
		{/if}
	</div>

	{*Content Right of Panel*}
	<div class="col-tn-12 col-xs-12 col-sm-8 col-md-9 col-lg-9">
		{*Row for Information and Registration/Your Events Button*}
		<div class="row">
			<div class="col-xs-8">
				<ul>
					{if $recordDriver->isAllDayEvent()}
						<li>{translate text="Date: " isPublicFacing=true}{$recordDriver->getStartDate()|format_date_locale:'full'}</li>
						<li>{translate text="Time: All Day Event" isPublicFacing=true}</li>
					{elseif $recordDriver->isMultiDayEvent()}
						<li>{translate text="Start Date: " isPublicFacing=true}{$recordDriver->getStartDate()|format_datetime_locale:'long'}</li>
						<li>{translate text="End Date: " isPublicFacing=true}{$recordDriver->getEndDate()|format_datetime_locale:'long'}</li>
					{else}
						<li>{translate text="Date: " isPublicFacing=true}{$recordDriver->getStartDate()|format_date_locale:'full'}</li>
						{if !$recordDriver->hiddenTimestamps()}
							<li>{translate text="Time: " isPublicFacing=true}{$recordDriver->getStartDate()|format_time_range_locale:$recordDriver->getEndDate()}</li>
						{/if}
					{/if}
					<li>{translate text="Branch: " isPublicFacing=true}{$recordDriver->getBranch()}</li>
					{if !empty($recordDriver->getRoom())}
						<li>{translate text="Room: " isPublicFacing=true}{$recordDriver->getRoom()}</li>
					{/if}
					{if !empty($recordDriver->getEventTypeFields())}
						{$recordDriver->getEventTypeFields()}
					{/if}
					{if $recordDriver->getNumberOfSeats() !== null}
						<li>
							{translate text="Available Seats: " isPublicFacing=true}
							{if $recordDriver->isEventFull()}
								<span class="label label-danger">{translate text="Full" isPublicFacing=true}</span>
							{else}
								{$recordDriver->getAvailableSeats()} / {$recordDriver->getNumberOfSeats()}
							{/if}
						</li>
					{/if}
					{if $recordDriver->isWaitingListEnabled()}
						<li>
							{translate text="Waiting List: " isPublicFacing=true}
							{if $recordDriver->isWaitingListFull()}
								<span class="label label-danger">{translate text="Full" isPublicFacing=true}</span>
							{else}
								{$recordDriver->getDisplayWaitingListSeats()}
							{/if}
						</li>
					{/if}
					{if $private}
						<li>
							<span class="label label-default">{translate text="Private" isPublicFacing=true}</span>
						</li>
					{/if}
				</ul>
			</div>
			<div class="col-tn-4" style="display:flex; justify-content:center;">
				<div class="btn-group btn-group-vertical btn-block">
					{if $recordDriver->isRegistrationRequired()}
						{if $registrationAction == 'registered'}
							<a href="{$recordDriver->getExternalUrl(true)}" class="btn btn-sm btn-action btn-wrap" target="_blank" style="width:70%" aria-label="{translate text="You Are Registered" isPublicFacing=true inAttribute=true} ({translate text='opens in new window' isPublicFacing=true inAttribute=true})"><i class="fas fa-external-link-alt" role="presentation"></i> {translate text="You Are Registered" isPublicFacing=true}</a>
						{elseif $registrationAction == 'completeRegistration'}
							<span class="btn btn-sm btn-default btn-wrap disabled" style="width:70%">{translate text="Event Full" isPublicFacing=true}</span>
							<a class="btn btn-sm btn-action btn-register btn-wrap btn-warning" onclick="return AspenDiscovery.Account.regInfoModal(this, 'Events', '{$recordDriver->getUniqueID()|escape}', 'aspenEvents', '{$recordDriver->getExternalUrl()}');" style="width:70%">{translate text="Complete Your Registration" isPublicFacing=true}</a>
							<a class="btn btn-sm btn-action btn-register btn-danger" aria-label="{translate text="Join the waiting list"}" onclick="return AspenDiscovery.Account.leaveEventWaitingList('{$recordDriver->getUniqueID()|escape}');" style="width:70%">{translate text="Leave Waiting List" isPublicFacing=true}</a>
						{elseif $registrationAction == 'showPosition'}
							<span class="btn btn-sm btn-default btn-wrap disabled" style="width:70%">{translate text="Event Full" isPublicFacing=true}</span>
							<a href="{$recordDriver->getExternalUrl(true)}" class="btn btn-sm btn-action btn-wrap" aria-label="{translate text="You are number %1% on the waiting list" 1=$userWaitingListPosition isPublicFacing=true inAttribute=true}" style="width:70%">{translate text="You are number %1% on the waiting list" 1=$userWaitingListPosition isPublicFacing=true}</a>
							<a class="btn btn-sm btn-action btn-register btn-danger" aria-label="{translate text="Join the waiting list"}" onclick="return AspenDiscovery.Account.leaveEventWaitingList('{$recordDriver->getUniqueID()|escape}');" style="width:70%">{translate text="Leave Waiting List" isPublicFacing=true}</a>
						{elseif $registrationAction == 'joinWaitingList'}
							<span class="btn btn-sm btn-default btn-wrap disabled" style="width:70%">{translate text="Event Full" isPublicFacing=true}</span>
							<a class="btn btn-sm btn-action btn-register btn-wrap" aria-label="{translate text="Join the waiting list"}" onclick="return AspenDiscovery.Account.joinEventWaitingList('{$recordDriver->getUniqueID()|escape}');" style="width:70%">{translate text="Join Waiting List" isPublicFacing=true}</a>
						{elseif $registrationAction == 'eventFull'}
							<span class="btn btn-sm btn-default btn-wrap disabled" style="width:70%">{translate text="Event Full" isPublicFacing=true}</span>
						{elseif $registrationAction == 'registrationAvailable'}
							<a class="btn btn-sm btn-action btn-register btn-wrap" onclick="return AspenDiscovery.Account.regInfoModal(this, 'Events', '{$recordDriver->getUniqueID()|escape}', 'aspenEvents', '{$recordDriver->getExternalUrl()}');" style="width:70%">{translate text="Registration Information" isPublicFacing=true}</a>
						{/if}
					{/if}
					{if $recordDriver->inEvents()}
						<a href="/MyAccount/MyEvents?page=1&eventsFilter=upcoming" class="btn btn-sm btn-action btn-wrap" style="width:70%">{translate text="In Your Events" isPublicFacing=true}</a>
					{elseif empty($offline) || $enableEContentWhileOffline}
						<a onclick="return AspenDiscovery.Account.saveEvent(this, 'Events', '{$recordDriver->getUniqueID()|escape}', 'aspenEvents');" class="btn btn-sm btn-action btn-wrap addToYourEventsBtn" style="width:70%">{translate text="Add to Your Events" isPublicFacing=true}</a>
					{/if}
				</div>
			</div>
		</div>
		{*column for tool buttons & event description*}
		<div class="col-sm-9">
			<div class="btn-group btn-group-sm">
				{if $isStaff && $eventsInLists == 1 || $eventsInLists == 2}
					<button onclick="return AspenDiscovery.Account.showSaveToListForm(this, 'Events', '{$recordDriver->getUniqueID()|escape}');" class="btn btn-sm btn-tools addToListBtn">{translate text="Add to List" isPublicFacing=true}</button>
				{/if}
				{if $upcomingInstanceCount > 1}
					<div class="btn-group">
						<button data-toggle="dropdown" class="btn btn-sm btn-tools btn-default dropdown-toggle" aria-haspopup="true" aria-expanded="false" id="export_{$recordDriver->getUniqueID()|escape}">
							{translate text="Export" isPublicFacing=true}&nbsp;
							<span class="caret"></span>
						</button>
						<ul class="dropdown-menu" aria-labelledby="export_{$recordDriver->getUniqueID()|escape}">
							<li><a href="#" onclick="return AspenDiscovery.Events.iCalendarExport('{$recordDriver->getUniqueID()|escape}', 'event_aspenEvent', 0);">{translate text="Only this event" isPublicFacing="true"}</a></li>
							<li><a onclick="return AspenDiscovery.Events.iCalendarExport('{$recordDriver->getUniqueID()|escape}', 'event_aspenEvent', 1);" href="#">{translate text="All upcoming events in this series" isPublicFacing="true"}</a></li>
						</ul>
					</div>
				{else}
					<button class="btn btn-sm btn-tools btn-default" onclick="return AspenDiscovery.Events.iCalendarExport('{$recordDriver->getUniqueID()|escape}', 'event_aspenEvent', 0);">
						{translate text="Export" isPublicFacing=true}
					</button>
				{/if}
			</div>
			<div class="btn-group btn-group-sm">
				{include file="Events/share-tools.tpl" eventUrl=$recordDriver->getExternalUrl()}
			</div>
			{if $canStaffRegister}
				<div class="btn-group btn-group-sm">
					<button class="btn btn-sm btn-tools" onclick="return AspenDiscovery.Events.showStaffRegistrationModal({$eventInstanceId});">
						<i class="fas fa-user-plus" role="presentation"></i> {translate text="Staff Registration" isAdminFacing=true}
					</button>
					<a href="/Events/AttendanceManagement?eventInstanceId={$eventInstanceId}" class="btn btn-sm btn-tools">
						<i class="fas fa-users" role="presentation"></i> {translate text="Manage Event" isAdminFacing=true}
					</a>
				</div>
			{/if}
			<br>
			<br>
			{$recordDriver->getFullDescription()}
		</div>
	</div>
</div>
