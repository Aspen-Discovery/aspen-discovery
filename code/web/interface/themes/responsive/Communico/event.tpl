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
		</div>
		<div class="col-sm-4">
			<ul>
				{if $recordDriver->getEventLength() == 0}
					<li>{translate text="Date: " isPublicFacing=true}{$recordDriver->getStartDate()|date_format:"%A %B %e, %Y"}</li>
					<li>{translate text="Time: All Day Event" isPublicFacing=true}</li>
				{elseif $recordDriver->getEventLength() > 24}
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
				{if !empty($recordDriver->getType())}
					<li>{translate text="Event Type: " isPublicFacing=true}{$recordDriver->getType()}</li>
				{/if}
			</ul>
		</div>
		<div class="col-sm-4" style="display:flex; justify-content:center;">
			{if $recordDriver->isRegistrationRequired()}
				<a class="btn btn-primary"  onclick="return AspenDiscovery.Account.saveEventReg(this, 'Events', '{$recordDriver->getUniqueID()|escape}', '{$recordDriver->getExternalUrl()}');">
					<i class="fas fa-external-link-alt"></i>
					{translate text=" Add to Your Events and Register" isPublicFacing=true}
				</a>
			{else}
				<a class="btn btn-primary" onclick="return AspenDiscovery.Account.saveEvent(this, 'Events', '{$recordDriver->getUniqueID()|escape}');">{translate text="Add to Your Events" isPublicFacing=true}</a>
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
		{*If there is no image or program types we need to make a new row to display properly*}
		{*A new row causes incorrect displays if there is an image or a panel for program type*}
		{if (empty($recordDriver->getEventCoverUrl()) || empty($recordDriver->getProgramTypes()))}
	</div>
	<div class="row">
		<div class="col-sm-offset-4 col-sm-8">
			{else}
		<div class="col-sm-8">
			{/if}
			{$recordDriver->getDescription()}
			<br>
			<br>
			{$recordDriver->getFullDescription()}
		</div>
	</div>
		{if !empty($loggedIn) && (in_array('Administer Communico Settings', $userPermissions))}
		<br>
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
							<h3>{translate text="Communico Event API response" isPublicFacing=true}</h3>
							<pre>{$recordDriver->getStaffView()|print_r}</pre>
						</div>
					</div>
				</div>
			</div> {* End of tabs*}
		</div>
	</div>
	{/if}
</div>