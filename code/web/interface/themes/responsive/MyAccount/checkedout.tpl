{strip}
	{if $loggedIn}
		{if !empty($profile->_web_note)}
			<div class="row">
				<div id="web_note" class="alert alert-info text-center col-xs-12">{$profile->_web_note}</div>
			</div>
		{/if}

		{* Alternate Mobile MyAccount Menu *}
		{include file="MyAccount/mobilePageHeader.tpl"}
		<span class='availableHoldsNoticePlaceHolder'></span>
		<h1>{translate text='Checked Out Titles'}</h1>
		{if $libraryHoursMessage}
			<div class="libraryHours alert alert-success">{$libraryHoursMessage}</div>
		{/if}

		{if $offline}
			<div class="alert alert-warning">{translate text=offline_notice defaultText="<strong>The library system is currently offline.</strong> We are unable to retrieve information about your account at this time."}</div>
		{else}
			<ul class="nav nav-tabs" role="tablist" id="checkoutsTab">
				<li role="presentation"{if $tab=='all'} class="active"{/if}><a href="#all" aria-controls="all" role="tab" data-toggle="tab">{translate text="All"} <span class="badge"><span class="checkouts-placeholder">&nbsp;</span></span></a></li>
				<li role="presentation"{if $tab=='ils'} class="active"{/if}><a href="#ils" aria-controls="ils" role="tab" data-toggle="tab">{translate text="Physical Materials"} <span class="badge"><span class="ils-checkouts-placeholder">&nbsp;</span></span></a></li>
				{if $user->isValidForEContentSource('overdrive')}
					<li role="presentation"{if $tab=='overdrive'} class="active"{/if}><a href="#overdrive" aria-controls="overdrive" role="tab" data-toggle="tab">{translate text="OverDrive"} <span class="badge"><span class="overdrive-checkouts-placeholder">&nbsp;</span></span></a></li>
				{/if}
				{if $user->isValidForEContentSource('hoopla')}
					<li role="presentation"{if $tab=='hoopla'} class="active"{/if}><a href="#hoopla" aria-controls="hoopla" role="tab" data-toggle="tab">{translate text="Hoopla"} <span class="badge"><span class="hoopla-checkouts-placeholder">&nbsp;</span></span></a></li>
				{/if}
				{if $user->isValidForEContentSource('rbdigital')}
					<li role="presentation"{if $tab=='rbdigital'} class="active"{/if}><a href="#rbdigital" aria-controls="rbdigital" role="tab" data-toggle="tab">{translate text="RBdigital"} <span class="badge"><span class="rbdigital-checkouts-placeholder">&nbsp;</span></span></a></li>
				{/if}
                {if $user->isValidForEContentSource('cloud_library')}
					<li role="presentation"{if $tab=='cloud_library'} class="active"{/if}><a href="#cloud_library" aria-controls="cloud_library" role="tab" data-toggle="tab">{translate text="Cloud Library"} <span class="badge"><span class="cloud_library-checkouts-placeholder">&nbsp;</span></span></a></li>
                {/if}
			</ul>

			<!-- Tab panes -->
			<div class="tab-content" id="checkouts">
				<div role="tabpanel" class="tab-pane{if $tab=='all'} active{/if}" id="all" aria-label="All Checkouts List"><div id="allCheckoutsPlaceholder">{translate text="Loading checkouts from all sources"}</div></div>
				<div role="tabpanel" class="tab-pane{if $tab=='ils'} active{/if}" id="ils" aria-label="Physical Checkouts List"><div id="ilsCheckoutsPlaceholder">{translate text="Loading checkouts of physical materials"}</div></div>
				{if $user->isValidForEContentSource('overdrive')}
					<div role="tabpanel" class="tab-pane{if $tab=='overdrive'} active{/if}" id="overdrive" aria-label="OverDrive Checkouts List"><div id="overdriveCheckoutsPlaceholder">{translate text="Loading checkouts from OverDrive"}</div></div>
				{/if}
				{if $user->isValidForEContentSource('hoopla')}
					<div role="tabpanel" class="tab-pane{if $tab=='hoopla'} active{/if}" id="hoopla" aria-label="Hoopla Checkouts List"><div id="hooplaCheckoutsPlaceholder">{translate text="Loading checkouts from Hoopla"}</div></div>
				{/if}
				{if $user->isValidForEContentSource('rbdigital')}
					<div role="tabpanel" class="tab-pane{if $tab=='rbdigital'} active{/if}" id="rbdigital" aria-label="RBdigital Checkouts List"><div id="rbdigitalCheckoutsPlaceholder">{translate text="Loading checkouts from RBdigital"}</div></div>
				{/if}
                {if $user->isValidForEContentSource('cloud_library')}
					<div role="tabpanel" class="tab-pane{if $tab=='cloud_library'} active{/if}" id="cloud_library" aria-label="Cloud Library Checkouts List"><div id="cloud_libraryCheckoutsPlaceholder">{translate text="Loading checkouts from Cloud Library"}</div></div>
                {/if}
			</div>
			<script type="text/javascript">
				{literal}
				$(document).ready(function() {
                    $("a[href='#all']").on('show.bs.tab', function () {
                        AspenDiscovery.Account.loadCheckouts('all');
                    });
                    $("a[href='#ils']").on('show.bs.tab', function () {
                        AspenDiscovery.Account.loadCheckouts('ils');
                    });
                    $("a[href='#overdrive']").on('show.bs.tab', function () {
                        AspenDiscovery.Account.loadCheckouts('overdrive');
                    });
                    $("a[href='#hoopla']").on('show.bs.tab', function () {
                        AspenDiscovery.Account.loadCheckouts('hoopla');
                    });
                    $("a[href='#rbdigital']").on('show.bs.tab', function () {
                        AspenDiscovery.Account.loadCheckouts('rbdigital');
                    });
                    $("a[href='#cloud_library']").on('show.bs.tab', function () {
                        AspenDiscovery.Account.loadCheckouts('cloud_library');
                    });
                    {/literal}
                    AspenDiscovery.Account.loadCheckouts('{$tab}');
					{literal}
                });
                {/literal}
			</script>
		{/if}
	{else}
		{translate text="login_to_view_account_notice" defaultText="You must login to view this information. Click <a href="/MyAccount/Login">here</a> to login."}
	{/if}
{/strip}