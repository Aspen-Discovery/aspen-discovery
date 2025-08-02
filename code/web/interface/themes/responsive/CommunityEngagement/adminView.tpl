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
			{if $library->communityEngagementAdminUserSelect == 'dropdown'}
				<div id="userDropdown" style="display:none;">
						<select id="user_id" class="form-control-sm" style="margin-bottom: 3px;" onchange="AspenDiscovery.CommunityEngagement.filterDropdownOptions('user')">
							<option value="">-</option>
							{foreach from=$users item=$user}
									<option value="{$user.id}">{$user.displayName}</option>
							{/foreach}
						</select>
						<button class="btn btn-sm btn-primary" onclick="$('#addUserByBarcodeModal').modal('show')">Add User by Barcode</button>
				</div>
			{else}
				<div id="userDropdown" style="display:none;">
					<input type="text" 
						id="user_search" 
						class="form-control-sm" 
						style="margin-bottom: 3px;" 
						placeholder="Search users..." 
						data-toggle="tooltip"
						data-placement="top"
						title="Enter at least 2 characters to search"
						autocomplete="off"
						oninput="AspenDiscovery.CommunityEngagement.searchUsers(this.value)">
					
					<div id="user_search_results" class="search-results" style="display:none; position:absolute; background:white; border:1px solid #ccc; max-height:200px; overflow-y:auto; z-index:1000; min-width:250px; font-size:14px; padding:5px; font-size:20px;">
					</div>
					
					<input type="hidden" id="selected_user_id" value="">
					<button class="btn btn-sm btn-primary" onclick="$('#addUserByBarcodeModal').modal('show')">Add User by Barcode</button>
				</div>
			{/if}
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

<div class="modal fade" id="addUserByBarcodeModal" tabindex="-1" role="dialog" aria-labelledby="addUserByBarcodeModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content p-3">
      <div class="modal-header">
        <h5 class="modal-title" id="addUserByBarcodeModalLabel">Add User</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body" id="addUserByBarcodeModalBody">
        <input type="text" id="newUserBarcode" class="form-control" placeholder="Enter Barcode">
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" onclick="AspenDiscovery.CommunityEngagement.addUserByBarcode()">Add User</button>
      </div>
    </div>
  </div>
</div>


{/strip}
<script type="text/javascript">
	$(document).ready(function () {
		$('#addUserByBarcodeModal').on('show.bs.modal', function () {
			$('#newUserBarcode').val('');
			$('#addUserByBarcodeModalBody').html('<input type="text" id="newUserBarcode" class="form-control" placeholder="Enter Barcode">');

		});
	});

	document.addEventListener('click', function(e) {
		if (!e.target.closest('#userDropdown')) {
			const userSearchResults = document.getElementById('user_search_results');
			if (userSearchResults) {
				userSearchResults.style.display = 'none';
			}
		}
	});

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

		if (filterBy === "campaign" || filterBy === "user") {
			AspenDiscovery.CommunityEngagement.filterDropdownOptions(filterBy);
		}
	}
</script>