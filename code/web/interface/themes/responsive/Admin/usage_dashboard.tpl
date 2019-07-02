{strip}
	<div id="main-content" class="col-sm-12">
		<h2>{translate text="Aspen Discovery Usage Dashboard"}</h2>
		<h3>{translate text="General Usage"}</h3>
		<div class="row">
			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h4 class="dashboardCategoryLabel">{translate text="Page Views"}</h4>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-4">
						<div class="dashboardLabel">{translate text="This Month"}</div>
						<div class="dashboardValue">{$usageThisMonth.totalViews|number_format}</div>
					</div>
					<div class="col-tn-4">
						<div class="dashboardLabel">{translate text="This Year"}</div>
						<div class="dashboardValue">{$usageThisYear.totalViews|number_format}</div>
					</div>
					<div class="col-tn-4">
						<div class="dashboardLabel">{translate text="All Time"}</div>
						<div class="dashboardValue">{$usageAllTime.totalViews|number_format}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h4 class="dashboardCategoryLabel">{translate text="Asynchronous Requests"}</h4>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-4">
						<div class="dashboardLabel">{translate text="This Month"}</div>
						<div class="dashboardValue">{$usageThisMonth.totalAsyncRequests|number_format}</div>
					</div>
					<div class="col-tn-4">
						<div class="dashboardLabel">{translate text="This Year"}</div>
						<div class="dashboardValue">{$usageThisYear.totalAsyncRequests|number_format}</div>
					</div>
					<div class="col-tn-4">
						<div class="dashboardLabel">{translate text="All Time"}</div>
						<div class="dashboardValue">{$usageAllTime.totalAsyncRequests|number_format}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h4 class="dashboardCategoryLabel">{translate text="Covers Requested"}</h4>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-4">
						<div class="dashboardLabel">{translate text="This Month"}</div>
						<div class="dashboardValue">{$usageThisMonth.totalCovers|number_format}</div>
					</div>
					<div class="col-tn-4">
						<div class="dashboardLabel">{translate text="This Year"}</div>
						<div class="dashboardValue">{$usageThisYear.totalCovers|number_format}</div>
					</div>
					<div class="col-tn-4">
						<div class="dashboardLabel">{translate text="All Time"}</div>
						<div class="dashboardValue">{$usageAllTime.totalCovers|number_format}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h4 class="dashboardCategoryLabel">{translate text="Errors"}</h4>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-4">
						<div class="dashboardLabel">{translate text="This Month"}</div>
						<div class="dashboardValue">{$usageThisMonth.totalErrors|number_format}</div>
					</div>
					<div class="col-tn-4">
						<div class="dashboardLabel">{translate text="This Year"}</div>
						<div class="dashboardValue">{$usageThisYear.totalErrors|number_format}</div>
					</div>
					<div class="col-tn-4">
						<div class="dashboardLabel">{translate text="All Time"}</div>
						<div class="dashboardValue">{$usageAllTime.totalErrors|number_format}</div>
					</div>
				</div>
			</div>
		</div>

		<h3>Searches</h3>
		<div class="row">
			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h4 class="dashboardCategoryLabel">{translate text="Grouped Work Searches"}</h4>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-4">
						<div class="dashboardLabel">{translate text="This Month"}</div>
						<div class="dashboardValue">{$usageThisMonth.totalGroupedWorkSearches|number_format}</div>
					</div>
					<div class="col-tn-4">
						<div class="dashboardLabel">{translate text="This Year"}</div>
						<div class="dashboardValue">{$usageThisYear.totalGroupedWorkSearches|number_format}</div>
					</div>
					<div class="col-tn-4">
						<div class="dashboardLabel">{translate text="All Time"}</div>
						<div class="dashboardValue">{$usageAllTime.totalGroupedWorkSearches|number_format}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h4 class="dashboardCategoryLabel">{translate text="Open Archives Searches"}</h4>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-4">
						<div class="dashboardLabel">{translate text="This Month"}</div>
						<div class="dashboardValue">{$usageThisMonth.totalOpenArchivesSearches|number_format}</div>
					</div>
					<div class="col-tn-4">
						<div class="dashboardLabel">{translate text="This Year"}</div>
						<div class="dashboardValue">{$usageThisYear.totalOpenArchivesSearches|number_format}</div>
					</div>
					<div class="col-tn-4">
						<div class="dashboardLabel">{translate text="All Time"}</div>
						<div class="dashboardValue">{$usageAllTime.totalOpenArchivesSearches|number_format}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h4 class="dashboardCategoryLabel">{translate text="User List Searches"}</h4>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-4">
						<div class="dashboardLabel">{translate text="This Month"}</div>
						<div class="dashboardValue">{$usageThisMonth.totalUserListSearches|number_format}</div>
					</div>
					<div class="col-tn-4">
						<div class="dashboardLabel">{translate text="This Year"}</div>
						<div class="dashboardValue">{$usageThisYear.totalUserListSearches|number_format}</div>
					</div>
					<div class="col-tn-4">
						<div class="dashboardLabel">{translate text="All Time"}</div>
						<div class="dashboardValue">{$usageAllTime.totalUserListSearches|number_format}</div>
					</div>
				</div>
			</div>


		</div>
	</div>
{/strip}