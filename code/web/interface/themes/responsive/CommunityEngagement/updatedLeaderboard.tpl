<!DOCTYPE html>
<head>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/grapesjs/0.21.10/css/grapes.min.css" />
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
	<script src="https://cdnjs.cloudflare.com/ajax/libs/grapesjs/0.21.10/grapes.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/grapesjs-blocks-basic@1.0.2/dist/index.min.js"></script>

</head>
<body>
    {strip}
		{if $userIsAdmin}
			<button id="editLeaderboardBtn" onclick="AspenDiscovery.CommunityEngagement.openLeaderboardEditor()">{translate text="Edit Leaderboard" isAdminFacing=true}</button>
			<button id="editLeaderboardBtn" onclick="AspenDiscovery.CommunityEngagement.resetLeaderboard()">{translate text="Reset Leaderboard" isAdminFacing=true}</button>
		{/if}<br><br>
			<select id="campaign_id" onchange="AspenDiscovery.CommunityEngagement.filterLeaderboardType()">
				<option value="">
					{translate text="All Campaigns" isPublicFacing=true}
				</option>
				{foreach from=$campaigns item=$campaign}
					<option value="{$campaign->id}">{$campaign->name}</option>
				{/foreach}
			</select>

        <div id="main-content">
			{*Filter Leaderboard by campaign*}
			<label for="campaignFilter">{translate text="Filter by Campaign:" isPublicFacing=true}</label>
		

            {$leaderboardHtml nofilter}
        </div>
		<div id="gjs" style="display: none;"></div>

    {/strip}
<script>
	document.addEventListener("DOMContentLoaded", function() {
	   
		AspenDiscovery.CommunityEngagement.filterLeaderboardType();
	})
</script>
</body>
<style>
    {$leaderboardCss}
</style>
</html>