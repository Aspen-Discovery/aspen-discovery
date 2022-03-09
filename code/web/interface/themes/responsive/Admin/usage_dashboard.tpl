{strip}
	<div id="main-content" class="col-sm-12">
		<h1>{translate text="Aspen Discovery Usage Dashboard" isAdminFacing=true}</h1>
		{include file="Admin/selectInterfaceForm.tpl"}

		<h2>{translate text="General Usage" isAdminFacing=true} <a href="/Admin/UsageGraphs?stat=generalUsage&instance={$selectedInstance}" title="{translate text="Show Active Users Graph" inAttribute="true"}"><i class="fas fa-chart-line"></i></a></h2>
		<div class="row">
			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h3 class="dashboardCategoryLabel">{translate text="Page Views" isAdminFacing=true} <a href="/Admin/UsageGraphs?stat=pageViews&instance={$selectedInstance}" title="{translate text="Show Active Users Graph" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chart-line"></i></a></h3>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageThisMonth.totalViews|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageLastMonth.totalViews|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageThisYear.totalViews|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageAllTime.totalViews|number_format}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h3 class="dashboardCategoryLabel">{translate text="Page Views By Authenticated Users" isAdminFacing=true} <a href="/Admin/UsageGraphs?stat=authenticatedPageViews&instance={$selectedInstance}" title="{translate text="Logged In Page Views Graph" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chart-line"></i></a></h3>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageThisMonth.totalPageViewsByAuthenticatedUsers|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageLastMonth.totalPageViewsByAuthenticatedUsers|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageThisYear.totalPageViewsByAuthenticatedUsers|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageAllTime.totalPageViewsByAuthenticatedUsers|number_format}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h3 class="dashboardCategoryLabel">{translate text="Sessions Started" isAdminFacing=true} <a href="/Admin/UsageGraphs?stat=sessionsStarted&instance={$selectedInstance}" title="{translate text="Logged In Page Views Graph" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chart-line"></i></a></h3>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageThisMonth.totalSessionsStarted|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageLastMonth.totalSessionsStarted|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageThisYear.totalSessionsStarted|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageAllTime.totalSessionsStarted|number_format}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h3 class="dashboardCategoryLabel">{translate text="Page Views By Bots" isAdminFacing=true} <a href="/Admin/UsageGraphs?stat=pageViewsByBots&instance={$selectedInstance}" title="{translate text="Page Views By Bots Graph" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chart-line"></i></a></h3>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageThisMonth.totalPageViewsByBots|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageLastMonth.totalPageViewsByBots|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageThisYear.totalPageViewsByBots|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageAllTime.totalPageViewsByBots|number_format}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h3 class="dashboardCategoryLabel">{translate text="Asynchronous Requests" isAdminFacing=true} <a href="/Admin/UsageGraphs?stat=asyncRequests&instance={$selectedInstance}" title="{translate text="Show Active Users Graph" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chart-line"></i></a></h3>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageThisMonth.totalAsyncRequests|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageLastMonth.totalAsyncRequests|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageThisYear.totalAsyncRequests|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageAllTime.totalAsyncRequests|number_format}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h3 class="dashboardCategoryLabel">{translate text="Covers Requested" isAdminFacing=true} <a href="/Admin/UsageGraphs?stat=coversRequested&instance={$selectedInstance}" title="{translate text="Show Covers Requested Graph" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chart-line"></i></a></h3>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageThisMonth.totalCovers|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageLastMonth.totalCovers|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageThisYear.totalCovers|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageAllTime.totalCovers|number_format}</div>
					</div>
				</div>
			</div>
		</div>

		<h2>{translate text="Searches" isAdminFacing=true} <a href="/Admin/UsageGraphs?stat=searches&instance={$selectedInstance}" title="{translate text="Show Searches Graph" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chart-line"></i></a></h2>
		<div class="row">
			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h3 class="dashboardCategoryLabel">{translate text="Grouped Work Searches" isAdminFacing=true} <a href="/Admin/UsageGraphs?stat=groupedWorksSearches&instance={$selectedInstance}" title="{translate text="Show Grouped Works Searches Graph" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chart-line"></i></a></h3>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageThisMonth.totalGroupedWorkSearches|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageLastMonth.totalGroupedWorkSearches|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageThisYear.totalGroupedWorkSearches|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageAllTime.totalGroupedWorkSearches|number_format}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h3 class="dashboardCategoryLabel">{translate text="User List Searches" isAdminFacing=true} <a href="/Admin/UsageGraphs?stat=listSearches&instance={$selectedInstance}" title="{translate text="Show List Searches Graph" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chart-line"></i></a></h3>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageThisMonth.totalUserListSearches|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageLastMonth.totalUserListSearches|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageThisYear.totalUserListSearches|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageAllTime.totalUserListSearches|number_format}</div>
					</div>
				</div>
			</div>

			{if array_key_exists('EBSCO EDS', $enabledModules)}
				<div class="dashboardCategory col-sm-6">
					<div class="row">
						<div class="col-sm-10 col-sm-offset-1">
							<h3 class="dashboardCategoryLabel">{translate text="EBSCO EDS Searches" isAdminFacing=true} <a href="/Admin/UsageGraphs?stat=edsSearches&instance={$selectedInstance}" title="{translate text="Show EDS Searches Graph" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chart-line"></i></a></h3>
						</div>
					</div>
					<div class="row">
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
							<div class="dashboardValue">{$aspenUsageThisMonth.totalEbscoEdsSearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
							<div class="dashboardValue">{$aspenUsageLastMonth.totalEbscoEdsSearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
							<div class="dashboardValue">{$aspenUsageThisYear.totalEbscoEdsSearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
							<div class="dashboardValue">{$aspenUsageAllTime.totalEbscoEdsSearches|number_format}</div>
						</div>
					</div>
				</div>
			{/if}

			{if array_key_exists('Events', $enabledModules)}
				<div class="dashboardCategory col-sm-6">
					<div class="row">
						<div class="col-sm-10 col-sm-offset-1">
							<h3 class="dashboardCategoryLabel">{translate text="Events Searches" isAdminFacing=true} <a href="/Admin/UsageGraphs?stat=eventSearches&instance={$selectedInstance}" title="{translate text="Show Event Searches Graph" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chart-line"></i></a></h3>
						</div>
					</div>
					<div class="row">
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
							<div class="dashboardValue">{$aspenUsageThisMonth.totalEventsSearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
							<div class="dashboardValue">{$aspenUsageLastMonth.totalEventsSearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
							<div class="dashboardValue">{$aspenUsageThisYear.totalEventsSearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
							<div class="dashboardValue">{$aspenUsageAllTime.totalEventsSearches|number_format}</div>
						</div>
					</div>
				</div>
			{/if}

			{if $enableGenealogy}
				<div class="dashboardCategory col-sm-6">
					<div class="row">
						<div class="col-sm-10 col-sm-offset-1">
							<h3 class="dashboardCategoryLabel">{translate text="Genealogy Searches" isAdminFacing=true} <a href="/Admin/UsageGraphs?stat=genealogySearches&instance={$selectedInstance}" title="{translate text="Show Genealogy Searches Graph" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chart-line"></i></a></h3>
						</div>
					</div>
					<div class="row">
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
							<div class="dashboardValue">{$aspenUsageThisMonth.totalGenealogySearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
							<div class="dashboardValue">{$aspenUsageLastMonth.totalGenealogySearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
							<div class="dashboardValue">{$aspenUsageThisYear.totalGenealogySearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
							<div class="dashboardValue">{$aspenUsageAllTime.totalGenealogySearches|number_format}</div>
						</div>
					</div>
				</div>
			{/if}

			{if array_key_exists('Open Archives', $enabledModules)}
				<div class="dashboardCategory col-sm-6">
					<div class="row">
						<div class="col-sm-10 col-sm-offset-1">
							<h3 class="dashboardCategoryLabel">{translate text="Open Archives Searches" isAdminFacing=true} <a href="/Admin/UsageGraphs?stat=openArchivesSearches&instance={$selectedInstance}" title="{translate text="Show Open Archives Searches Graph" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chart-line"></i></a></h3>
						</div>
					</div>
					<div class="row">
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
							<div class="dashboardValue">{$aspenUsageThisMonth.totalOpenArchivesSearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
							<div class="dashboardValue">{$aspenUsageLastMonth.totalOpenArchivesSearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
							<div class="dashboardValue">{$aspenUsageThisYear.totalOpenArchivesSearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
							<div class="dashboardValue">{$aspenUsageAllTime.totalOpenArchivesSearches|number_format}</div>
						</div>
					</div>
				</div>
			{/if}

			{if array_key_exists('Web Indexer', $enabledModules)}
				<div class="dashboardCategory col-sm-6">
					<div class="row">
						<div class="col-sm-10 col-sm-offset-1">
							<h3 class="dashboardCategoryLabel">{translate text="Website Searches" isAdminFacing=true} <a href="/Admin/UsageGraphs?stat=websiteSearches&instance={$selectedInstance}" title="{translate text="Show Website Searches Graph" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chart-line"></i></a></h3>
						</div>
					</div>
					<div class="row">
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
							<div class="dashboardValue">{$aspenUsageThisMonth.totalWebsiteSearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
							<div class="dashboardValue">{$aspenUsageLastMonth.totalWebsiteSearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
							<div class="dashboardValue">{$aspenUsageThisYear.totalWebsiteSearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
							<div class="dashboardValue">{$aspenUsageAllTime.totalWebsiteSearches|number_format}</div>
						</div>
					</div>
				</div>
			{/if}
		</div>

		<h2>{translate text="Exceptions" isAdminFacing=true} <a href="/Admin/UsageGraphs?stat=exceptionsReport&instance={$selectedInstance}" title="{translate text="Exceptions Report Graph" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chart-line"></i></a></h2>
		<div class="row">
			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h3 class="dashboardCategoryLabel">{translate text="Blocked Pages" isAdminFacing=true} <a href="/Admin/UsageGraphs?stat=blockedPages&instance={$selectedInstance}" title="{translate text="Blocked Pages Graph" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chart-line"></i></a></h3>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageThisMonth.totalBlockedRequests|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageLastMonth.totalBlockedRequests|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageThisYear.totalBlockedRequests|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageAllTime.totalBlockedRequests|number_format}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h3 class="dashboardCategoryLabel">{translate text="Blocked API Requests" isAdminFacing=true} <a href="/Admin/UsageGraphs?stat=blockedApiRequests&instance={$selectedInstance}" title="{translate text="Blocked API Graph" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chart-line"></i></a></h3>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageThisMonth.totalBlockedApiRequests|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageLastMonth.totalBlockedApiRequests|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageThisYear.totalBlockedApiRequests|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageAllTime.totalBlockedApiRequests|number_format}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h3 class="dashboardCategoryLabel">{translate text="Errors" isAdminFacing=true} <a href="/Admin/UsageGraphs?stat=errors&instance={$selectedInstance}" title="{translate text="Errors Graph" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chart-line"></i></a></h3>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageThisMonth.totalErrors|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageLastMonth.totalErrors|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageThisYear.totalErrors|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageAllTime.totalErrors|number_format}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h3 class="dashboardCategoryLabel">{translate text="Searches With Errors" isAdminFacing=true} <a href="/Admin/UsageGraphs?stat=searchesWithErrors&instance={$selectedInstance}" title="{translate text="Errors Graph" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chart-line"></i></a></h3>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageThisMonth.totalSearchesWithErrors|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageLastMonth.totalSearchesWithErrors|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageThisYear.totalSearchesWithErrors|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageAllTime.totalSearchesWithErrors|number_format}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h3 class="dashboardCategoryLabel">{translate text="Timed Out Searches" isAdminFacing=true} <a href="/Admin/UsageGraphs?stat=timedOutSearches&instance={$selectedInstance}" title="{translate text="Errors Graph" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chart-line"></i></a></h3>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageThisMonth.totalTimedOutSearches|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageLastMonth.totalTimedOutSearches|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageThisYear.totalTimedOutSearches|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageAllTime.totalTimedOutSearches|number_format}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h3 class="dashboardCategoryLabel">{translate text="Timed Out Searches Under High Load" isAdminFacing=true} <a href="/Admin/UsageGraphs?stat=timedOutSearchesWithHighLoad&instance={$selectedInstance}" title="{translate text="Errors Graph" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chart-line"></i></a></h3>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageThisMonth.totalTimedOutSearchesWithHighLoad|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageLastMonth.totalTimedOutSearchesWithHighLoad|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageThisYear.totalTimedOutSearchesWithHighLoad|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$aspenUsageAllTime.totalTimedOutSearchesWithHighLoad|number_format}</div>
					</div>
				</div>
			</div>
		</div>

		{if $webResourceUsage|@count > 0}
		<h2>{translate text="Web Resources" isAdminFacing=true}</h2>
			<div class="row">
		        {foreach from=$webResourceUsage item=resource name="webResourceLoop"}
			        <div class="dashboardCategory col-sm-6">
			            <h3 class="dashboardCategoryLabel">{$resource.name}</h3>
				        <div class="table-responsive">
									<table class="table table-striped table-condensed">
										<thead>
											<tr>
												<th></th>
												<th>{translate text="This Month" isAdminFacing=true}</th>
												<th>{translate text="Last Month" isAdminFacing=true}</th>
												<th>{translate text="This Year" isAdminFacing=true}</th>
												<th>{translate text="All Time" isAdminFacing=true}</th>
											</tr>
										</thead>
										<tbody>
											<tr>
												<th scope="row">{translate text="Views" isAdminFacing=true}</th>
												<td>{$resource.thisMonth.totalViews|number_format}</td>
												<td>{$resource.lastMonth.totalViews|number_format}</td>
												<td>{$resource.thisYear.totalViews|number_format}</td>
												<td>{$resource.allTime.totalViews|number_format}</td>
											</tr>
											<tr>
												<th scope="row">{translate text="Views by Authenticated Users" isAdminFacing=true}</th>
												<td>{$resource.thisMonth.totalPageViewsbyAuthenticatedUsers|number_format}</td>
												<td>{$resource.lastMonth.totalPageViewsbyAuthenticatedUsers|number_format}</td>
												<td>{$resource.thisYear.totalPageViewsbyAuthenticatedUsers|number_format}</td>
												<td>{$resource.allTime.totalPageViewsbyAuthenticatedUsers|number_format}</td>
											</tr>
											<tr>
												<th scope="row">{translate text="Views in Library" isAdminFacing=true}</th>
												<td>{$resource.thisMonth.totalPageViewsInLibrary|number_format}</td>
												<td>{$resource.lastMonth.totalPageViewsInLibrary|number_format}</td>
												<td>{$resource.thisYear.totalPageViewsInLibrary|number_format}</td>
												<td>{$resource.allTime.totalPageViewsInLibrary|number_format}</td>
											</tr>
										</tbody>
									</table>
								</div>
					</div>
		        {/foreach}
			</div>
        {/if}
	</div>
{/strip}