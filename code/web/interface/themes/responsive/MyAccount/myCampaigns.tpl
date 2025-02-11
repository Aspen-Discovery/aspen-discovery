{strip}
    <h1>{translate text="Campaigns" isPublicFacing=true}</h1>
    {if empty($campaignList)}
        <div class="alert alert-info">
            {translate text="There are no available campaigns at the moment" isPublicFacing=true}
        </div>
    {else}
        {assign var="hasEnrolledCampaigns" value=false}
        {foreach from=$campaignList item="campaign" key="resultIndex"}
            {if $campaign->enrolled && ($campaign->isActive || $campaign->isUpcoming)}
                {assign var="hasEnrolledCampaigns" value=true}
                {break}
            {/if}
        {/foreach}
        {if $hasEnrolledCampaigns}
            <h2>{translate text="Your Campaigns" isPublicFacing=true}</h2>
            <table id="yourCampaignsTable" class="table table-striped">
                <thead>
                    <tr>
                        <th>{translate text="Campaign Name" isPublicFacing=true}</th>
                        <th>{translate text="Start Date" isPublicFacing=true}</th>
                        <th>{translate text="End Date" isPublicFacing=true}</th>
                        <th>{translate text="Campaign Reward" isPublicFacing=true}</th>
                        <th>{translate text="Milestones Completed" isPublicFacing=true}</th>
                    </tr>
                </thead>
                <tbody>
                {foreach from=$campaignList item="campaign" key="resultIndex"}
                    {if $campaign->enrolled && ($campaign->isActive || $campaign->isUpcoming)}
                        <tr>
                            <td>{$campaign->name}</td>
                            <td>{$campaign->startDate}</td>
                            <td>{$campaign->endDate}</td>
                            <td>
                                {$campaign->rewardName}
                                {if $campaign->rewardType == 1 && $campaign->rewardExists}
                                    <img src="{$campaign->badgeImage}" alt="{$campaign->rewardName}" style="max-width:100px; max-height:100px;" />
                                {/if}
                            </td>
                            <td>{$campaign->numCompletedMilestones} / {$campaign->numCampaignMilestones}</td>
                            <td>
                                <button class="btn btn-primary btn-sm" onclick="AspenDiscovery.Account.unenroll({$campaign->id}, {$userId});">{translate text="Unenroll" isPublicFacing=true}</button>
                            </td>
                            <td>
                                <button class="btn btn-primary btn-sm" onclick="toggleYourCampaignInfo({$resultIndex});">{translate text="Campaign Information" isPublicFacing=true}</button>
                            </td>
                        </tr>
                            {* <tr id="campaignInfo_{$resultIndex}" style="display:none;"> *}
                            <tr id="yourCampaigns_{$resultIndex}" class="campaign-dropdown" style="display:none;">
                                <td colspan="4">
                                    {* <h4>{translate text="Milestones"}</h4> *}
                                    <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>{translate text="Milestone" isPublicFacing=true}</th>
                                            <th>{translate text="Milestone Reward" isPublicFacing=true}</th>
                                            <th>{translate text="Progress Towards Milestone" isPublicFacing=true}</th>
                                            <th>{translate text="Progress Percentage" isPublicFacing=true}</th>
                                        </tr>
                                    </thead>
                                        <tbody>
                                        {foreach from=$campaign->milestones item="milestone"}
                                            <tr>
                                                <td>{$milestone->name}</td>
                                                <td>
                                                    {$milestone->rewardName}
                                                    {if $milestone->rewardType == 1 && $milestone->rewardExists}
                                                        <img src="{$milestone->rewardImage}" alt="{$milestone->rewardName}" style="max-width:100px; max-height:100px;" />
                                                    {/if}
                                                </td>
                                                <td>
                                                    {$milestone->completedGoals}/ {$milestone->totalGoals}
                                                    {foreach from=$milestone->progressData item="progressData"}
                                                    <div style="padding:10px;">
                                                        {if isset($progressData['title'])}
                                                            {$progressData['title']}
                                                        {/if}
                                                    </div>
                                                    {/foreach}
                                                </td>
                                                <td>
                                                    <div class="progress" style="width:100%; border:1px solid black; border-radius:4px;height:20px;">
                                                        <div class="progress-bar" role="progressbar" aria-valuenow="{$milestone->progress}" aria-valuemin="0"
                                                        aria-valuemax="100" style="width: {$milestone->progress}%; line-height: 20px; text-align: center; color: #fff;">
                                                            {$milestone->progress}%
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>                                 
                                        {/foreach}
                                        </tbody>
                                    </table>
                                </td>
                        </tr>
                    {/if}
                {/foreach}
                </tbody>
            </table>
        {/if}
                {if $hasLinkedUsers}
            <h2>{translate text="Linked Account Campaigns" isPublicFacing=true}</h2>
            {foreach from=$linkedCampaigns item="linkedUser"}
            <h3>{$linkedUser.linkedUserName}</h3>
            <table>
                <thead>
                    <tr>
                        <th>{translate text="Campaign Name" isPublicFacing=true}</th>
                        <th>{translate text="Start Date" isPublicFacing=true}</th>
                        <th>{translate text="End Date" isPublicFacing=true}</th>
                        <th>{translate text="Campaign Reward" isPublicFacing=true}</th>
                        <th>{translate text="Milestones Completed" isPublicFacing=true}</th>
                        <th>{translate text="Action" isPublicFacing=true}</th>
                        <th>{translate text="Campaign Information" isPublicFacing=true}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$linkedUser.campaigns item="campaign" key="resultIndex"}
                        <tr>
                            <td>{$campaign.campaignName}</td>
                            <td>{$campaign.startDate}</td>
                            <td>{$campaign.endDate}</td>
                            <td>
                               {$campaign.campaignReward.rewardName}
                               {if $campaign.campaignReward.rewardType === 1}
                                    <img src="{$campaign.reward.badgeImage}" alt="{$campaign.reward.rewardName}" width="100" height="100" />
                                {/if}
                            </td>
                            <td>{$campaign.numCompletedMilestones} / {$campaign.numCampaignMilestones}</td>
                            <td>
                                {if $campaign.isEnrolled}
                                    <button class="btn btn-primary btn-sm" onclick="AspenDiscovery.Account.unenroll({$campaign.campaignId}, {$linkedUser.linkedUserId});">{translate text="Unenroll" isPublicFacing=true}</button>
                                {else}
                                    <button class="btn btn-primary btn-sm" onclick="AspenDiscovery.Account.enroll({$campaign.campaignId}, {$linkedUser.linkedUserId});">{translate text="Enroll" isPublicFacing=true}</button>
                                {/if}
                            </td>
                            <td>
                                <button class="btn btn-primary btn-sm" onclick="toggleLinkedUserCampaignInfo('linkedUserCampaigns_{$resultIndex}');">{translate text="Campaign Information" isPublicFacing=true}</button>
                            </td>
                        </tr>
                        <tr id="linkedUserCampaigns_{$resultIndex}" class="campaign-dropdown" style="display:none;">
                            <td colspan="7">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>{translate text="Milestone" isPublicFacing=true}</th>
                                            <th>{translate text="Milestone Reward" isPublicFacing=true}</th>
                                            <th>{translate text="Progress Towards Milestone" isPublicFacing=true}</th>
                                            <th>{translate text="Progress Percentage" isPublicFacing=true}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    {foreach from=$campaign.milestones item="milestone"}
                                        <tr>
                                            <td>{$milestone.milestoneName}</td>
                                            <td>{$milestone.rewardName} 
                                                {if $milestone.rewardType === 1}
                                                    <img src="{$milestone.badgeImage}" alt="{$milestone.rewardName}" width="100" height="100" />
                                                {/if}
                                            </td>
                                            <td>{$milestone.completedGoals} / {$milestone.totalGoals}
                                            {foreach from=$milestone.progressData item="progressData"}
                                                <div style="padding:10px;">
                                                    {$progressData['title']}
                                                </div>
                                            {/foreach}
                                            </td>
                                            <td>
                                                <div class="progress" style="width:100%; border:1px solid black; border-radius:4px; height:20px;">
                                                    <div class="progress-bar" role="progressbar" aria-valuenow="{$milestone.progress}" aria-valuemin="0" aria-valuemax="100" style="width: {$milestone.progress}%; line-height: 20px; text-align: center; color: #fff;">
                                                        {$milestone.progress}%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    {/foreach}
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
            {/foreach}
        {/if}
        {assign var="hasActiveCampaigns" value=false}
        {foreach from=$campaignList item="campaign" key="resultIndex"}
            {if $campaign->isActive}
                {assign var="hasActiveCampaigns" value=true}
                {break}
            {/if}
        {/foreach}
        {if $hasActiveCampaigns}
            <h2>{translate text="Active Campaigns" isPublicFacing=true}</h2>
            <table id="activeCampaignsTable" class="table table-striped">
                <thead>
                    <tr>
                        <th>{translate text="Campaign Name" isPublicFacing=true}</th>
                        <th>{translate text="Campaign Reward" isPublicFacing=true}</th>
                        <th>{translate text="End Date" isPublicFacing=true}</th>
                        <th>{translate text="Enrollment" isPublicFacing=true}</th>
                    </tr>
                </thead>
                <tbody>
                {foreach from=$campaignList item="campaign" key="resultIndex"}
                    {if $campaign->isActive}
                        <tr>
                            <td>{$campaign->name}</td>
                            <td>
                                {$campaign->rewardName}
                                {if $campaign->rewardType == 1 && $campaign->rewardExists}
                                    <img src="{$campaign->badgeImage}" alt="{$campaign->rewardName}" style="max-width:100px; max-height:100px;" />
                                {/if}
                            </td>
                            <td>{$campaign->endDate}</td>
                            {if $campaign->enrolled}
                                <td>{translate text="Enrolled" isPublicFacing=true}</td>
                            {else}
                                <td>{translate text="Not Enrolled" isPublicFacing=true}</td>
                            {/if}
                            {if $campaign->enrolled}
                            <td>
                                <button class="btn btn-primary btn-sm" onclick="AspenDiscovery.Account.unenroll({$campaign->id}, {$userId});">{translate text="Unenroll" isPublicFacing=true}</button>
                            </td>
                            {else}
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="AspenDiscovery.Account.enroll({$campaign->id}, {$userId});">{translate text="Enroll" isPublicFacing=true}</button>
                                </td>
                            {/if}
                            <td>
                                <button class="btn btn-primary btn-sm" onclick="toggleActiveCampaignInfo({$resultIndex});">{translate text="Campaign Information" isPublicFacing=true}</button>
                            </td>
                        </tr>
                            {* <tr id="campaignInfo_{$resultIndex}" style="display:none;"> *}
                            <tr id="activeCampaigns_{$resultIndex}" class="campaign-dropdown" style="display:none;">

                                <td colspan="4">
                                    {* <h4>{translate text="Milestones"}</h4> *}
                                    <table class="table table-bordered">
                                    {$campaign->textBlockTranslationDescription}

                                    <thead>
                                        <tr>
                                            <th>{translate text="Milestone" isPublicFacing=true}</th>
                                            <th>{translate text="Milestone Reward" isPublicFacing=true}</th>
                                            <th>{translate text="Progress Towards Milestone" isPublicFacing=true}</th>
                                            <th>{translate text="Progress Percentage" isPublicFacing=true}</th>
                                        </tr>
                                    </thead>
                                        <tbody>
                                        {foreach from=$campaign->milestones item="milestone"}
                                            <tr>
                                                <td>{$milestone->name}</td>
                                                <td>
                                                    {$milestone->rewardName}
                                                    {if $milestone->rewardType == 1 && $milestone->rewardExists}
                                                        <img src="{$milestone->rewardImage}" alt="{$milestone->rewardName}" style="max-width:100px; max-height:100px;" />
                                                    {/if}
                                                </td>
                                                <td>
                                                    {$milestone->completedGoals}/ {$milestone->totalGoals}
                                                    {foreach from=$milestone->progressData item="progressData"}
                                                    <div style="padding:10px;">
                                                        {if isset($progressData['title'])}
                                                            {$progressData['title']}
                                                        {/if}
                                                    </div>
                                                    {/foreach}
                                                </td>
                                                <td>
                                                    <div class="progress" style="width:100%; border:1px solid black; border-radius:4px;height:20px;">
                                                        <div class="progress-bar" role="progressbar" aria-valuenow="{$milestone->progress}" aria-valuemin="0"
                                                        aria-valuemax="100" style="width: {$milestone->progress}%; line-height: 20px; text-align: center; color: #fff;">
                                                            {$milestone->progress}%
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>                                 
                                        {/foreach}
                                        </tbody>
                                    </table>
                                </td>
                        </tr>
                    {/if}
                {/foreach}
                </tbody>
            </table>
        {/if}
        {assign var="hasUpcomingCampaigns" value=false}
        {foreach from=$campaignList item="campaign" key="resultIndex"}
            {if $campaign->isUpcoming}
                {assign var="hasUpcomingCampaigns" value=true}
                {break}
            {/if}
        {/foreach}
        {if $hasUpcomingCampaigns}
            <h2>{translate text="Upcoming Campaigns" isPublicFacing=true}</h2>
            <table id ="upcomingCampaignsTable" class="table table-striped">
                <thead>
                    <tr>
                        <th>{translate text="Campaign Name" isPublicFacing=true}</th>
                        <th>{translate text="Campaign Reward" isPublicFacing=true}</th>
                        <th>{translate text="Start Date" isPublicFacing=true}</th>
                        <th>{translate text="Enrollment" isPublicFacing=true}</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
                {foreach from=$campaignList item="campaign" key="resultIndex"}
                    {if $campaign->isUpcoming}
                        <tr>
                            <td>{$campaign->name}</td>
                            <td>
                                {$campaign->rewardName}
                                {if $campaign->rewardType == 1 && $campaign->rewardExists}
                                    <img src="{$campaign->badgeImage}" alt="{$campaign->rewardName}" style="max-width:100px; max-height:100px;" />
                                {/if}
                            </td>
                            <td>{$campaign->startDate}</td>
                            {if $campaign->enrolled}
                                <td>{translate text="Enrolled" isPublicFacing=true}</td>
                            {else}
                                <td>{translate text="Not Enrolled" isPublicFacing=true}</td>
                            {/if}
                            {if $campaign->enrolled}
                                <td>
                                    <button class="btn btn-primary btn-sm" onclick="AspenDiscovery.Account.unenroll({$campaign->id}, {$userId});">{translate text="Unenroll" isPublicFacing=true}</button>
                                </td>
                                {else}
                                    <td>
                                        <button class="btn btn-primary btn-sm" onclick="AspenDiscovery.Account.enroll({$campaign->id}, {$userId});">{translate text="Enroll" isPublicFacing=true}</button>
                                    </td>
                                {/if}
                                <td>
                                    <button class="btn btn-primary btn-sm" onclick="toggleUpcomingCampaignInfo({$resultIndex});">{translate text="Campaign Information" isPublicFacing=true}</button>
                                </td>
                        </tr>
                        <tr id="upcomingCampaigns_{$resultIndex}" class="campaign-dropdown" style="display:none;">
                                <td colspan="4">
                                    {* <h4>{translate text="Milestones"}</h4> *}
                                    <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>{translate text="Milestone" isPublicFacing=true}</th>
                                            <th>{translate text="Milestone Reward" isPublicFacing=true}</th>
                                        </tr>
                                    </thead>
                                        <tbody>
                                        {foreach from=$campaign->milestones item="milestone"}
                                            <tr>
                                                <td>{$milestone->name}</td>
                                                <td>
                                                    {$milestone->rewardName}
                                                    {if $milestone->rewardType == 1 && $milestone->rewardExists}
                                                        <img src="{$milestone->rewardImage}" alt="{$milestone->rewardName}" style="max-width:100px; max-height:100px;" />
                                                    {/if}
                                                </td>
                                            </tr>                                 
                                        {/foreach}
                                        </tbody>
                                    </table>
                                </td>
                        </tr>
                    {/if}
                {/foreach}
            </table>
        {/if}
        {assign var="hasPastCampaigns" value=false}
        {foreach from=$pastCampaigns item="campaign" key="resultIndex"}
            {assign var="hasPastCampaigns" value=true}
            {break}
        {/foreach}
        {if $hasPastCampaigns}
            <h2>{translate text="Past Campaigns" isPublicFacing=true}</h2>
            <table id="pastCampaignsTable" class="table table-striped">
                <thead>
                    <tr>
                        <th>{translate text="Campaign Name" isPublicFacing=true}</th>
                        <th>{translate text="Start Date" isPublicFacing=true}</th>
                        <th>{translate text="End Date" isPublicFacing=true}</th>
                        <th>{translate text="Campaign Reward" isPublicFacing=true}</th>
                    </tr>
                </thead>
                <tbody>
                {foreach from=$pastCampaigns item="campaign" key="resultIndex"}
                    <tr>
                        <td>{$campaign->name}</td>
                        <td>{$campaign->startDate}</td>
                        <td>{$campaign->endDate}</td>
                        <td>
                            {$campaign->rewardName}
                            {if $campaign->rewardType == 1 && $campaign->rewardExists}
                                <img src="{$campaign->rewardImage}" alt="{$campaign->rewardName}" style="max-width:100px; max-height:100px;" />
                            {/if}
                        </td>
                        <td>
                            <button class="btn btn-primary btn-small" onclick="togglePastCampaignInfo({$resultIndex});">{translate text="Campaign Information" isPublicFacing=true}</button>
                        </td>
                    </tr>
                    <tr id="pastCampaigns_{$resultIndex}" class="campaign-dropdown" style="display:none;">
                        <td col="4">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>{translate text="Milestone" isPublicFacing=true}</th>
                                        <th>{translate text="Milestone Reward" isPublicFacing=true}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {foreach from=$campaign->milestones item="milestone"}
                                        <tr>
                                            <td>{$milestone->name}</td>
                                            <td>
                                                {$milestone->rewardName}
                                                {if $milestone->rewardType == 1 && $milestone->rewardExists}
                                                    <img src="{$milestone->rewardImage}" alt="{$milestone->rewardName}" style="max-width:100px; max-height:100px;" />
                                                {/if}
                                            </td>
                                        </tr>
                                    {/foreach}
                                </tbody>
                            </table>
                        </td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        {/if}
        {assign var="hasEnrolledPastCampaigns" value=false}
        {foreach from=$pastCampaigns item="campaign" key="resultIndex"}
            {if $campaign->enrolled}
                {assign var="hasEnrolledPastCampaigns" value=true}
                {break}
            {/if}
        {/foreach}
        {if $hasEnrolledPastCampaigns}
            <h2>{translate text="Your Past Campaigns" isPublicFacing=true}</h2>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>{translate text="Campaign Name" isPublicFacing=true}</th>
                        <th>{translate text="Start Date" isPublicFacing=true}</th>
                        <th>{translate text="End Date" isPublicFacing=true}</th>
                        <th>{translate text="Campaign Reward" isPublicFacing=true}</th>
                    </tr>
                </thead>
                <tbody>
                {foreach from=$pastCampaigns item="campaign" key="resultIndex"}
                    {if $campaign->enrolled}
                            <tr>
                                <td>{$campaign->name}</td>
                                <td>{$campaign->startDate}</td>
                                <td>{$campaign->endDate}</td>
                                <td>
                                {$campaign->rewardName}
                                {if $campaign->rewardType == 1 && $campaign->rewardExists}
                                    <img src="{$campaign->rewardImage}" alt="{$campaign->rewardName}" style="max-width:100px; max-height:100px;" />
                                {/if}<br>
                                {if $campaign->campaignRewardGiven}
                                    <strong>{translate text="Reward Received"}</strong>
                                {/if}
                                </td>
                                <td>
                                    <button class="btn btn-primary btn-sm" onclick="toggleYourPastCampaignInfo({$resultIndex});">{translate text="Campaign Information" isPublicFacing=true}</button>
                                </td>
                            </tr>
                            <tr id="yourPastCampaigns_{$resultIndex}" style="display:none;">
                                <td colspan="4">
                                    <table class="table table-bordered">
                                        <thead>
                                            <th>{translate text="Milestone" isPublicFacing=true}</th>
                                            <th>{translate text="Milestone Progress" isPublicFacing=true}</th>
                                            <th>{translate text="Milestone Reward" isPublicFacing=true}</th>
                                            <th>{translate text="Milestone Reward Status" isPublicFacing=true}</th>
                                        </thead>
                                        <tbody>
                                        {foreach from=$campaign->milestones item="milestone"}
                                            <tr>
                                                <td>{$milestone->name}</td>
                                                <td>
                                                <div class="progress" style="width:100%; border:1px solid black; border-radius:4px;height:20px;">
                                                    <div class="progress-bar" role="progressbar" aria-valuenow="{$milestone->progress}" aria-valuemin="0"
                                                    aria-valuemax="100" style="width: {$milestone->progress}%; line-height: 20px; text-align: center; color: #fff;">
                                                        {$milestone->progress}%
                                                    </div>
                                                </td>
                                                <td>
                                                    {$milestone->rewardName}
                                                    {if $milestone->rewardType == 1 && $milestone->rewardExists}
                                                        <img src="{$milestone->rewardImage}" alt="{$milestone->rewardName}" style="max-width:100px; max-height:100px;" />
                                                    {/if}
                                                </td>
                                                <td>
                                                    {if $milestone->rewardGiven}
                                                        {translate text="Reward Given" isPublicFacing=true}
                                                    {else}
                                                        {translate text="Not Yet Given" isPublicFacing=true}
                                                    {/if}
                                                </td>
                                            </tr>
                                        {/foreach}
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                    {/if}
                {/foreach}
                </tbody>
            </table>
        {/if}
    {/if}
{/strip}
{literal}
    <script type="text/javascript">
           function toggleYourCampaignInfo(index) {
            var campaignInfoDiv = document.getElementById('yourCampaigns_' + index);
            if (campaignInfoDiv.style.display === 'none') {
                campaignInfoDiv.style.display = 'block';
            } else {
                campaignInfoDiv.style.display = 'none';
            }
        }       

        function toggleActiveCampaignInfo(index) {
            var campaignInfoDiv = document.getElementById('activeCampaigns_' + index);
            if (campaignInfoDiv.style.display === 'none') {
                campaignInfoDiv.style.display = 'block';
            } else {
                campaignInfoDiv.style.display = 'none';
            }
        }    

        function toggleUpcomingCampaignInfo(index) {
            var campaignInfoDiv = document.getElementById('upcomingCampaigns_' + index);
            if (campaignInfoDiv.style.display === 'none') {
                campaignInfoDiv.style.display = 'block';
            } else {
                campaignInfoDiv.style.display = 'none';
            }
        }    

        function togglePastCampaignInfo(index) {
            var campaignInfoDiv = document.getElementById('pastCampaigns_' + index);
            if (campaignInfoDiv.style.display === 'none') {
                campaignInfoDiv.style.display = 'block';
            } else {
                campaignInfoDiv.style.display = 'none';
            }
        }    

        function toggleYourPastCampaignInfo(index) {
            var campaignInfoDiv = document.getElementById('yourPastCampaigns_' + index);
            if (campaignInfoDiv.style.display === 'none') {
                campaignInfoDiv.style.display = 'block';
            } else {
                campaignInfoDiv.style.display = 'none';
            }
        }

        function toggleLinkedUserCampaignInfo(campaignRowId) {
            var infoRow = document.getElementById(campaignRowId);
            if (infoRow.style.display === "none") {
                infoRow.style.display = "table-row";
            } else {
                infoRow.style.display = "none";
            }
        }
    </script>
{/literal}