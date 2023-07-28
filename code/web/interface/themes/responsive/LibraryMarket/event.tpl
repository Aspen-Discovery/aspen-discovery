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
	{if !empty($recordDriver->getEventCoverUrl()) || !empty($recordDriver->getAudiences())}
	<div class="col-tn-12 col-xs-12 col-sm-4 col-md-3 col-lg-3">
		{if !empty($recordDriver->getEventCoverUrl())}
			<div class="panel active">
				<div class="panel-body">
					<a href="{$recordDriver->getLinkUrl()}"><img class="img-responsive img-thumbnail {$coverStyle}" src="{$recordDriver->getEventCoverUrl()}" alt="{$recordDriver->getTitle()|escape}"></a>
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
	</div>

	{*Content Right of Panel*}
	<div class="col-tn-12 col-xs-12 col-sm-8 col-md-9 col-lg-9">
		{else}
	<div class="col-tn-12 col-xs-12 col-sm-12 col-md-12 col-lg-12">
		{/if}
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
					</ul>
				</div>
				<div class="col-tn-4" style="display:flex; justify-content:center;">
				{if $recordDriver->inEvents()}
					{if $recordDriver->isRegistrationRequired()}
						<div class="btn btn-sm btn-action btn-wrap" style="width:70%">
							<a href="{$recordDriver->getExternalUrl()}" class="btn btn-sm btn-info btn-wrap" target="_blank" style="width:70%"><i class="fas fa-external-link-alt"></i>&nbsp{translate text="Check Registration" isPublicFacing=true}</a>
							<a href="/MyAccount/MyEvents?page=1&eventsFilter=upcoming" class="btn btn-sm btn-action btn-wrap">{translate text="Go To Your Events" isPublicFacing=true}</a>
						</div>
						<br>
					{else}
						<a href="/MyAccount/MyEvents?page=1&eventsFilter=upcoming" class="btn btn-sm btn-action btn-wrap">{translate text="In Your Events" isPublicFacing=true}</a>
					{/if}
				{else}
					{if $recordDriver->isRegistrationRequired()}
						<div class="btn-group btn-group-vertical btn-block">
							<a href="{$recordDriver->getExternalUrl()}" class="btn btn-sm btn-info btn-wrap" target="_blank" style="width:70%"><i class="fas fa-external-link-alt"></i>&nbsp{translate text="Registration Information" isPublicFacing=true}</a>
							<a onclick="return AspenDiscovery.Account.saveEvent(this, 'Events', '{$recordDriver->getUniqueID()|escape}');" class="btn btn-sm btn-action btn-wrap" style="width:70%">{translate text="Add to Your Events" isPublicFacing=true}</a>
						</div>
						{*<a class="btn btn-sm btn-action btn-wrap" style="width:70%"  onclick="return AspenDiscovery.Account.saveEventReg(this, 'Events', '{$recordDriver->getUniqueID()|escape}', '{$recordDriver->getExternalUrl()}');">
							<i class="fas fa-external-link-alt"></i>
							{translate text=" Add to Your Events and Register" isPublicFacing=true}
						</a>*}
					{else}
						<a class="btn btn-sm btn-action btn-wrap" style="width:70%" onclick="return AspenDiscovery.Account.saveEvent(this, 'Events', '{$recordDriver->getUniqueID()|escape}');">{translate text="Add to Your Events" isPublicFacing=true}</a>
					{/if}
				{/if}
			</div>
		</div>
		{*column for tool buttons & event description*}
			<div class="col-sm-9">
				<div class="btn-group btn-group-sm">
					<a href="{$recordDriver->getExternalUrl()}" class="btn btn-sm btn-tools" target="_blank"><i class="fas fa-external-link-alt"></i> {translate text="More Info" isPublicFacing=true}</a>
					{if $isStaffWithPermissions && $eventsInLists == 1 || $eventsInLists == 2}
						<button onclick="return AspenDiscovery.Account.showSaveToListForm(this, 'Events', '{$recordDriver->getUniqueID()|escape}');" class="btn btn-sm btn-tools addToListBtn">{translate text="Add to List" isPublicFacing=true}</button>
					{/if}
				</div>
				<div class="btn-group btn-group-sm">
					{include file="Events/share-tools.tpl" eventUrl=$recordDriver->getExternalUrl()}
				</div>
				<br>
				<br>
				{$recordDriver->getDescription()}
			</div>
	</div>
</div>

{*Staff View Div*}
{if !empty($loggedIn) && (in_array('Administer LibraryMarket LibraryCalendar Settings', $userPermissions))}
	<div class="row">
		<div class="col-sm-12">
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
							<h3>{translate text="LibraryMarket LibraryCalendar Event API response" isPublicFacing=true}</h3>
							<pre>{$recordDriver->getStaffView()|print_r}</pre>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
{/if}