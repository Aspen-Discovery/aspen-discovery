<!DOCTYPE html>
<head>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/grapesjs/0.21.10/css/grapes.min.css" />
	<script src="https://cdnjs.cloudflare.com/ajax/libs/grapesjs/0.21.10/grapes.min.js"></script>
</head>
<body>
{strip}
		<div id="main-content" class="col-sm-12" data-leaderboard-type="{$campaignLeaderboardDisplay}">
			<h1>{translate text="Leaderboard" isPublicFacing=true}</h1>
			<button id="editLeaderboardBtn" onclick="AspenDiscovery.CommunityEngagement.openLeaderboardEditor()">{translate text="Edit Leaderboard" isAdminFacing=true}</button>
			<button id="saveLeaderboardBtn" style="display: none;" onclick="AspenDiscovery.CommunityEngagement.saveLeaderboardChanges()">{translate text="Save Changes" isAdminFacing=true}</button>
			<div id="gjs" style="display: none;"></div>
			{*Filter Leaderboard by campaign*}
			<label for="campaignFilter">{translate text="Filter by Campaign:" isPublicFacing=true}</label>
			<select id="campaign_id" onchange="AspenDiscovery.CommunityEngagement.filterLeaderboardType()">
				<option value="">
					{translate text="All Campaigns" isPublicFacing=true}
				</option>
				{foreach from=$campaigns item=$campaign}
					<option value="{$campaign->id}">{$campaign->name}</option>
				{/foreach}
			</select>
			<h2 id="campaign-name"></h2>
			<div id="leaderboard-table"></div>
		</div>
{/strip}
<script>
	document.addEventListener("DOMContentLoaded", function() {
	   
		AspenDiscovery.CommunityEngagement.filterLeaderboardType();
	})
</script>
</body>