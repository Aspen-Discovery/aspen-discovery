{strip}
	{if $loggedIn}
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
		{if $libraryHoursMessage}
			<div class="libraryHours alert alert-success">{$libraryHoursMessage}</div>
		{/if}
		{if $offline}
			<div class="alert alert-warning"><strong>{translate text="The library system is currently offline." isPublicFacing=true}</strong> {translate text="We are unable to retrieve information about your account at this time." isPublicFacing=true}</div>
		{else}
			<ul class="nav nav-tabs" role="tablist" id="holdsTab">
				<li role="presentation"{if $tab=='all'} class="active"{/if}><a href="#all" aria-controls="all" role="tab" data-toggle="tab">{translate text="All" isPublicFacing=true} <span class="badge"><span class="holds-placeholder">&nbsp;</span></span></a></li>
				<li role="presentation"{if $tab=='ils'} class="active"{/if}><a href="#ils" aria-controls="ils" role="tab" data-toggle="tab">{translate text="Physical Materials" isPublicFacing=true} <span class="badge"><span class="ils-holds-placeholder">&nbsp;</span></span></a></li>
				{if $user->isValidForEContentSource('overdrive')}
					<li role="presentation"{if $tab=='overdrive'} class="active"{/if}><a href="#overdrive" aria-controls="overdrive" role="tab" data-toggle="tab">{translate text="OverDrive" isPublicFacing=true} <span class="badge"><span class="overdrive-holds-placeholder">&nbsp;</span></span></a></li>
				{/if}
				{if $user->isValidForEContentSource('cloud_library')}
					<li role="presentation"{if $tab=='cloud_library'} class="active"{/if}><a href="#cloud_library" aria-controls="cloud_library" role="tab" data-toggle="tab">{translate text="cloudLibrary" isPublicFacing=true} <span class="badge"><span class="cloud_library-holds-placeholder">&nbsp;</span></span></a></li>
				{/if}
				{if $user->isValidForEContentSource('axis360')}
					<li role="presentation"{if $tab=='axis360'} class="active"{/if}><a href="#axis360" aria-controls="axis360" role="tab" data-toggle="tab">{translate text="Axis 360" isPublicFacing=true} <span class="badge"><span class="axis360-holds-placeholder">&nbsp;</span></span></a></li>
				{/if}
			</ul>
			<div class="refresh-indicator small pull-right">
				{translate text="Last Loaded <span id='accountLoadTime'>%1%</span>" 1=$profile->getFormattedHoldInfoLastLoaded() isPublicFacing=true} <a onclick="return AspenDiscovery.Account.reloadHolds();" title="{translate text="Refresh" isPublicFacing=true}"><i class="fas fa-sync-alt"></i></a>
			</div>

			<!-- Tab panes -->
			<div class="tab-content" id="holds">
				<div role="tabpanel" class="tab-pane{if $tab=='all'} active{/if}" id="all"><div id="allHoldsPlaceholder" aria-label="All Holds List">{translate text="Loading holds from all sources" isPublicFacing=true}</div></div>
				<div role="tabpanel" class="tab-pane{if $tab=='ils'} active{/if}" id="ils"><div id="ilsHoldsPlaceholder" aria-label="List of Holds on Physical Materials">{translate text="Loading holds of physical materials" isPublicFacing=true}</div></div>
				{if $user->isValidForEContentSource('overdrive')}
					<div role="tabpanel" class="tab-pane{if $tab=='overdrive'} active{/if}" id="overdrive" aria-label="List of Holds on OverDrive Titles"><div id="overdriveHoldsPlaceholder">{translate text="Loading holds from OverDrive" isPublicFacing=true}</div></div>
				{/if}
				{if $user->isValidForEContentSource('cloud_library')}
					<div role="tabpanel" class="tab-pane{if $tab=='cloud_library'} active{/if}" id="cloud_library" aria-label="List of Holds on cloudLibrary Titles"><div id="cloud_libraryHoldsPlaceholder">{translate text="Loading holds from cloudLibrary" isPublicFacing=true}</div></div>
				{/if}
				{if $user->isValidForEContentSource('axis360')}
					<div role="tabpanel" class="tab-pane{if $tab=='axis360'} active{/if}" id="axis360" aria-label="List of Holds on Axis 360 Titles"><div id="axis360HoldsPlaceholder">{translate text="Loading holds from Axis 360" isPublicFacing=true}</div></div>
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
					$("a[href='#overdrive']").on('show.bs.tab', function (e) {
						AspenDiscovery.Account.loadHolds('overdrive');
					});
					$("a[href='#cloud_library']").on('show.bs.tab', function (e) {
						AspenDiscovery.Account.loadHolds('cloud_library');
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