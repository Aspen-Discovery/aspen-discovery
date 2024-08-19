{strip}
	<div id="main-content" class="col-sm-12">
		<h1>{translate text="Side Loading Dashboard" isAdminFacing=true}</h1>
		{include file="Admin/selectInterfaceForm.tpl"}

		{foreach from=$profiles item=profileName key=profileId}
			<h2>{$profileName}</h2>
			<div class="row">
				<div class="dashboardCategory col-sm-6">
					<div class="row">
						<div class="col-sm-10 col-sm-offset-1">
							<h3 class="dashboardCategoryLabel">{translate text="Active Users" isAdminFacing=true}
								{' '}
								<a href="/SideLoads/UsageGraphs?stat=activeUsers{if !empty($profileName)}&profileName={$profileName}{/if}&instance={$selectedInstance}" title="{translate text="Show Active Users Graph" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chart-line"></i></a>
							</h3>
						</div>
					</div>
					<div class="row">
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
							<div class="dashboardValue">{$activeUsersThisMonth.$profileId}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
							<div class="dashboardValue">{$activeUsersLastMonth.$profileId}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
							<div class="dashboardValue">{$activeUsersThisYear.$profileId}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
							<div class="dashboardValue">{$activeUsersAllTime.$profileId}</div>
						</div>
					</div>
				</div>
	
				<div class="dashboardCategory col-sm-6">
					<div class="row">
						<div class="col-sm-10 col-sm-offset-1">
							<h3 class="dashboardCategoryLabel">{translate text="Records Accessed Online" isAdminFacing=true}
								{' '}
								<a href="/SideLoads/UsageGraphs?stat=recordsAccessedOnline{if !empty($profileName)}&profileName={$profileName}{/if}&instance={$selectedInstance}" title="{translate text="Show Records Accessed Online Graph" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chart-line"></i></a>
							</h3>
						</div>
					</div>
					<div class="row">
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
							<div class="dashboardValue">{$activeRecordsThisMonth.$profileId.numRecordsUsed}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
							<div class="dashboardValue">{$activeRecordsLastMonth.$profileId.numRecordsUsed}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
							<div class="dashboardValue">{$activeRecordsThisYear.$profileId.numRecordsUsed}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
							<div class="dashboardValue">{$activeRecordsAllTime.$profileId.numRecordsUsed}</div>
						</div>
					</div>
				</div>
			</div>
		{/foreach}
	</div>
{/strip}