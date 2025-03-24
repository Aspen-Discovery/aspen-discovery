{strip}
	<div id="main-content" class="col-sm-12">
		<h1>{translate text="Admin View" isAdminFacing=true}</h1>
		{*Filtered Results*}
		<div>
			<label for="filterBy">Filter By:</label>
			<select id="filterBy" class="form-control-sm" style="margin-bottom: 3px;" onchange="toggleFilterOptions()">
				<option value="">Select Filter</option>
				<option value="campaign">Campaign</option>
				<option value="user">User</option>
			</select>
			<div id="campaignDropdown" style="display:none;">
				<select id="campaign_id" class="form-control-sm" style="margin-bottom: 3px;" onchange="AspenDiscovery.CommunityEngagement.filterDropdownOptions('campaign')">
					<option value="">All Campaigns</option>
					{foreach from=$campaigns item=$campaign}
						<option value="{$campaign->id}">{$campaign->name}</option>
					{/foreach}
				</select>
			</div>
			<div id="userDropdown" style="display:none;">
					<select id="user_id" class="form-control-sm" style="margin-bottom: 3px;" onchange="AspenDiscovery.CommunityEngagement.filterDropdownOptions('user')">
						<option value="">All Users</option>
						{foreach from=$users item=$user}
								<option value="{$user->id}">{$user->displayName}</option>
						{/foreach}
					</select>
			</div>
		</div>
		<div id="campaignsList">
			<div class="dashboardCategory row" style="border: 1px solid #3174AF;padding:0 10px 10px 10px; margin-bottom: 10px;">

				<div class="col-sm-12">
					<h2 class="dashboardCategoryLabel">{translate text="All Campaigns" isAdminFacing=true}</h2>
					{foreach from=$campaigns item=campaign}
						<div style="border-bottom: 2px solid #3174AF;padding: 10px; margin-bottom; 10px;">

							<h5 style="font-weight:bold;">
								<a href="/CommunityEngagement/CampaignTable?id={$campaign->id}">
									{translate text=$campaign->name isAdminFacing=true}
								</a>
							</h5>

							<div class="dashboardLabel">Number of Patrons Enrolled:</div>
							<div class="dashboardValue">{translate text=$campaign->currentEnrollments isAdminFacing=true}</div>

							<div class="dashboardLabel">Total Number of Enrollments:</div>
							<div class="dashboardValue">{translate text=$campaign->enrollmentCounter isAdminFacing=true}</div>

							<div class="dashboardLabel">Total Number of Unenrollments:</div>
							<div class="dashboardValue">{translate text=$campaign->unenrollmentCounter isAdminFacing=true}</div>

							<div class="dashboardLabel">Number of Users Who Have Completed the Campaign</div>
							<div class="dashboardValue">{translate text=$campaign->completedUsersCount isAdminFacing=true}</div>
						</div>
					{/foreach}
				</div>
			</div>  
		</div>

		{*Filtered Campaigns*}
		<div id="filteredCampaign">
		 
		</div>
	</div>
{/strip}
<script type="text/javascript">
	function toggleFilterOptions() {
		var filterBy = document.getElementById("filterBy").value;
		var campaignDropdown = document.getElementById("campaignDropdown");
		var userDropdown = document.getElementById("userDropdown");

		if (filterBy === "campaign") {
			campaignDropdown.style.display = "block";
			userDropdown.style.display = "none";
		} else if (filterBy === "user") {
			userDropdown.style.display = "block";
			campaignDropdown.style.display = "none";
		} else {
			campaignDropdown.style.display = "none";
			userDropdown.style.display = "none";
		}
	}
</script>