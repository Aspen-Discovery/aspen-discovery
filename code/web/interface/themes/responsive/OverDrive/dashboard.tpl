{strip}
	<div id="main-content" class="col-sm-12">
		<h3>OverDrive Dashboard</h3>
		<h4 style="text-align: center">Active Users</h4>
		<div class="row">
			<div class="col-md-4">
				<h5 style="text-align: center">This Month</h5>
				<div style="text-align: center;width: 100%">{$activeUsersThisMonth}</div>
			</div>
			<div class="col-md-4">
				<h5 style="text-align: center">This Year</h5>
				<div style="text-align: center;width: 100%">{$activeUsersThisYear}</div>
			</div>
			<div class="col-md-4">
				<h5 style="text-align: center">All Time</h5>
				<div style="text-align: center;width: 100%">{$activeUsersAllTime}</div>
			</div>
		</div>

		<h4 style="text-align: center">Records With Usage</h4>
		<div class="row">
			<div class="col-md-4">
				<h5 style="text-align: center">This Month</h5>
				<div style="text-align: center;width: 100%">{$activeRecordsThisMonth}</div>
			</div>
			<div class="col-md-4">
				<h5 style="text-align: center">This Year</h5>
				<div style="text-align: center;width: 100%">{$activeRecordsThisYear}</div>
			</div>
			<div class="col-md-4">
				<h5 style="text-align: center">All Time</h5>
				<div style="text-align: center;width: 100%">{$activeRecordsAllTime}</div>
			</div>
		</div>

		<h4 style="text-align: center">Circulation Loans / Holds</h4>
		<div class="row">
			<div class="col-md-4">
				<h5 style="text-align: center">This Month</h5>
				<div style="text-align: center;width: 100%">{$loansThisMonth} / {$holdsThisMonth}</div>
			</div>
			<div class="col-md-4">
				<h5 style="text-align: center">This Year</h5>
				<div style="text-align: center;width: 100%">{$loansThisYear} / {$holdsThisYear}</div>
			</div>
			<div class="col-md-4">
				<h5 style="text-align: center">All Time</h5>
				<div style="text-align: center;width: 100%">{$loansAllTime} / {$holdsAllTime}</div>
			</div>
		</div>
	</div>
{/strip}