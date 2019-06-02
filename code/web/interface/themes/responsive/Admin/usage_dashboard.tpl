{strip}
	<div id="main-content" class="col-sm-12">
		<h2>Aspen Discovery Usage Dashboard</h2>
		<h3>General Usage</h3>
		<div class="row">
			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h4 class="dashboardCategoryLabel">Page Views</h4>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-4">
						<div class="dashboardLabel">This Month</div>
						<div class="dashboardValue">{$usageThisMonth.totalViews}</div>
					</div>
					<div class="col-tn-4">
						<div class="dashboardLabel">This Year</div>
						<div class="dashboardValue">{$usageThisYear.totalViews}</div>
					</div>
					<div class="col-tn-4">
						<div class="dashboardLabel">All Time</div>
						<div class="dashboardValue">{$usageAllTime.totalViews}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h4 class="dashboardCategoryLabel">Asynchronous Requests</h4>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-4">
						<div class="dashboardLabel">This Month</div>
						<div class="dashboardValue">{$usageThisMonth.totalAsyncRequests}</div>
					</div>
					<div class="col-tn-4">
						<div class="dashboardLabel">This Year</div>
						<div class="dashboardValue">{$usageThisYear.totalAsyncRequests}</div>
					</div>
					<div class="col-tn-4">
						<div class="dashboardLabel">All Time</div>
						<div class="dashboardValue">{$usageAllTime.totalAsyncRequests}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h4 class="dashboardCategoryLabel">Covers Requested</h4>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-4">
						<div class="dashboardLabel">This Month</div>
						<div class="dashboardValue">{$usageThisMonth.totalCovers}</div>
					</div>
					<div class="col-tn-4">
						<div class="dashboardLabel">This Year</div>
						<div class="dashboardValue">{$usageThisYear.totalCovers}</div>
					</div>
					<div class="col-tn-4">
						<div class="dashboardLabel">All Time</div>
						<div class="dashboardValue">{$usageAllTime.totalCovers}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h4 class="dashboardCategoryLabel">Errors</h4>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-4">
						<div class="dashboardLabel">This Month</div>
						<div class="dashboardValue">{$usageThisMonth.totalErrors}</div>
					</div>
					<div class="col-tn-4">
						<div class="dashboardLabel">This Year</div>
						<div class="dashboardValue">{$usageThisYear.totalErrors}</div>
					</div>
					<div class="col-tn-4">
						<div class="dashboardLabel">All Time</div>
						<div class="dashboardValue">{$usageAllTime.totalErrors}</div>
					</div>
				</div>
			</div>
		</div>

		<h3>Searches</h3>
		<div class="row">
			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h4 class="dashboardCategoryLabel">Grouped Work Searches</h4>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-4">
						<div class="dashboardLabel">This Month</div>
						<div class="dashboardValue">{$usageThisMonth.totalGroupedWorkSearches}</div>
					</div>
					<div class="col-tn-4">
						<div class="dashboardLabel">This Year</div>
						<div class="dashboardValue">{$usageThisYear.totalGroupedWorkSearches}</div>
					</div>
					<div class="col-tn-4">
						<div class="dashboardLabel">All Time</div>
						<div class="dashboardValue">{$usageAllTime.totalGroupedWorkSearches}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h4 class="dashboardCategoryLabel">Open Archives Searches</h4>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-4">
						<div class="dashboardLabel">This Month</div>
						<div class="dashboardValue">{$usageThisMonth.totalOpenArchivesSearches}</div>
					</div>
					<div class="col-tn-4">
						<div class="dashboardLabel">This Year</div>
						<div class="dashboardValue">{$usageThisYear.totalOpenArchivesSearches}</div>
					</div>
					<div class="col-tn-4">
						<div class="dashboardLabel">All Time</div>
						<div class="dashboardValue">{$usageAllTime.totalOpenArchivesSearches}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h4 class="dashboardCategoryLabel">User List Searches</h4>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-4">
						<div class="dashboardLabel">This Month</div>
						<div class="dashboardValue">{$usageThisMonth.totalUserListSearches}</div>
					</div>
					<div class="col-tn-4">
						<div class="dashboardLabel">This Year</div>
						<div class="dashboardValue">{$usageThisYear.totalUserListSearches}</div>
					</div>
					<div class="col-tn-4">
						<div class="dashboardLabel">All Time</div>
						<div class="dashboardValue">{$usageAllTime.totalUserListSearches}</div>
					</div>
				</div>
			</div>


		</div>
	</div>
{/strip}