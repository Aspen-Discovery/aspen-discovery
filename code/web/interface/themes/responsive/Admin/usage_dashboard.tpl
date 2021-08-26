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
						<div class="dashboardValue">{$usageThisMonth.totalViews|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$usageLastMonth.totalViews|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$usageThisYear.totalViews|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$usageAllTime.totalViews|number_format}</div>
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
						<div class="dashboardValue">{$usageThisMonth.totalPageViewsByAuthenticatedUsers|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$usageLastMonth.totalPageViewsByAuthenticatedUsers|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$usageThisYear.totalPageViewsByAuthenticatedUsers|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$usageAllTime.totalPageViewsByAuthenticatedUsers|number_format}</div>
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
						<div class="dashboardValue">{$usageThisMonth.totalSessionsStarted|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$usageLastMonth.totalSessionsStarted|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$usageThisYear.totalSessionsStarted|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$usageAllTime.totalSessionsStarted|number_format}</div>
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
						<div class="dashboardValue">{$usageThisMonth.totalPageViewsByBots|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$usageLastMonth.totalPageViewsByBots|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$usageThisYear.totalPageViewsByBots|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$usageAllTime.totalPageViewsByBots|number_format}</div>
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
						<div class="dashboardValue">{$usageThisMonth.totalAsyncRequests|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$usageLastMonth.totalAsyncRequests|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$usageThisYear.totalAsyncRequests|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$usageAllTime.totalAsyncRequests|number_format}</div>
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
						<div class="dashboardValue">{$usageThisMonth.totalCovers|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$usageLastMonth.totalCovers|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$usageThisYear.totalCovers|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$usageAllTime.totalCovers|number_format}</div>
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
						<div class="dashboardValue">{$usageThisMonth.totalGroupedWorkSearches|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$usageLastMonth.totalGroupedWorkSearches|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$usageThisYear.totalGroupedWorkSearches|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$usageAllTime.totalGroupedWorkSearches|number_format}</div>
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
						<div class="dashboardValue">{$usageThisMonth.totalUserListSearches|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$usageLastMonth.totalUserListSearches|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$usageThisYear.totalUserListSearches|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$usageAllTime.totalUserListSearches|number_format}</div>
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
							<div class="dashboardValue">{$usageThisMonth.totalEbscoEdsSearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
							<div class="dashboardValue">{$usageLastMonth.totalEbscoEdsSearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
							<div class="dashboardValue">{$usageThisYear.totalEbscoEdsSearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
							<div class="dashboardValue">{$usageAllTime.totalEbscoEdsSearches|number_format}</div>
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
							<div class="dashboardValue">{$usageThisMonth.totalEventsSearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
							<div class="dashboardValue">{$usageLastMonth.totalEventsSearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
							<div class="dashboardValue">{$usageThisYear.totalEventsSearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
							<div class="dashboardValue">{$usageAllTime.totalEventsSearches|number_format}</div>
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
							<div class="dashboardValue">{$usageThisMonth.totalGenealogySearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
							<div class="dashboardValue">{$usageLastMonth.totalGenealogySearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
							<div class="dashboardValue">{$usageThisYear.totalGenealogySearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
							<div class="dashboardValue">{$usageAllTime.totalGenealogySearches|number_format}</div>
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
							<div class="dashboardValue">{$usageThisMonth.totalOpenArchivesSearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
							<div class="dashboardValue">{$usageLastMonth.totalOpenArchivesSearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
							<div class="dashboardValue">{$usageThisYear.totalOpenArchivesSearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
							<div class="dashboardValue">{$usageAllTime.totalOpenArchivesSearches|number_format}</div>
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
							<div class="dashboardValue">{$usageThisMonth.totalWebsiteSearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
							<div class="dashboardValue">{$usageLastMonth.totalWebsiteSearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
							<div class="dashboardValue">{$usageThisYear.totalWebsiteSearches|number_format}</div>
						</div>
						<div class="col-tn-6">
							<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
							<div class="dashboardValue">{$usageAllTime.totalWebsiteSearches|number_format}</div>
						</div>
					</div>
				</div>
			{/if}
		</div>

		<h2>{translate text="Exceptions" isAdminFacing=true} <a href="/Admin/UsageGraphs?stat=exceptionsReport&instance={$selectedInstance}" title="{translate text="Exceptions Report Graph" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chart-line"></i></a></h2>
		<div class="dashboardCategory col-sm-6">
			<div class="row">
				<div class="col-sm-10 col-sm-offset-1">
					<h3 class="dashboardCategoryLabel">{translate text="Blocked Pages" isAdminFacing=true} <a href="/Admin/UsageGraphs?stat=blockedPages&instance={$selectedInstance}" title="{translate text="Blocked Pages Graph" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chart-line"></i></a></h3>
				</div>
			</div>
			<div class="row">
				<div class="col-tn-6">
					<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
					<div class="dashboardValue">{$usageThisMonth.totalBlockedRequests|number_format}</div>
				</div>
				<div class="col-tn-6">
					<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
					<div class="dashboardValue">{$usageLastMonth.totalBlockedRequests|number_format}</div>
				</div>
				<div class="col-tn-6">
					<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
					<div class="dashboardValue">{$usageThisYear.totalBlockedRequests|number_format}</div>
				</div>
				<div class="col-tn-6">
					<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
					<div class="dashboardValue">{$usageAllTime.totalBlockedRequests|number_format}</div>
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
					<div class="dashboardValue">{$usageThisMonth.totalBlockedApiRequests|number_format}</div>
				</div>
				<div class="col-tn-6">
					<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
					<div class="dashboardValue">{$usageLastMonth.totalBlockedApiRequests|number_format}</div>
				</div>
				<div class="col-tn-6">
					<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
					<div class="dashboardValue">{$usageThisYear.totalBlockedApiRequests|number_format}</div>
				</div>
				<div class="col-tn-6">
					<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
					<div class="dashboardValue">{$usageAllTime.totalBlockedApiRequests|number_format}</div>
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
					<div class="dashboardValue">{$usageThisMonth.totalErrors|number_format}</div>
				</div>
				<div class="col-tn-6">
					<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
					<div class="dashboardValue">{$usageLastMonth.totalErrors|number_format}</div>
				</div>
				<div class="col-tn-6">
					<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
					<div class="dashboardValue">{$usageThisYear.totalErrors|number_format}</div>
				</div>
				<div class="col-tn-6">
					<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
					<div class="dashboardValue">{$usageAllTime.totalErrors|number_format}</div>
				</div>
			</div>
		</div>
	</div>
{/strip}