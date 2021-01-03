{strip}
	<div id="main-content" class="col-sm-12">
		<h1>EBSCO EDS Dashboard</h1>
		{include file="Admin/selectInterfaceForm.tpl"}
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
						<div class="dashboardValue">{$activeUsersThisMonth}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month"}</div>
						<div class="dashboardValue">{$activeUsersLastMonth}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year"}</div>
						<div class="dashboardValue">{$activeUsersThisYear}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time"}</div>
						<div class="dashboardValue">{$activeUsersAllTime}</div>
					</div>
				</div>
			</div>
	
			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h2 class="dashboardCategoryLabel">Number of Records Viewed</h2>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month"}</div>
						<div class="dashboardValue">{$thisMonthStats.numRecordsViewed}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month"}</div>
						<div class="dashboardValue">{$lastMonthStats.numRecordsViewed}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year"}</div>
						<div class="dashboardValue">{$thisYearStats.numRecordsViewed}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time"}</div>
						<div class="dashboardValue">{$allTimeStats.numRecordsViewed}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h2 class="dashboardCategoryLabel">Number of Records Clicked</h2>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month"}</div>
						<div class="dashboardValue">{$thisMonthStats.numRecordsUsed}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month"}</div>
						<div class="dashboardValue">{$lastMonthStats.numRecordsUsed}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year"}</div>
						<div class="dashboardValue">{$thisYearStats.numRecordsUsed}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time"}</div>
						<div class="dashboardValue">{$allTimeStats.numRecordsUsed}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h2 class="dashboardCategoryLabel">Total Clicks</h2>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month"}</div>
						<div class="dashboardValue">{$thisMonthStats.numClicks}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month"}</div>
						<div class="dashboardValue">{$lastMonthStats.numClicks}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year"}</div>
						<div class="dashboardValue">{$thisYearStats.numClicks}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time"}</div>
						<div class="dashboardValue">{$allTimeStats.numClicks}</div>
					</div>
				</div>
			</div>
		</div>
	</div>
{/strip}