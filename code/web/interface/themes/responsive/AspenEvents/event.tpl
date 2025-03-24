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
					{foreach from=$recordDriver->getOtherEventsInSeries() item=event key=key}
						<div class="col-xs-12">
							<a href='/AspenEvents/{$key|escape:'url'}/Event'>{$event|date_format:"%x"}</a>
						</div>
					{/foreach}
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
						<li>{translate text="Date: " isPublicFacing=true}{$recordDriver->getStartDate()|date_format:"%A %B %e, %Y"}</li>
						<li>{translate text="Time: All Day Event" isPublicFacing=true}</li>
					{elseif $recordDriver->isMultiDayEvent()}
						<li>{translate text="Start Date: " isPublicFacing=true}{$recordDriver->getStartDate()|date_format:"%a %b %e, %Y %l:%M%p"}</li>
						<li>{translate text="End Date: " isPublicFacing=true}{$recordDriver->getEndDate()|date_format:"%a %b %e, %Y %l:%M%p"}</li>
					{else}
						<li>{translate text="Date: " isPublicFacing=true}{$recordDriver->getStartDate()|date_format:"%A %B %e, %Y"}</li>
						<li>{translate text="Time: " isPublicFacing=true}{$recordDriver->getStartDate()|date_format:"%l:%M %p"} to {$recordDriver->getEndDate()|date_format:"%l:%M %p"}</li>
					{/if}
					<li>{translate text="Branch: " isPublicFacing=true}{$recordDriver->getBranch()}</li>
					{if !empty($recordDriver->getRoom())}
						<li>{translate text="Room: " isPublicFacing=true}{$recordDriver->getRoom()}</li>
					{/if}
					{if !empty($recordDriver->getEventTypeFields())}
						{$recordDriver->getEventTypeFields()}
					{/if}
					{if $private}
						<li>
							<span class="label label-default">{translate text="Private" isPublicFacing=true}</span>
						</li>
					{/if}
				</ul>
			</div>
			<div class="col-tn-4" style="display:flex; justify-content:center;">
				{if $recordDriver->inEvents()}
					{if $recordDriver->isRegistrationRequired()}
						<div class="btn-group btn-group-vertical btn-block">
							{if $recordDriver->isRegisteredForEvent()}
								<a href="{$recordDriver->getExternalUrl()}" class="btn btn-sm btn-action btn-wrap" target="_blank" style="width:70%" aria-label="{translate text="You Are Registered" isPublicFacing=true inAttribute=true} ({translate text="opens in a new window" isPublicFacing=true inAttribute=true})"><i class="fas fa-external-link-alt" role="presentation"></i> {translate text="You Are Registered" isPublicFacing=true}</a>
							{else}
								{if !empty($recordDriver->getRegistrationModalBody())}
									<a class="btn btn-sm btn-action btn-register btn-wrap" onclick="return AspenDiscovery.Account.regInfoModal(this, 'Events', '{$recordDriver->getUniqueID()|escape}', 'assabet', '{$recordDriver->getExternalUrl()}');" style="width:70%">{translate text="Registration Information" isPublicFacing=true}
									</a>
								{else}
									<a href="{$recordDriver->getExternalUrl()}" class="btn btn-sm btn-action btn-register btn-wrap" target="_blank" style="width:70%" aria-label="{translate text="Registration Information" isPublicFacing=true inAttribute=true} ({translate text="opens in a new window" isPublicFacing=true inAttribute=true})"><i class="fas fa-external-link-alt" role="presentation"></i> {translate text="Registration Information" isPublicFacing=true}</a>
								{/if}
							{/if}
							<a href="/MyAccount/MyEvents?page=1&eventsFilter=upcoming" class="btn btn-sm btn-action btn-wrap" style="width:70%">{translate text="Go To Your Events" isPublicFacing=true}</a>
						</div>
						<br>
					{else}
						<a href="/MyAccount/MyEvents?page=1&eventsFilter=upcoming" class="btn btn-sm btn-action btn-wrap" style="width:70%">{translate text="In Your Events" isPublicFacing=true}</a>
					{/if}
				{else}
					{if $recordDriver->isRegistrationRequired()}
						<div class="btn-group btn-group-vertical btn-block">
							{if !empty($recordDriver->getRegistrationModalBody())}
								<a class="btn btn-sm btn-action btn-register btn-wrap" onclick="return AspenDiscovery.Account.regInfoModal(this, 'Events', '{$recordDriver->getUniqueID()|escape}', 'assabet', '{$recordDriver->getExternalUrl()}');" style="width:70%">{translate text="Registration Information" isPublicFacing=true}
								</a>
							{else}
								<a href="{$recordDriver->getExternalUrl()}" class="btn btn-sm btn-action btn-register btn-wrap" target="_blank" style="width:70%" aria-label="{translate text="Registration Information" isPublicFacing=true inAttribute=true} ({translate text="opens in a new window" isPublicFacing=true inAttribute=true})"><i class="fas fa-external-link-alt" role="presentation"></i> {translate text="Registration Information" isPublicFacing=true}</a>
							{/if}
							{if empty($offline) || $enableEContentWhileOffline}
								<a onclick="return AspenDiscovery.Account.saveEvent(this, 'Events', '{$recordDriver->getUniqueID()|escape}', 'assabet');"
								   class="btn btn-sm btn-action btn-wrap addToYourEventsBtn"
								   style="width:70%">{translate text="Add to Your Events" isPublicFacing=true}</a>
							{/if}
						</div>
					{elseif empty($offline) || $enableEContentWhileOffline}
						<a class="btn btn-sm btn-action btn-wrap addToYourEventsBtn" style="width:70%" onclick="return AspenDiscovery.Account.saveEvent(this, 'Events', '{$recordDriver->getUniqueID()|escape}', 'aspenEvents');">{translate text="Add to Your Events" isPublicFacing=true}</a>
					{/if}
				{/if}
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
							{translate text="Export" isPublicFacing=true}
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
			<br>
			<br>
			{$recordDriver->getFullDescription()}
		</div>
	</div>
</div>

{*Staff View Div*}
{if !empty($loggedIn) && (in_array('Administer Assabet Settings', $userPermissions))}
	<div class="row">
		<div id="more-details-accordion" class="panel-group">
			<div class="panel" id="staffPanel">
				<a data-toggle="collapse" href="#staffPanelBody">
					<div class="panel-heading">
						<div class="panel-title">
							<h2>{translate text=Staff isPublicFacing=true}</h2>
						</div>
					</div>
				</a>
				<div id="staffPanelBody" class="panel-collapse collapse">
					<div class="panel-body">
						<h3>{translate text="Assabet Event API response" isPublicFacing=true}</h3>
						<pre>{$recordDriver->getStaffView()|print_r}</pre>
					</div>
				</div>
			</div>
		</div>
	</div>
{/if}
