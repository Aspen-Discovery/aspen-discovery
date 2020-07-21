{strip}
	<div id="main-content" class="col-sm-12">
		<h1>{translate text="Aspen Discovery Usage Dashboard"}</h1>
		<h2>{translate text="General Usage"}</h2>
		<div class="row">
			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h3 class="dashboardCategoryLabel">{translate text="Page Views"}</h3>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month"}</div>
						<div class="dashboardValue">{$usageThisMonth.totalViews|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month"}</div>
						<div class="dashboardValue">{$usageLastMonth.totalViews|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year"}</div>
						<div class="dashboardValue">{$usageThisYear.totalViews|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time"}</div>
						<div class="dashboardValue">{$usageAllTime.totalViews|number_format}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h3 class="dashboardCategoryLabel">{translate text="Asynchronous Requests"}</h3>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month"}</div>
						<div class="dashboardValue">{$usageThisMonth.totalAsyncRequests|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month"}</div>
						<div class="dashboardValue">{$usageLastMonth.totalAsyncRequests|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year"}</div>
						<div class="dashboardValue">{$usageThisYear.totalAsyncRequests|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time"}</div>
						<div class="dashboardValue">{$usageAllTime.totalAsyncRequests|number_format}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h3 class="dashboardCategoryLabel">{translate text="Covers Requested"}</h3>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month"}</div>
						<div class="dashboardValue">{$usageThisMonth.totalCovers|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month"}</div>
						<div class="dashboardValue">{$usageLastMonth.totalCovers|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year"}</div>
						<div class="dashboardValue">{$usageThisYear.totalCovers|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time"}</div>
						<div class="dashboardValue">{$usageAllTime.totalCovers|number_format}</div>
					</div>
				</div>
			</div>
		</div>

		<h2>Searches</h2>
		<div class="row">
			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h3 class="dashboardCategoryLabel">{translate text="Grouped Work Searches"}</h3>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month"}</div>
						<div class="dashboardValue">{$usageThisMonth.totalGroupedWorkSearches|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month"}</div>
						<div class="dashboardValue">{$usageLastMonth.totalGroupedWorkSearches|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year"}</div>
						<div class="dashboardValue">{$usageThisYear.totalGroupedWorkSearches|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time"}</div>
						<div class="dashboardValue">{$usageAllTime.totalGroupedWorkSearches|number_format}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h3 class="dashboardCategoryLabel">{translate text="User List Searches"}</h3>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month"}</div>
						<div class="dashboardValue">{$usageThisMonth.totalUserListSearches|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month"}</div>
						<div class="dashboardValue">{$usageLastMonth.totalUserListSearches|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year"}</div>
						<div class="dashboardValue">{$usageThisYear.totalUserListSearches|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time"}</div>
						<div class="dashboardValue">{$usageAllTime.totalUserListSearches|number_format}</div>
					</div>
				</div>
			</div>

			{if array_key_exists('EBSCO EDS', $enabledModules)}
				<div class="dashboardCategory col-sm-6">
					<div class="row">
						<div class="col-sm-10 col-sm-offset-1">
							<h3 class="dashboardCategoryLabel">{translate text="EBSCO EDS Searches"}</h3>
						</div>
					</div>
					<div class="row">
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="This Month"}</div>
							<div class="dashboardValue">{$usageThisMonth.totalEbscoEdsSearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="Last Month"}</div>
							<div class="dashboardValue">{$usageLastMonth.totalEbscoEdsSearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="This Year"}</div>
							<div class="dashboardValue">{$usageThisYear.totalEbscoEdsSearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="All Time"}</div>
							<div class="dashboardValue">{$usageAllTime.totalEbscoEdsSearches|number_format}</div>
						</div>
					</div>
				</div>
			{/if}

			{if array_key_exists('Events', $enabledModules)}
				<div class="dashboardCategory col-sm-6">
					<div class="row">
						<div class="col-sm-10 col-sm-offset-1">
							<h3 class="dashboardCategoryLabel">{translate text="Events Searches"}</h3>
						</div>
					</div>
					<div class="row">
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="This Month"}</div>
							<div class="dashboardValue">{$usageThisMonth.totalEventsSearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="Last Month"}</div>
							<div class="dashboardValue">{$usageLastMonth.totalEventsSearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="This Year"}</div>
							<div class="dashboardValue">{$usageThisYear.totalEventsSearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="All Time"}</div>
							<div class="dashboardValue">{$usageAllTime.totalEventsSearches|number_format}</div>
						</div>
					</div>
				</div>
			{/if}

			{if array_key_exists('Open Archives', $enabledModules)}
				<div class="dashboardCategory col-sm-6">
					<div class="row">
						<div class="col-sm-10 col-sm-offset-1">
							<h3 class="dashboardCategoryLabel">{translate text="Open Archives Searches"}</h3>
						</div>
					</div>
					<div class="row">
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="This Month"}</div>
							<div class="dashboardValue">{$usageThisMonth.totalOpenArchivesSearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="Last Month"}</div>
							<div class="dashboardValue">{$usageLastMonth.totalOpenArchivesSearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="This Year"}</div>
							<div class="dashboardValue">{$usageThisYear.totalOpenArchivesSearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="All Time"}</div>
							<div class="dashboardValue">{$usageAllTime.totalOpenArchivesSearches|number_format}</div>
						</div>
					</div>
				</div>
			{/if}

			{if array_key_exists('Web Indexer', $enabledModules)}
				<div class="dashboardCategory col-sm-6">
					<div class="row">
						<div class="col-sm-10 col-sm-offset-1">
							<h3 class="dashboardCategoryLabel">{translate text="Website Searches"}</h3>
						</div>
					</div>
					<div class="row">
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="This Month"}</div>
							<div class="dashboardValue">{$usageThisMonth.totalWebsiteSearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="Last Month"}</div>
							<div class="dashboardValue">{$usageLastMonth.totalWebsiteSearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="This Year"}</div>
							<div class="dashboardValue">{$usageThisYear.totalWebsiteSearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="All Time"}</div>
							<div class="dashboardValue">{$usageAllTime.totalWebsiteSearches|number_format}</div>
						</div>
					</div>
				</div>
			{/if}
		</div>

		<h2>{translate text="Exceptions"}</h2>

		<div class="dashboardCategory col-sm-6">
			<div class="row">
				<div class="col-sm-10 col-sm-offset-1">
					<h3 class="dashboardCategoryLabel">{translate text="Blocked Pages"}</h3>
				</div>
			</div>
			<div class="row">
				<div class="col-tn-6">
					<div class="dashboardLabel">{translate text="This Month"}</div>
					<div class="dashboardValue">{$usageThisMonth.totalBlockedRequests|number_format}</div>
				</div>
				<div class="col-tn-6">
					<div class="dashboardLabel">{translate text="Last Month"}</div>
					<div class="dashboardValue">{$usageLastMonth.totalBlockedRequests|number_format}</div>
				</div>
				<div class="col-tn-6">
					<div class="dashboardLabel">{translate text="This Year"}</div>
					<div class="dashboardValue">{$usageThisYear.totalBlockedRequests|number_format}</div>
				</div>
				<div class="col-tn-6">
					<div class="dashboardLabel">{translate text="All Time"}</div>
					<div class="dashboardValue">{$usageAllTime.totalBlockedRequests|number_format}</div>
				</div>
			</div>
		</div>

		<div class="dashboardCategory col-sm-6">
			<div class="row">
				<div class="col-sm-10 col-sm-offset-1">
					<h3 class="dashboardCategoryLabel">{translate text="Blocked API Requests"}</h3>
				</div>
			</div>
			<div class="row">
				<div class="col-tn-6">
					<div class="dashboardLabel">{translate text="This Month"}</div>
					<div class="dashboardValue">{$usageThisMonth.totalBlockedApiRequests|number_format}</div>
				</div>
				<div class="col-tn-6">
					<div class="dashboardLabel">{translate text="Last Month"}</div>
					<div class="dashboardValue">{$usageLastMonth.totalBlockedApiRequests|number_format}</div>
				</div>
				<div class="col-tn-6">
					<div class="dashboardLabel">{translate text="This Year"}</div>
					<div class="dashboardValue">{$usageThisYear.totalBlockedApiRequests|number_format}</div>
				</div>
				<div class="col-tn-6">
					<div class="dashboardLabel">{translate text="All Time"}</div>
					<div class="dashboardValue">{$usageAllTime.totalBlockedApiRequests|number_format}</div>
				</div>
			</div>
		</div>

		<div class="dashboardCategory col-sm-6">
			<div class="row">
				<div class="col-sm-10 col-sm-offset-1">
					<h3 class="dashboardCategoryLabel">{translate text="Errors"}</h3>
				</div>
			</div>
			<div class="row">
				<div class="col-tn-6">
					<div class="dashboardLabel">{translate text="This Month"}</div>
					<div class="dashboardValue">{$usageThisMonth.totalErrors|number_format}</div>
				</div>
				<div class="col-tn-6">
					<div class="dashboardLabel">{translate text="Last Month"}</div>
					<div class="dashboardValue">{$usageLastMonth.totalErrors|number_format}</div>
				</div>
				<div class="col-tn-6">
					<div class="dashboardLabel">{translate text="This Year"}</div>
					<div class="dashboardValue">{$usageThisYear.totalErrors|number_format}</div>
				</div>
				<div class="col-tn-6">
					<div class="dashboardLabel">{translate text="All Time"}</div>
					<div class="dashboardValue">{$usageAllTime.totalErrors|number_format}</div>
				</div>
			</div>
		</div>
	</div>
{/strip}