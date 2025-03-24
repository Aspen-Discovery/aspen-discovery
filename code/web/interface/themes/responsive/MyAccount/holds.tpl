{strip}
	{if !empty($loggedIn)}
		{if !empty($profile->_web_note)}
			<div class="row">
				<div id="web_note" class="alert alert-info text-center col-xs-12">{$profile->_web_note}</div>
			</div>
		{/if}
		{if !empty($accountMessages)}
			{include file='systemMessages.tpl' messages=$accountMessages}
		{/if}
		{if !empty($ilsMessages)}
			{include file='ilsMessages.tpl' messages=$ilsMessages}
		{/if}

		<h1>{translate text='Titles On Hold' isPublicFacing=true}</h1>

		{* Check to see if there is data for the section *}
		{if !empty($libraryHoursMessage)}
			<div class="libraryHours alert alert-success">{$libraryHoursMessage}</div>
		{/if}
		{if !empty($offline) && !$enableEContentWhileOffline}
			<div class="alert alert-warning"><strong>{translate text=$offlineMessage isPublicFacing=true}</strong></div>
		{else}
		{if count($linkedUsers) > 0 && $allowFilteringOfLinkedAccountsInHolds}
			<div class="row">
				<div class="col-tn-12">
					<div id="linkedUserOptions" class="form-group">
						<label class="control-label" for="linkedUsersDropdown">{translate text="Linked Users" isPublicFacing=true}&nbsp;</label>
						<select name="selectedUser" id="linkedUsersDropdown" class="form-control" onchange="AspenDiscovery.Account.filterOutLinkedUsers();">
							<option value="" {if $selectedUser == ""}selected{/if}>All</option>
							<option value="{$currentUserId}" {if $selectedUser == $currentUserId} selected="selected"{/if}>
								{$currentUserName}
							</option>
							{foreach from=$linkedUsers item=user}
								<option value="{$user->id}"{if $selectedUser == $user->id} selected="selected"{/if}>{$user->displayName}</option>
							{/foreach}
						</select>
					</div>
				</div>
			</div>
		{/if}
			<ul class="nav nav-tabs" role="tablist" id="holdsTab">
				{if empty($offline)}
					<li role="presentation"{if $tab=='all'} class="active"{/if}><a href="#all" aria-controls="all" role="tab" data-toggle="tab">{translate text="All" isPublicFacing=true} <span class="badge"><span class="holds-placeholder">&nbsp;</span></span></a></li>
					<li role="presentation"{if $tab=='ils'} class="active"{/if}><a href="#ils" aria-controls="ils" role="tab" data-toggle="tab">{translate text="Physical Materials" isPublicFacing=true} <span class="badge"><span class="ils-holds-placeholder">&nbsp;</span></span></a></li>
				{/if}
				{if $user->getInterlibraryLoanType() == 'vdx'}
					<li role="presentation"{if $tab=='interlibrary_loan'} class="active"{/if}><a href="#interlibrary_loan" aria-controls="interlibrary_loan" role="tab" data-toggle="tab">{translate text="Interlibrary Loan Requests" isPublicFacing=true} <span class="badge"><span class="interlibrary-loan-requests-placeholder">&nbsp;</span></span></a></li>
				{/if}
				{if $user->isValidForEContentSource('overdrive')}
					<li role="presentation"{if $tab=='overdrive'} class="active"{/if}><a href="#overdrive" aria-controls="overdrive" role="tab" data-toggle="tab">{$readerName} <span class="badge"><span class="overdrive-holds-placeholder">&nbsp;</span></span></a></li>
				{/if}
				{if $user->isValidForEContentSource('palace_project')}
					<li role="presentation"{if $tab=='palace_project'} class="active"{/if}><a href="#palace_project" aria-controls="palace_project" role="tab" data-toggle="tab">{translate text="Palace Project" isPublicFacing=true} <span class="badge"><span class="palace_project-holds-placeholder">&nbsp;</span></span></a></li>
				{/if}
				{if $user->isValidForEContentSource('cloud_library')}
					<li role="presentation"{if $tab=='cloud_library'} class="active"{/if}><a href="#cloud_library" aria-controls="cloud_library" role="tab" data-toggle="tab">{translate text="cloudLibrary" isPublicFacing=true} <span class="badge"><span class="cloud_library-holds-placeholder">&nbsp;</span></span></a></li>
				{/if}
				{if $user->isValidForEContentSource('axis360')}
					<li role="presentation"{if $tab=='axis360'} class="active"{/if}><a href="#axis360" aria-controls="axis360" role="tab" data-toggle="tab">{translate text="Boundless" isPublicFacing=true} <span class="badge"><span class="axis360-holds-placeholder">&nbsp;</span></span></a></li>
				{/if}
			</ul>
			<div class="refresh-indicator small pull-right">
				{translate text="Last Loaded <span id='accountLoadTime'>%1%</span>" 1=$profile->getFormattedHoldInfoLastLoaded() isPublicFacing=true} <a class="btn btn-default btn-sm" href="#" onclick="return AspenDiscovery.Account.reloadHolds();" title="{translate text="Refresh" isPublicFacing=true}">{translate text="Refresh" isPublicFacing=true inAttribute=true} <i class="fas fa-sync-alt" role="presentation"></i></a>
			</div>

			<!-- Tab panes -->
			<div class="tab-content" id="holds">
				{if empty($offline)}
					<div role="tabpanel" class="tab-pane{if $tab=='all'} active{/if}" id="all"><div id="allHoldsPlaceholder" aria-label="All Holds List">{translate text="Loading holds from all sources" isPublicFacing=true}</div></div>
					<div role="tabpanel" class="tab-pane{if $tab=='ils'} active{/if}" id="ils"><div id="ilsHoldsPlaceholder" aria-label="List of Holds on Physical Materials">{translate text="Loading holds of physical materials" isPublicFacing=true}</div></div>
				{/if}
				{if $user->getInterlibraryLoanType() == 'vdx'}
					<div role="tabpanel" class="tab-pane{if $tab=='interlibrary_loan'} active{/if}" id="interlibrary_loan" aria-label="List of Interlibrary Loan Requests"><div id="interlibrary_loanHoldsPlaceholder">{translate text="Loading Interlibrary Loan Requests" isPublicFacing=true}</div></div>
				{/if}
				{if $user->isValidForEContentSource('overdrive')}
					<div role="tabpanel" class="tab-pane{if $tab=='overdrive'} active{/if}" id="overdrive" aria-label="List of Holds on OverDrive Titles"><div id="overdriveHoldsPlaceholder">{translate text="Loading holds from %1%" 1=$readerName isPublicFacing=true}</div></div>
				{/if}
				{if $user->isValidForEContentSource('cloud_library')}
					<div role="tabpanel" class="tab-pane{if $tab=='cloud_library'} active{/if}" id="cloud_library" aria-label="List of Holds on cloudLibrary Titles"><div id="cloud_libraryHoldsPlaceholder">{translate text="Loading holds from cloudLibrary" isPublicFacing=true}</div></div>
				{/if}
				{if $user->isValidForEContentSource('palace_project')}
					<div role="tabpanel" class="tab-pane{if $tab=='palace_project'} active{/if}" id="palace_project" aria-label="List of Holds on Palace Project Titles"><div id="palace_projectHoldsPlaceholder">{translate text="Loading holds from Palace Project" isPublicFacing=true}</div></div>
				{/if}
				{if $user->isValidForEContentSource('axis360')}
					<div role="tabpanel" class="tab-pane{if $tab=='axis360'} active{/if}" id="axis360" aria-label="List of Holds on Boundless Titles"><div id="axis360HoldsPlaceholder">{translate text="Loading holds from Boundless" isPublicFacing=true}</div></div>
				{/if}
			</div>
			<script type="text/javascript">
				{literal}
				$(document).ready(function() {
					$("a[href='#all']").on('show.bs.tab', function (e) {
						AspenDiscovery.Account.loadHolds('all');
					});
					$("a[href='#ils']").on('show.bs.tab', function (e) {
						AspenDiscovery.Account.loadHolds('ils');
					});
					$("a[href='#interlibrary_loan']").on('show.bs.tab', function (e) {
						AspenDiscovery.Account.loadHolds('interlibrary_loan');
					});
					$("a[href='#overdrive']").on('show.bs.tab', function (e) {
						AspenDiscovery.Account.loadHolds('overdrive');
					});
					$("a[href='#cloud_library']").on('show.bs.tab', function (e) {
						AspenDiscovery.Account.loadHolds('cloud_library');
					});
					$("a[href='#palace_project']").on('show.bs.tab', function (e) {
						AspenDiscovery.Account.loadHolds('palace_project');
					});
					$("a[href='#axis360']").on('show.bs.tab', function (e) {
						AspenDiscovery.Account.loadHolds('axis360');
					});
					{/literal}
					AspenDiscovery.Account.loadHolds('{$tab}');
					{literal}
				});
				{/literal}
			</script>
		{/if}
	{else} {* Check to see if user is logged in *}
		{translate text="You must sign in to view this information." isPublicFacing=true}<a href='/MyAccount/Login' class="btn btn-primary">{translate text="Sign In" isPublicFacing=true}</a>
	{/if}
{/strip}
