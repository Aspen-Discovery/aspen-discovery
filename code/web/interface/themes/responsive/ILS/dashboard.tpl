{strip}
	<div id="main-content" class="col-sm-12">
		<h1>ILS Usage Dashboard</h1>
		{foreach from=$profiles item=profileName key=profileId}
			<h1>Profile: {$profileName}</h1>
			<div class="row">
				<div class="dashboardCategory col-sm-6">
					<div class="row">
						<div class="col-sm-10 col-sm-offset-1">
							<h2 class="dashboardCategoryLabel">Active Users</h2>
						</div>
					</div>
					<div class="row">
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="This Month"}</div>
							<div class="dashboardValue">{$activeUsersThisMonth.$profileId}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="Last Month"}</div>
							<div class="dashboardValue">{$activeUsersLastMonth.$profileId}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="This Year"}</div>
							<div class="dashboardValue">{$activeUsersThisYear.$profileId}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="All Time"}</div>
							<div class="dashboardValue">{$activeUsersAllTime.$profileId}</div>
						</div>
					</div>
				</div>

				<div class="dashboardCategory col-sm-6">
					<div class="row">
						<div class="col-sm-10 col-sm-offset-1">
							<h2 class="dashboardCategoryLabel">Records Held</h2>
						</div>
					</div>
					<div class="row">
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="This Month"}</div>
							<div class="dashboardValue">{$activeRecordsThisMonth.$profileId.numRecordsUsed}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="Last Month"}</div>
							<div class="dashboardValue">{$activeRecordsLastMonth.$profileId.numRecordsUsed}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="This Year"}</div>
							<div class="dashboardValue">{$activeRecordsThisYear.$profileId.numRecordsUsed}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="All Time"}</div>
							<div class="dashboardValue">{$activeRecordsAllTime.$profileId.numRecordsUsed}</div>
						</div>
					</div>
				</div>
				
				<div class="dashboardCategory col-sm-6">
					<div class="row">
						<div class="col-sm-10 col-sm-offset-1">
							<h2 class="dashboardCategoryLabel">Self Registrations</h2>
						</div>
					</div>
					<div class="row">
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="This Month"}</div>
							<div class="dashboardValue">{$selfRegistrationsThisMonth.$profileId}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="Last Month"}</div>
							<div class="dashboardValue">{$selfRegistrationsLastMonth.$profileId}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="This Year"}</div>
							<div class="dashboardValue">{$selfRegistrationsThisYear.$profileId}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="All Time"}</div>
							<div class="dashboardValue">{$selfRegistrationsAllTime.$profileId}</div>
						</div>
					</div>
				</div>
			</div>
		{/foreach}
	</div>
{/strip}