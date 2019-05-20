{strip}
	<div id="main-content" class="col-sm-12">
		<h2>ILS & Side Loading Dashboard</h2>
		{foreach from=$profiles item=profileName key=profileId}
			<h3>Profile: {$profileName}</h3>
			<div class="dashboardCategory">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h4 class="dashboardCategoryLabel">Active Users</h4>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-4">
						<div class="dashboardLabel">This Month</div>
						<div class="dashboardValue">{$activeUsersThisMonth.$profileId}</div>
					</div>
					<div class="col-tn-4">
						<div class="dashboardLabel">This Year</div>
						<div class="dashboardValue">{$activeUsersThisYear.$profileId}</div>
					</div>
					<div class="col-tn-4">
						<div class="dashboardLabel">All Time</div>
						<div class="dashboardValue">{$activeUsersAllTime.$profileId}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h4 class="dashboardCategoryLabel">Records Held or Accessed Online</h4>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-4">
						<div class="dashboardLabel">This Month</div>
						<div class="dashboardValue">{$activeRecordsThisMonth.$profileId.numRecordsUsed}</div>
					</div>
					<div class="col-tn-4">
						<div class="dashboardLabel">This Year</div>
						<div class="dashboardValue">{$activeRecordsThisYear.$profileId.numRecordsUsed}</div>
					</div>
					<div class="col-tn-4">
						<div class="dashboardLabel">All Time</div>
						<div class="dashboardValue">{$activeRecordsAllTime.$profileId.numRecordsUsed}</div>
					</div>
				</div>
			</div>
		{/foreach}
	</div>
{/strip}