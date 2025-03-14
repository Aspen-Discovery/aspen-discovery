{strip}
<div class="main-content">
	<h1>{translate text="ILS Usage Dashboard" isAdminFacing=true}</h1>
	<h2>{translate text="Patron Engagement Report" isAdminFacing=true}</h2>
	<form method="get" action="" class="form-inline" id="campaignReportForm">
		<label for="campaign">Select Campaign:</label>
		<select name="campaign" id="campaign">
			<option value="">All Campaigns</option>
			{foreach from=$campaigns item=campaign}
				<option value="{$campaign->id}" {if $campaign->id == $selectedCampaignId}selected{/if}>{$campaign->name}</option>
			{/foreach}
		</select><br/>

		<label for="date_from">From:</label>
		<input type="date" name="date_from" id="date_from" placeholder="mm/dd/yyyy" class="form-control" size="5" value="{$selectedDateFrom|escape}">


		<label for="date_to">To:</label>
		<input type="date" name="date_to" id="date_to" placeholder="mm/dd/yyyy" class="form-control" size="5" value="{$selectedDateTo|escape}">



		<button type="submit" class="btn btn-primary" name="download_report" value="true">Download CSV</button>
	</form>
	<div>
		<h2>{translate text="Dashboard" isAdminFacing=true}</h2>
		<div class="row">
			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<a href="/CommunityEngagement/UsageGraphs?stat=enrollments" title="{translate text="Show Campaign Graph" inAttribute="true" isAdminFacing=true}"><h2 class="dashboardCategoryLabel">{translate text="Enrolled Users" isAdminFacing=true}&nbsp;<i class="fas fa-chart-line"></i></h2></a>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$enrolledUsersThisMonth}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$enrolledUsersLastMonth}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$enrolledUsersThisYear}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$enrolledUsersAllTime}</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
		{foreach from=$campaignStats key=campaignId item=campaignStat}
			<div class="dashboardCategory col-sm-6">
				<div class="row">
					<div class="col-sm-10 col-sm-offset-1">
						<h2 class="dashboardCategoryLabel">
						<a href="/CommunityEngagement/UsageGraphs?stat=enrollments&campaignId={$campaignStat.id}" title="{translate text="Show Campaign Graph" inAttribute="true" isAdminFacing=true}">
							{$campaignStat.campaignName}&nbsp;
							<i class="fas fa-chart-line"></i></h2>
						</a>
						</h2>
					</div>
				</div>
				<div class="row">
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$campaignStatsThisMonth[$campaignId].enrolledUsers}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="Last Month" isAdminFacing=true}</div>
						<div class="dashboardValue">{$campaignStatsLastMonth[$campaignId].enrolledUsers}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="This Year" isAdminFacing=true}</div>
						<div class="dashboardValue">{$campaignStatsThisYear[$campaignId].enrolledUsers}</div>
					</div>
					<div class="col-tn-6">
						<div class="dashboardLabel">{translate text="All Time" isAdminFacing=true}</div>
						<div class="dashboardValue">{$campaignStatsAllTime[$campaignId].enrolledUsers}</div>
					</div>
				</div>
			</div>
		{/foreach}
		</div>

		
	

	</div>

</div>
{/strip}