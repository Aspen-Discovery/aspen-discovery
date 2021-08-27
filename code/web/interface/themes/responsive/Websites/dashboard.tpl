{strip}
	<div id="main-content" class="col-sm-12">
		<h1>{translate text="Website Search Dashboard"}</h1>
		{include file="Admin/selectInterfaceForm.tpl"}
		{foreach from=$websites item=websiteName key=websiteId}
			<h2>{$websiteName}</h2>
			<div class="row">
				<div class="dashboardCategory col-sm-6">
					<div class="row">
						<div class="col-sm-10 col-sm-offset-1">
							<h3 class="dashboardCategoryLabel">{translate text="Pages Viewed"} <a href="/Websites/PageStats?siteId={$websiteId}"><small>({translate text="Details"})</small></a></h3>
						</div>
					</div>
					<div class="row">
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
							<div class="dashboardValue">{$activeRecordsThisMonth.$websiteId.numRecordViewed}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
							<div class="dashboardValue">{$activeRecordsLastMonth.$websiteId.numRecordViewed}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
							<div class="dashboardValue">{$activeRecordsThisYear.$websiteId.numRecordViewed}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
							<div class="dashboardValue">{$activeRecordsAllTime.$websiteId.numRecordViewed}</div>
						</div>
					</div>
				</div>

				<div class="dashboardCategory col-sm-6">
					<div class="row">
						<div class="col-sm-10 col-sm-offset-1">
							<h3 class="dashboardCategoryLabel">{translate text="Pages Visited"} <a href="/Websites/PageStats?siteId={$websiteId}"><small>({translate text="Details"})</small></a></h3>
						</div>
					</div>
					<div class="row">
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
							<div class="dashboardValue">{$activeRecordsThisMonth.$websiteId.numRecordsUsed}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
							<div class="dashboardValue">{$activeRecordsLastMonth.$websiteId.numRecordsUsed}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
							<div class="dashboardValue">{$activeRecordsThisYear.$websiteId.numRecordsUsed}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
							<div class="dashboardValue">{$activeRecordsAllTime.$websiteId.numRecordsUsed}</div>
						</div>
					</div>
				</div>

				<div class="dashboardCategory col-sm-6">
					<div class="row">
						<div class="col-sm-10 col-sm-offset-1">
							<h3 class="dashboardCategoryLabel">Active Users</h3>
						</div>
					</div>
					<div class="row">
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
							<div class="dashboardValue">{$activeUsersThisMonth.$websiteId}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
							<div class="dashboardValue">{$activeUsersLastMonth.$websiteId}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
							<div class="dashboardValue">{$activeUsersThisYear.$websiteId}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
							<div class="dashboardValue">{$activeUsersAllTime.$websiteId}</div>
						</div>
					</div>
				</div>
			</div>
		{/foreach}
	</div>
{/strip}