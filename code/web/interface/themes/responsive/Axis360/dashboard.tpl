{strip}
	<div id="main-content" class="col-sm-12">
		<h1>{translate text="Boundless Dashboard" isAdminFacing=true}</h1>
		{include file="Admin/selectInterfaceForm.tpl"}
		<div class="row">
			<div class="col-xs-12">
				<a href="/Axis360/UsageGraphs?stat=general&instance={$selectedInstance}" title="{translate text="Show Graph" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chart-line"></i> {translate text="View as graph" isAdminFacing=true}</a>
			</div>
		</div>
		<div class="row">
			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h2 class="dashboardCategoryLabel">{translate text="Active Users" isAdminFacing=true}
							{' '}
							<a href="/Axis360/UsageGraphs?stat=activeUsers&instance={$selectedInstance}" title="{translate text="Show Active Users Graph" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chart-line"></i></a>
						</h2>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$activeUsersThisMonth|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$activeUsersLastMonth|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$activeUsersThisYear|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$activeUsersAllTime|number_format}</div>
					</div>
				</div>
			</div>
	
			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h2 class="dashboardCategoryLabel">{translate text="Records With Usage" isAdminFacing=true}
							{' '}
							<a href="/Axis360/UsageGraphs?stat=recordsWithUsage&instance={$selectedInstance}" title="{translate text="Show Records With Usage Graph" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chart-line"></i></a>
						</h2>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$activeRecordsThisMonth|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$activeRecordsLastMonth|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$activeRecordsThisYear|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$activeRecordsAllTime|number_format}</div>
					</div>
				</div>
			</div>
	
			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h2 class="dashboardCategoryLabel">{translate text="Loans" isAdminFacing=true}
							{' '}
							<a href="/Axis360/UsageGraphs?stat=loans&instance={$selectedInstance}" title="{translate text="Show Loans Graph" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chart-line"></i></a>
						</h2>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$loansThisMonth|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$loansLastMonth|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$loansThisYear|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$loansAllTime|number_format}</div>
					</div>
				</div>
			</div>
	
			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h2 class="dashboardCategoryLabel">{translate text="Holds" isAdminFacing=true}
							{' '}
							<a href="/Axis360/UsageGraphs?stat=holds&instance={$selectedInstance}" title="{translate text="Show Holds Graph" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chart-line"></i></a>
						</h2>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$holdsThisMonth|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$holdsLastMonth|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$holdsThisYear|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$holdsAllTime|number_format}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h2 class="dashboardCategoryLabel">{translate text="Renewals" isAdminFacing=true}
							{' '}
							<a href="/Axis360/UsageGraphs?stat=renewals&instance={$selectedInstance}" title="{translate text="Show Renewals Graph" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chart-line"></i></a>
						</h2>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$statsThisMonth->numRenewals|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$statsLastMonth->numRenewals|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$statsThisYear->numRenewals|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$statsAllTime->numRenewals|number_format}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h2 class="dashboardCategoryLabel">{translate text="Early Returns" isAdminFacing=true}
							{' '}
							<a href="/Axis360/UsageGraphs?stat=earlyReturns&instance={$selectedInstance}" title="{translate text="Show Early Returns Graph" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chart-line"></i></a>
						</h2>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$statsThisMonth->numEarlyReturns|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$statsLastMonth->numEarlyReturns|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$statsThisYear->numEarlyReturns|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$statsAllTime->numEarlyReturns|number_format}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h2 class="dashboardCategoryLabel">{translate text="Holds Cancelled" isAdminFacing=true}
							{' '}
							<a href="/Axis360/UsageGraphs?stat=holdsCancelled&instance={$selectedInstance}" title="{translate text="Show Holds Cancelled Graph" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chart-line"></i></a>
						</h2>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$statsThisMonth->numHoldsCancelled|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$statsLastMonth->numHoldsCancelled|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$statsThisYear->numHoldsCancelled|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$statsAllTime->numHoldsCancelled|number_format}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h2 class="dashboardCategoryLabel">{translate text="Holds Frozen" isAdminFacing=true}
							{' '}
							<a href="/Axis360/UsageGraphs?stat=holdsFrozen&instance={$selectedInstance}" title="{translate text="Show Holds Frozen Graph" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chart-line"></i></a>
						</h2>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$statsThisMonth->numHoldsFrozen|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$statsLastMonth->numHoldsFrozen|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$statsThisYear->numHoldsFrozen|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$statsAllTime->numHoldsFrozen|number_format}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h2 class="dashboardCategoryLabel">{translate text="Holds Thawed" isAdminFacing=true}
							{' '}
							<a href="/Axis360/UsageGraphs?stat=holdsThawed&instance={$selectedInstance}" title="{translate text="Show Holds Thawed Graph" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chart-line"></i></a>
						</h2>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$statsThisMonth->numHoldsThawed|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$statsLastMonth->numHoldsThawed|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$statsThisYear->numHoldsThawed|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$statsAllTime->numHoldsThawed|number_format}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h2 class="dashboardCategoryLabel">{translate text="API Errors" isAdminFacing=true}
							{' '}
							<a href="/Axis360/UsageGraphs?stat=apiErrors&instance={$selectedInstance}" title="{translate text="Show Records With Usage Graph" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chart-line"></i></a>
						</h2>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$statsThisMonth->numApiErrors|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$statsLastMonth->numApiErrors|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$statsThisYear->numApiErrors|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$statsAllTime->numApiErrors|number_format}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h2 class="dashboardCategoryLabel">{translate text="Connection Failures" isAdminFacing=true}
							{' '}
							<a href="/Axis360/UsageGraphs?stat=connectionFailures&instance={$selectedInstance}" title="{translate text="Show Connection Failures Graph" inAttribute="true" isAdminFacing=true}"><i class="fas fa-chart-line"></i></a>
						</h2>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$statsThisMonth->numConnectionFailures|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$statsLastMonth->numConnectionFailures|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$statsThisYear->numConnectionFailures|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$statsAllTime->numConnectionFailures|number_format}</div>
					</div>
				</div>
			</div>
		</div>
	</div>
{/strip}