{strip}
	<div id="main-content" class="col-sm-12">
		<h1>Open Archives Dashboard</h1>
		{foreach from=$collections item=collectionName key=collectionId}
			<h2>{$collectionName}</h2>

			<div class="dashboardCategory">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h3 class="dashboardCategoryLabel">Records Viewed</h3>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-4">
						<div class="dashboardLabel">This Month</div>
						<div class="dashboardValue">{$activeRecordsThisMonth.$collectionId.numRecordViewed}</div>
					</div>
					<div class="col-tn-4">
						<div class="dashboardLabel">This Year</div>
						<div class="dashboardValue">{$activeRecordsThisYear.$collectionId.numRecordViewed}</div>
					</div>
					<div class="col-tn-4">
						<div class="dashboardLabel">All Time</div>
						<div class="dashboardValue">{$activeRecordsAllTime.$collectionId.numRecordViewed}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h3 class="dashboardCategoryLabel">Records Used (clicked on)</h3>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-4">
						<div class="dashboardLabel">This Month</div>
						<div class="dashboardValue">{$activeRecordsThisMonth.$collectionId.numRecordsUsed}</div>
					</div>
					<div class="col-tn-4">
						<div class="dashboardLabel">This Year</div>
						<div class="dashboardValue">{$activeRecordsThisYear.$collectionId.numRecordsUsed}</div>
					</div>
					<div class="col-tn-4">
						<div class="dashboardLabel">All Time</div>
						<div class="dashboardValue">{$activeRecordsAllTime.$collectionId.numRecordsUsed}</div>
					</div>
				</div>
			</div>

			<div class="dashboardCategory">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h3 class="dashboardCategoryLabel">Active Users</h3>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-4">
						<div class="dashboardLabel">This Month</div>
						<div class="dashboardValue">{$activeUsersThisMonth.$collectionId}</div>
					</div>
					<div class="col-tn-4">
						<div class="dashboardLabel">This Year</div>
						<div class="dashboardValue">{$activeUsersThisYear.$collectionId}</div>
					</div>
					<div class="col-tn-4">
						<div class="dashboardLabel">All Time</div>
						<div class="dashboardValue">{$activeUsersAllTime.$collectionId}</div>
					</div>
				</div>
			</div>
		{/foreach}
	</div>
{/strip}