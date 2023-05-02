<div class="col-xs-12">
	<div class="row">
		<div class="col-sm-12">
			<h1>{$recordDriver->getTitle()}</h1>
		</div>
	</div>
	<div class="row">
		<div class="col-sm-4">
			{if !empty($recordDriver->getEventCoverUrl())}
				<div class="panel active">
					<div class="panel-body">
						<a href="{$recordDriver->getLinkUrl()}"><img class="img-responsive img-thumbnail {$coverStyle}" src="{$recordDriver->getEventCoverUrl()}" alt="{$recordDriver->getTitle()|escape}"></a>
					</div>
				</div>
			{/if}
			{if !empty($recordDriver->getCategories())}
				<div class="panel active">
					<div class="panel-heading">
						{translate text="Category" isPublicFacing=true}
					</div>
					<div class="panel-body">
						{foreach from=$recordDriver->getCategories() item=category}
							<div class="col-xs-12">
								<a href='/Events/Results?filter[]=program_type_facet%3A"{$category|escape:'url'}"'>{$category}</a>
							</div>
						{/foreach}
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
		<div class="col-sm-4">
				<ul>
					{if $recordDriver->isAllDayEvent()}
						<li>{translate text="Date: " isPublicFacing=true}{$recordDriver->getStartDateString()|date_format:"%A %B %e, %Y"}</li>
						<li>{translate text="Time: All Day Event" isPublicFacing=true}</li>
					{elseif $recordDriver->isMultiDayEvent()}
						<li>{translate text="Start Date: " isPublicFacing=true}{$recordDriver->getStartDateString()|date_format:"%a %b %e, %Y %l:%M%p"}</li>
						<li>{translate text="End Date: " isPublicFacing=true}{$recordDriver->getEndDateString()|date_format:"%a %b %e, %Y %l:%M%p"}</li>
					{else}
						<li>{translate text="Date: " isPublicFacing=true}{$recordDriver->getStartDateString()|date_format:"%A %B %e, %Y"}</li>
						<li>{translate text="Time: " isPublicFacing=true}{$recordDriver->getStartDateString()|date_format:"%l:%M %p"} to {$recordDriver->getEndDateString()|date_format:"%l:%M %p"}</li>
					{/if}
					<li>{translate text="Branch: " isPublicFacing=true}{$recordDriver->getBranch()}</li>
				</ul>
		</div>
		<div class="col-sm-4" style="display:flex; justify-content:center;">
			{if $recordDriver->inEvents()}
				{if $recordDriver->isRegistrationRequired()}
					<div class="btn-group btn-group-vertical btn-block">
						<a href="{$recordDriver->getExternalUrl()}" class="btn btn-sm btn-action btn-wrap" target="_blank" style="width:100%"><i class="fas fa-external-link-alt"></i>{translate text="Check Registration" isPublicFacing=true}</a>
						<a href="/MyAccount/MyEvents?page=1&eventsFilter=upcoming" class="btn btn-sm btn-action btn-wrap" style="width:100%">{translate text="Go To Your Events" isPublicFacing=true}</a>
					</div>
					<br>
				{else}
					<a href="/MyAccount/MyEvents?page=1&eventsFilter=upcoming" class="btn btn-sm btn-action btn-wrap" style="width:100%">{translate text="In Your Events" isPublicFacing=true}</a>
				{/if}
			{else}
				{if $recordDriver->isRegistrationRequired()}
					<a class="btn btn-primary"  onclick="return AspenDiscovery.Account.saveEventReg(this, 'Events', '{$recordDriver->getUniqueID()|escape}', '{$recordDriver->getExternalUrl()}');">
						<i class="fas fa-external-link-alt"></i>
						{translate text=" Add to Your Events and Register" isPublicFacing=true}
					</a>
				{else}
					<a class="btn btn-primary" onclick="return AspenDiscovery.Account.saveEvent(this, 'Events', '{$recordDriver->getUniqueID()|escape}');">{translate text="Add to Your Events" isPublicFacing=true}</a>
				{/if}
			{/if}
		</div>
			<br>
		<div class="col-sm-8">
			<div class="btn-group btn-group-sm">
				<a href="{$recordDriver->getExternalUrl()}" class="btn btn-sm addtolistlink addToListBtn" target="_blank"><i class="fas fa-external-link-alt"></i> {translate text="More Info" isPublicFacing=true}</a>
				<button onclick="return AspenDiscovery.Account.showSaveToListForm(this, 'Events', '{$recordDriver->getUniqueID()|escape}');" class="btn btn-sm addtolistlink addToListBtn">{translate text="Add to list" isPublicFacing=true}</button>
			</div>
			<div class="btn-group btn-group-sm">
				{include file="Events/share-tools.tpl" eventUrl=$recordDriver->getExternalUrl()}
			</div>
			<br>
			<br>
		</div>
		<div class="col-sm-8">
			{$recordDriver->getDescription()}
		</div>
	</div>
		<div class="row">
			<div class="col-sm-12">
				{if !empty($loggedIn) && (in_array('Administer LibraryMarket LibraryCalendar Settings', $userPermissions))}
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
				{/if}
			</div>
		</div>
</div>