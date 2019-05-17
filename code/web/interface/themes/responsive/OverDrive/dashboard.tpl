{strip}
	<div id="main-content" class="col-sm-12">
		<h3>OverDrive Dashboard</h3>
		<div class="dashboardCategory">
			<div class="row">
				<div class="col-sm-10 col-sm-offset-1">
					<h4 class="dashboardCategoryLabel">Active Users</h4>
				</div>
			</div>
			<div class="row">
				<div class="col-tn-4">
					<div class="dashboardLabel">This Month</div>
					<div class="dashboardValue">{$activeUsersThisMonth}</div>
				</div>
				<div class="col-tn-4">
					<div class="dashboardLabel">This Year</div>
					<div class="dashboardValue">{$activeUsersThisYear}</div>
				</div>
				<div class="col-tn-4">
					<div class="dashboardLabel">All Time</div>
					<div class="dashboardValue">{$activeUsersAllTime}</div>
				</div>
			</div>
		</div>

		<div class="dashboardCategory">
			<div class="row">
				<div class="col-sm-10 col-sm-offset-1">
					<h4 class="dashboardCategoryLabel">Records With Usage</h4>
				</div>
			</div>
			<div class="row">
				<div class="col-tn-4">
					<div class="dashboardLabel">This Month</div>
					<div class="dashboardValue">{$activeRecordsThisMonth}</div>
				</div>
				<div class="col-tn-4">
					<div class="dashboardLabel">This Year</div>
					<div class="dashboardValue">{$activeRecordsThisYear}</div>
				</div>
				<div class="col-tn-4">
					<div class="dashboardLabel">All Time</div>
					<div class="dashboardValue">{$activeRecordsAllTime}</div>
				</div>
			</div>
		</div>

		<div class="dashboardCategory">
			<div class="row">
				<div class="col-sm-10 col-sm-offset-1">
					<h4 class="dashboardCategoryLabel">Loans</h4>
				</div>
			</div>
			<div class="row">
				<div class="col-tn-4">
					<div class="dashboardLabel">This Month</div>
					<div class="dashboardValue">{$loansThisMonth}</div>
				</div>
				<div class="col-tn-4">
					<div class="dashboardLabel">This Year</div>
					<div class="dashboardValue">{$loansThisYear}</div>
				</div>
				<div class="col-tn-4">
					<div class="dashboardLabel">All Time</div>
					<div class="dashboardValue">{$loansAllTime}</div>
				</div>
			</div>
		</div>

		<div class="dashboardCategory">
			<div class="row">
				<div class="col-sm-10 col-sm-offset-1">
					<h4 class="dashboardCategoryLabel">Holds</h4>
				</div>
			</div>
			<div class="row">
				<div class="col-tn-4">
					<div class="dashboardLabel">This Month</div>
					<div class="dashboardValue">{$holdsThisMonth}</div>
				</div>
				<div class="col-tn-4">
					<div class="dashboardLabel">This Year</div>
					<div class="dashboardValue">{$holdsThisYear}</div>
				</div>
				<div class="col-tn-4">
					<div class="dashboardLabel">All Time</div>
					<div class="dashboardValue">{$holdsAllTime}</div>
				</div>
			</div>
		</div>
	</div>
{/strip}