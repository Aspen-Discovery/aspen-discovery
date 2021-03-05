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

		<span class='availableHoldsNoticePlaceHolder'></span>
		<h1>{translate text='Titles On Hold'}</h1>


		{* Check to see if there is data for the section *}
		{if $libraryHoursMessage}
			<div class="libraryHours alert alert-success">{$libraryHoursMessage}</div>
		{/if}
		{if $offline}
			<div class="alert alert-warning">{translate text=offline_notice defaultText="<strong>The library system is currently offline.</strong> We are unable to retrieve information about your account at this time."}</div>
		{else}
			<ul class="nav nav-tabs" role="tablist" id="holdsTab">
				<li role="presentation"{if $tab=='all'} class="active"{/if}><a href="#all" aria-controls="all" role="tab" data-toggle="tab">{translate text="All"} <span class="badge"><span class="holds-placeholder">&nbsp;</span></span></a></li>
				<li role="presentation"{if $tab=='ils'} class="active"{/if}><a href="#ils" aria-controls="ils" role="tab" data-toggle="tab">{translate text="Physical Materials"} <span class="badge"><span class="ils-holds-placeholder">&nbsp;</span></span></a></li>
				{if $user->isValidForEContentSource('overdrive')}
					<li role="presentation"{if $tab=='overdrive'} class="active"{/if}><a href="#overdrive" aria-controls="overdrive" role="tab" data-toggle="tab">{translate text="OverDrive"} <span class="badge"><span class="overdrive-holds-placeholder">&nbsp;</span></span></a></li>
				{/if}
				{if $user->isValidForEContentSource('rbdigital') && $user->showRBdigitalHolds()}
					<li role="presentation"{if $tab=='rbdigital'} class="active"{/if}><a href="#rbdigital" aria-controls="rbdigital" role="tab" data-toggle="tab">{translate text="RBdigital"} <span class="badge"><span class="rbdigital-holds-placeholder">&nbsp;</span></span></a></li>
				{/if}
				{if $user->isValidForEContentSource('cloud_library')}
					<li role="presentation"{if $tab=='cloud_library'} class="active"{/if}><a href="#cloud_library" aria-controls="cloud_library" role="tab" data-toggle="tab">{translate text="Cloud Library"} <span class="badge"><span class="cloud_library-holds-placeholder">&nbsp;</span></span></a></li>
				{/if}
				{if $user->isValidForEContentSource('axis360')}
					<li role="presentation"{if $tab=='axis360'} class="active"{/if}><a href="#axis360" aria-controls="axis360" role="tab" data-toggle="tab">{translate text="Axis 360"} <span class="badge"><span class="axis360-holds-placeholder">&nbsp;</span></span></a></li>
				{/if}
			</ul>
			<div class="refresh-indicator small pull-right">
				{translate text="Last Loaded <span id='accountLoadTime'>%1%</span>" 1=$profile->getFormattedHoldInfoLastLoaded()} <a onclick="return AspenDiscovery.Account.reloadHolds();" title="Refresh"><i class="fas fa-sync-alt"></i></a>
			</div>

			<!-- Tab panes -->
			<div class="tab-content" id="holds">
				<div role="tabpanel" class="tab-pane{if $tab=='all'} active{/if}" id="all"><div id="allHoldsPlaceholder" aria-label="All Holds List">{translate text="Loading holds from all sources"}</div></div>
				<div role="tabpanel" class="tab-pane{if $tab=='ils'} active{/if}" id="ils"><div id="ilsHoldsPlaceholder" aria-label="List of Holds on Physical Materials">{translate text="Loading holds of physical materials"}</div></div>
				{if $user->isValidForEContentSource('overdrive')}
					<div role="tabpanel" class="tab-pane{if $tab=='overdrive'} active{/if}" id="overdrive" aria-label="List of Holds on OverDrive Titles"><div id="overdriveHoldsPlaceholder">{translate text="Loading holds from OverDrive"}</div></div>
				{/if}
				{if $user->isValidForEContentSource('rbdigital')}
					<div role="tabpanel" class="tab-pane{if $tab=='rbdigital'} active{/if}" id="rbdigital" aria-label="List of Holds on RBdigital Titles"><div id="rbdigitalHoldsPlaceholder">{translate text="Loading holds from RBdigital"}</div></div>
				{/if}
				{if $user->isValidForEContentSource('cloud_library')}
					<div role="tabpanel" class="tab-pane{if $tab=='cloud_library'} active{/if}" id="cloud_library" aria-label="List of Holds on Cloud Library Titles"><div id="cloud_libraryHoldsPlaceholder">{translate text="Loading holds from Cloud Library"}</div></div>
				{/if}
				{if $user->isValidForEContentSource('axis360')}
					<div role="tabpanel" class="tab-pane{if $tab=='axis360'} active{/if}" id="axis360" aria-label="List of Holds on Axis 360 Titles"><div id="axis360HoldsPlaceholder">{translate text="Loading holds from Axis 360"}</div></div>
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
					$("a[href='#rbdigital']").on('show.bs.tab', function (e) {
						AspenDiscovery.Account.loadHolds('rbdigital');
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
		{translate text="login_to_view_account_notice" defaultText="You must sign in to view this information. Click <a href="/MyAccount/Login">here</a> to sign in."}
	{/if}
{/strip}