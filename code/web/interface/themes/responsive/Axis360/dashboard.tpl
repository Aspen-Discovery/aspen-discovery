{strip}
	<div id="main-content" class="col-sm-12">
		<h1>{translate text="Axis 360 Dashboard"}</h1>
		{include file="Admin/selectInterfaceForm.tpl"}
		<div class="row">
			<div class="col-xs-12">
				<a href="/Axis360/Graphs?instance={$selectedInstance}" title="{translate text="Show Graph" inAttribute="true"}"><i class="fas fa-chart-line"></i> {translate text="View as graph"}</a>
			</div>
		</div>
		<div class="row">
			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h2 class="dashboardCategoryLabel">{translate text="Active Users"}</h2>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month"}</div>
						<div class="dashboardValue">{$activeUsersThisMonth|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month"}</div>
						<div class="dashboardValue">{$activeUsersLastMonth|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year"}</div>
						<div class="dashboardValue">{$activeUsersThisYear|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time"}</div>
						<div class="dashboardValue">{$activeUsersAllTime|number_format}</div>
					</div>
				</div>
			</div>
	
			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h2 class="dashboardCategoryLabel">{translate text="Records With Usage"}</h2>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month"}</div>
						<div class="dashboardValue">{$activeRecordsThisMonth|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month"}</div>
						<div class="dashboardValue">{$activeRecordsLastMonth|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year"}</div>
						<div class="dashboardValue">{$activeRecordsThisYear|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time"}</div>
						<div class="dashboardValue">{$activeRecordsAllTime|number_format}</div>
					</div>
				</div>
			</div>
	
			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h2 class="dashboardCategoryLabel">{translate text="Loans"}</h2>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month"}</div>
						<div class="dashboardValue">{$loansThisMonth|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month"}</div>
						<div class="dashboardValue">{$loansLastMonth|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year"}</div>
						<div class="dashboardValue">{$loansThisYear|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time"}</div>
						<div class="dashboardValue">{$loansAllTime|number_format}</div>
					</div>
				</div>
			</div>
	
			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h2 class="dashboardCategoryLabel">{translate text="Holds"}</h2>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month"}</div>
						<div class="dashboardValue">{$holdsThisMonth|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month"}</div>
						<div class="dashboardValue">{$holdsLastMonth|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year"}</div>
						<div class="dashboardValue">{$holdsThisYear|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time"}</div>
						<div class="dashboardValue">{$holdsAllTime|number_format}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h2 class="dashboardCategoryLabel">{translate text="Renewals"}</h2>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month"}</div>
						<div class="dashboardValue">{$statsThisMonth->numRenewals|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month"}</div>
						<div class="dashboardValue">{$statsLastMonth->numRenewals|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year"}</div>
						<div class="dashboardValue">{$statsThisYear->numRenewals|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time"}</div>
						<div class="dashboardValue">{$statsAllTime->numRenewals|number_format}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h2 class="dashboardCategoryLabel">{translate text="Early Returns"}</h2>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month"}</div>
						<div class="dashboardValue">{$statsThisMonth->numEarlyReturns|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month"}</div>
						<div class="dashboardValue">{$statsLastMonth->numEarlyReturns|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year"}</div>
						<div class="dashboardValue">{$statsThisYear->numEarlyReturns|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time"}</div>
						<div class="dashboardValue">{$statsAllTime->numEarlyReturns|number_format}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h2 class="dashboardCategoryLabel">{translate text="Holds Cancelled"}</h2>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month"}</div>
						<div class="dashboardValue">{$statsThisMonth->numHoldsCancelled|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month"}</div>
						<div class="dashboardValue">{$statsLastMonth->numHoldsCancelled|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year"}</div>
						<div class="dashboardValue">{$statsThisYear->numHoldsCancelled|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time"}</div>
						<div class="dashboardValue">{$statsAllTime->numHoldsCancelled|number_format}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h2 class="dashboardCategoryLabel">{translate text="Holds Frozen"}</h2>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month"}</div>
						<div class="dashboardValue">{$statsThisMonth->numHoldsFrozen|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month"}</div>
						<div class="dashboardValue">{$statsLastMonth->numHoldsFrozen|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year"}</div>
						<div class="dashboardValue">{$statsThisYear->numHoldsFrozen|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time"}</div>
						<div class="dashboardValue">{$statsAllTime->numHoldsFrozen|number_format}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h2 class="dashboardCategoryLabel">{translate text="Holds Thawed"}</h2>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month"}</div>
						<div class="dashboardValue">{$statsThisMonth->numHoldsThawed|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month"}</div>
						<div class="dashboardValue">{$statsLastMonth->numHoldsThawed|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year"}</div>
						<div class="dashboardValue">{$statsThisYear->numHoldsThawed|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time"}</div>
						<div class="dashboardValue">{$statsAllTime->numHoldsThawed|number_format}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h2 class="dashboardCategoryLabel">{translate text="API Errors"}</h2>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month"}</div>
						<div class="dashboardValue">{$statsThisMonth->numApiErrors|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month"}</div>
						<div class="dashboardValue">{$statsLastMonth->numApiErrors|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year"}</div>
						<div class="dashboardValue">{$statsThisYear->numApiErrors|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time"}</div>
						<div class="dashboardValue">{$statsAllTime->numApiErrors|number_format}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h2 class="dashboardCategoryLabel">{translate text="Connection Failures"}</h2>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month"}</div>
						<div class="dashboardValue">{$statsThisMonth->numConnectionFailures|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month"}</div>
						<div class="dashboardValue">{$statsLastMonth->numConnectionFailures|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year"}</div>
						<div class="dashboardValue">{$statsThisYear->numConnectionFailures|number_format}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time"}</div>
						<div class="dashboardValue">{$statsAllTime->numConnectionFailures|number_format}</div>
					</div>
				</div>
			</div>
		</div>
	</div>
{/strip}