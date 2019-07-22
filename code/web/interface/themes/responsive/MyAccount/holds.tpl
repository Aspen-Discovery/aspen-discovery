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
				{if $user->isValidForEContentSource('rbdigital')}
					<li role="presentation"{if $tab=='rbdigital'} class="active"{/if}><a href="#rbdigital" aria-controls="rbdigital" role="tab" data-toggle="tab">{translate text="RBdigital"} <span class="badge"><span class="rbdigital-holds-placeholder">&nbsp;</span></span></a></li>
				{/if}
			</ul>

			<!-- Tab panes -->
			<div class="tab-content" id="holds">
				<div role="tabpanel" class="tab-pane{if $tab=='all'} active{/if}" id="all"><div id="allHoldsPlaceholder">{translate text="Loading holds from all sources"}</div></div>
				<div role="tabpanel" class="tab-pane{if $tab=='ils'} active{/if}" id="ils"><div id="ilsHoldsPlaceholder">{translate text="Loading holds of physical materials"}</div></div>
				{if $user->isValidForEContentSource('overdrive')}
					<div role="tabpanel" class="tab-pane{if $tab=='overdrive'} active{/if}" id="overdrive"><div id="overdriveHoldsPlaceholder">{translate text="Loading holds from OverDrive"}</div></div>
				{/if}
				{if $user->isValidForEContentSource('rbdigital')}
					<div role="tabpanel" class="tab-pane{if $tab=='rbdigital'} active{/if}" id="rbdigital"><div id="rbdigitalHoldsPlaceholder">{translate text="Loading holds from RBdigital"}</div></div>
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
					{/literal}
                    AspenDiscovery.Account.loadHolds('{$tab}');
					{literal}
                });
				{/literal}
			</script>
		{/if}
	{else} {* Check to see if user is logged in *}
		{translate text="login_to_view_account_notice" defaultText="You must login to view this information. Click <a href="{$path}/MyAccount/Login">here</a> to login."}
	{/if}
{/strip}