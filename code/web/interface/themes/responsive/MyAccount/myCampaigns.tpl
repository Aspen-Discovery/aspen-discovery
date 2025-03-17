{strip}
    <h1>{translate text="Campaigns" isPublicFacing=true}</h1>
    <h3><a href="/CommunityEngagement/Leaderboard">See the Leaderboard</a></h3>
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
                                {if $campaign->displayName}
                                    {$campaign->rewardName}
                                {/if}
                                {if $campaign->rewardType == 1 && $campaign->rewardExists}
                                    <img src="{$campaign->badgeImage}" alt="{$campaign->rewardName}" style="max-width:100px; max-height:100px;" />
                                {/if}
                                 {if $campaign->campaignRewardGiven && $campaign->rewardType == 1}
                                    <a href="/Search/ShareCampaigns?rewardName={$campaign->rewardName}&rewardImage={$campaign->badgeImage}&rewardId={$campaign->rewardId}">
                                        {translate text="Share on Social Media" isPublicFacing=true}
                                    </a>
                                {/if}
                            </td>
                            <td>{$campaign->numCompletedMilestones} / {$campaign->numCampaignMilestones}</td>
                            <td>
                                <button class="btn btn-primary btn-sm" onclick="toggleActionButtons({$resultIndex});" aria-expanded="false" id="toggle-actions-{$resultIndex}" aria-label="{translate text="Toggle Manage Campaign Options for {$campaign->name}" isPublicFacing=true}">
                                    {translate text="Manage Campaign" isPublicFacing=true}
                                </button>
                                <div class="action-buttons" id="actions-{$resultIndex}" style="display:none;" role="group" aria-labelledby="toggle-actions-{$resultIndex}">
                                        {if $campaign->optInToCampaignLeaderboard == 0}
                                            <button class="btn btn-primary btn-sm" aria-label="{translate text="Join Leaderboard for {$campaign->name}" isPublicFacing=true}" onclick="AspenDiscovery.CommunityEngagement.optInToCampaignLeaderboard({$campaign->id}, {$userId});">{translate text=" Join Leaderboard" isPublicFacing=true}</button>
                                        {else}
                                            <button class="btn btn-primary btn-sm" aria-label="{translate text="Leave Leaderboard for {$campaign->name}" isPublicFacing=true}" onclick="AspenDiscovery.CommunityEngagement.optOutOfCampaignLeaderboard({$campaign->id}, {$userId});">{translate text="Leave Leaderboard " isPublicFacing=true}</button>
                                        {/if}
                                    
                                        {if $campaign->optInToCampaignEmailNotifications}
                                            <button class="btn btn-primary btn-sm" aria-label="{translate text="Opt out of email notifications for {$campaign->name}" isPublicFacing=true}" onclick="AspenDiscovery.CommunityEngagement.optOutOfCampaignEmailNotifications({$campaign->id}, {$userId});">{translate text="Email Notifications Opt Out" isPublicFacing=true}</button>
                                        {else}
                                            <button class="btn btn-primary btn-sm" aria-label="{translate text="Opt into email notifications for {$campaign->name}" isPublicFacing=true}" onclick="AspenDiscovery.CommunityEngagement.optInToCampaignEmailNotifications({$campaign->id}, {$userId});">{translate text="Email Notifications Opt In" isPublicFacing=true}</button>
                                        {/if}
                                    
                                   
                                </div>
                            </td>
                            <td>
                                <button class="btn btn-primary btn-sm" aria-label="{translate text="Unenroll from {$campaign->name}" isPublicFacing=true}" onclick="AspenDiscovery.Account.unenroll({$campaign->id}, {$userId});">{translate text="Unenroll" isPublicFacing=true}</button>
                            </td>
                            <td>
                                <button class="btn btn-primary btn-sm" aria-label="{translate text="See data for {$campaign->name}" isPublicFacing=true}" onclick="toggleYourCampaignInfo({$resultIndex});">{translate text="Campaign Information" isPublicFacing=true}</button>
                            </td>
                        </tr>
                            {* <tr id="campaignInfo_{$resultIndex}" style="display:none;"> *}
                            {assign var="showAddProgressColumn" value=false}
                            {foreach from=$campaign->milestones item="milestone"}
                                {if $milestone->allowPatronProgressInput}
                                    {assign var="showAddProgressColumn" value=true}
                                {/if}
                            {/foreach}
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
                                              {if $showAddProgressColumn}
                                                <th>{translate text="Add Progress" isPublicFacing=true}</th>
                                            {/if}
                                        </tr>
                                    </thead>
                                        <tbody>
                                        {foreach from=$campaign->milestones item="milestone"}
                                            <tr>
                                                <td>{$milestone->name}</td>
                                                <td>
                                                    {if $milestone->displayName}
                                                        {$milestone->rewardName}
                                                    {/if}
                                                    {$milestone->rewardGiven}
                                                    {if $milestone->rewardType == 1 && $milestone->rewardExists}
                                                        <img src="{$milestone->rewardImage}" alt="{$milestone->rewardName}" style="max-width:100px; max-height:100px;" />
                                                    {/if}
                                                </td>
                                                <td>
                                                    {if $milestone->completedGoals <= $milestone->totalGoals}
                                                        {$milestone->completedGoals}/ {$milestone->totalGoals}
                                                    {else}
                                                        {$milestone->totalGoals} / {$milestone->totalGoals}
                                                    {/if}
                                                    {foreach from=$milestone->progressData item="progressData"}
                                                    <div style="padding:10px;">
                                                        {if isset($progressData['title'])}
                                                            {$progressData['title']}
                                                        {/if}
                                                    </div>
                                                    {/foreach}
                                                </td>
                                                <td style="position: relative; text-align: center; vertical-align: middle;">
                                                    <div class="progress" style="width:100%; border:1px solid black; border-radius:4px;height:20px;">
                                                        <div class="progress-bar" role="progressbar" aria-valuenow="{$milestone->progress}" aria-valuemin="0"
                                                        aria-valuemax="100" style="width: {$milestone->progress}%; line-height: 20px; text-align: center; color: #fff;">
                                                            {$milestone->progress}%
                                                        </div>
                                                    </div>
                                                    {if $milestone->progressBeyondOneHundredPercent && $milestone->extraProgress > 0}
                                                        <div class="extra-progress" aria-valuenow="{$milestone->extraProgress}" style="margin-top: 10px; font-weight: bold; display: flex; justify-content: center; align-items: center;">
                                                            <span style="background-color: #3174AF;  color: white; border-radius: 50%; width: 60px; height: 60px; text-align: center; display: flex; align-items: center; justify-content: center;">
                                                                {$milestone->extraProgress}%
                                                            </span>
                                                        </div>
                                                    {/if}
                                                </td>
                                                 {if $milestone->allowPatronProgressInput}
                                                    <td>
                                                        <button class="btn btn-primary btn-sm" onclick="AspenDiscovery.CommunityEngagement.manuallyProgressMilestone({$milestone->id}, {$userId}, {$campaign->id});">{translate text="Add Progress" isPublicFacing=true}</button>     
                                                    </td>
                                                {/if}
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
                    <table id="linkedAccountCampaignsTable" class="table table-striped">
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
                            {assign var="showLinkedUserAddProgressColumn" value=false}
                                    {foreach from=$campaign.milestones item="milestone"}
                                        {if $milestone.allowPatronProgressInput && $campaign.isEnrolled}
                                            {assign var="showLinkedUserAddProgressColumn" value=true}
                                        {/if}
                                    {/foreach}
                                <tr>
                                    <td>{$campaign.campaignName}</td>
                                    <td>{$campaign.startDate}</td>
                                    <td>{$campaign.endDate}</td>
                                    <td>
                                    {if $campaign.campaignReward.displayName}
                                        {$campaign.campaignReward.rewardName}
                                    {/if}
                                    {if $campaign.campaignReward.rewardType == 1 && $campaign.campaignReward.rewardExists}
                                            <img src="{$campaign.campaignReward.badgeImage}" alt="{$campaign.reward.rewardName}" width="100" height="100" />
                                    {/if}
                                    </td>
                                    <td>{$campaign.numCompletedMilestones} / {$campaign.numCampaignMilestones}</td>
                                    <td>
                                        {if $campaign.isEnrolled}
                                            <button class="btn btn-primary btn-sm" aria-label="{translate text="Unenroll user from {$campaign->name}" isPublicFacing=true}" onclick="AspenDiscovery.Account.unenroll({$campaign.campaignId}, {$linkedUser.linkedUserId});">{translate text="Unenroll" isPublicFacing=true}</button>
                                        {else}
                                            <button class="btn btn-primary btn-sm" aria-label="{translate text="Enroll user into {$campaign->name}" isPublicFacing=true}" onclick="AspenDiscovery.Account.enroll({$campaign.campaignId}, {$linkedUser.linkedUserId});">{translate text="Enroll" isPublicFacing=true}</button>
                                        {/if}
                                    </td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" aria-label="{translate text="See data for {$campaign->name}" isPublicFacing=true}" onclick="toggleLinkedUserCampaignInfo('linkedUserCampaigns_{$resultIndex}');">{translate text="Campaign Information" isPublicFacing=true}</button>
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
                                                    {if $showLinkedUserAddProgressColumn}
                                                        <th>{translate text="Add Progress" isPublicFacing=true}</th>
                                                    {/if}
                                                </tr>
                                            </thead>
                                            <tbody>
                                            {foreach from=$campaign.milestones item="milestone"}
                                                <tr>
                                                    <td>{$milestone.milestoneName}</td>
                                                    <td>
                                                        {if $milestone.displayName}
                                                            {$milestone.rewardName} 
                                                        {/if}
                                                        {if $milestone.rewardType == 1 && $milestone.rewardExists}
                                                            <img src="{$milestone.badgeImage}" alt="{$milestone.rewardName}" width="100" height="100" />
                                                        {/if}
                                                    </td>
                                                    <td>
                                                        {if $milestone.completedGoals <= $milestone->totalGoals}
                                                            {$milestone.completedGoals} / {$milestone.totalGoals}
                                                        {else}
                                                            {$milestone.totalGoals} / {$milestone.totalGoals}
                                                        {/if}
                                                        {foreach from=$milestone.progressData item="progressData"}
                                                            <div style="padding:10px;">
                                                                {$progressData['title']}
                                                            </div>
                                                        {/foreach}
                                                    </td>
                                                    <td style="position: relative; text-align: center; vertical-align: middle;">
                                                        <div class="progress" style="width:100%; border:1px solid black; border-radius:4px; height:20px;">
                                                            <div class="progress-bar" role="progressbar" aria-valuenow="{$milestone.progress}" aria-valuemin="0" aria-valuemax="100" style="width: {$milestone.progress}%; line-height: 20px; text-align: center; color: #fff;">
                                                                {$milestone.progress}%
                                                            </div>
                                                        </div>

                                                        {if $milestone.progressBeyondOneHundredPercent && $milestone.extraProgress > 0}
                                                            <div class="extra-progress" aria-valuenow="{$milestone.extraProgress}" style="margin-top: 10px; font-weight: bold; display: flex; justify-content: center; align-items: center;">
                                                                <span style="background-color: #3174AF;  color: white; border-radius: 50%; width: 60px; height: 60px; text-align: center; display: flex; align-items: center; justify-content: center;">
                                                                    {$milestone.extraProgress}%
                                                                </span>
                                                            </div>
                                                        {/if}
                                                    </td>
                                                    {if $milestone.allowPatronProgressInput && $campaign.isEnrolled}
                                                        <td>
                                                            <button class="btn btn-primary btn-sm" onclick="AspenDiscovery.CommunityEngagement.manuallyProgressMilestone({$milestone.id}, {$linkedUser.linkedUserId}, {$campaign.campaignId});">{translate text="Add Progress" isPublicFacing=true}</button>
                                                        </td>
                                                    {/if}
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
                                {if $campaign->displayName}
                                    {$campaign->rewardName}
                                {/if}
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
                                    <button class="btn btn-primary btn-sm" aria-label="{translate text="Unenroll from {$campaign->name}" isPublicFacing=true}" onclick="AspenDiscovery.Account.unenroll({$campaign->id}, {$userId});">{translate text="Unenroll" isPublicFacing=true}</button>
                                </td>
                            {else}
                                <td>
                                    <button class="btn btn-sm btn-primary" aria-label="{translate text="Enroll in  {$campaign->name}" isPublicFacing=true}" onclick="AspenDiscovery.Account.enroll({$campaign->id}, {$userId});">{translate text="Enroll" isPublicFacing=true}</button>
                                </td>
                            {/if}
                            <td>
                                <button class="btn btn-primary btn-sm" aria-label="{translate text="See data for {$campaign->name}" isPublicFacing=true}" onclick="toggleActiveCampaignInfo({$resultIndex});">{translate text="Campaign Information" isPublicFacing=true}</button>
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
                                                    {if $milestone->displayName}
                                                        {$milestone->rewardName}
                                                    {/if}
                                                    {if $milestone->rewardType == 1 && $milestone->rewardExists}
                                                        <img src="{$milestone->rewardImage}" alt="{$milestone->rewardName}" style="max-width:100px; max-height:100px;" />
                                                    {/if}
                                                </td>
                                                <td>
                                                    {if $milestone->completedGoals <= $milestone->totalGoals}
                                                        {$milestone->completedGoals} / {$milestone->totalGoals}
                                                    {else}
                                                        {$milestone->totalGoals} / {$milestone->totalGoals}
                                                    {/if}
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

                                                    {if $milestone->progressBeyondOneHundredPercent && $milestone->extraProgress > 0}
                                                        <div class="extra-progress" aria-valuenow="{$milestone->extraProgress}" style="margin-top: 10px; font-weight: bold; display: flex; justify-content: center; align-items: center;">
                                                            <span style="background-color: #3174AF;  color: white; border-radius: 50%; width: 60px; height: 60px; text-align: center; display: flex; align-items: center; justify-content: center;">
                                                                {$milestone->extraProgress}%
                                                            </span>
                                                        </div>
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
                                {if $campaign->displayName}
                                    {$campaign->rewardName}
                                {/if}
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
                                    <button class="btn btn-primary btn-sm" aria-label="{translate text="Unenroll from  {$campaign->name}" isPublicFacing=true}" onclick="AspenDiscovery.Account.unenroll({$campaign->id}, {$userId});">{translate text="Unenroll" isPublicFacing=true}</button>
                                </td>
                            {else}
                                <td>
                                    <button class="btn btn-primary btn-sm" aria-label="{translate text="Enroll in  {$campaign->name}" isPublicFacing=true}" onclick="AspenDiscovery.Account.enroll({$campaign->id}, {$userId});">{translate text="Enroll" isPublicFacing=true}</button>
                                </td>
                            {/if}
                                <td>
                                    <button class="btn btn-primary btn-sm" aria-label="{translate text="See data for {$campaign->name}" isPublicFacing=true}" onclick="toggleUpcomingCampaignInfo({$resultIndex});">{translate text="Campaign Information" isPublicFacing=true}</button>
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
                                                    {if $milestone->displayName}
                                                        {$milestone->rewardName}
                                                    {/if}
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
                            {if $campaign->displayName}
                                {$campaign->rewardName}
                            {/if}
                            {if $campaign->rewardType == 1 && $campaign->rewardExists}
                                <img src="{$campaign->rewardImage}" alt="{$campaign->rewardName}" style="max-width:100px; max-height:100px;" />
                            {/if}
                        </td>
                        <td>
                            <button class="btn btn-primary btn-small" aria-label="{translate text="See data for {$campaign->name}" isPublicFacing=true}" onclick="togglePastCampaignInfo({$resultIndex});">{translate text="Campaign Information" isPublicFacing=true}</button>
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
                                            <td>
                                                {$milestone->name}
                                            </td>
                                            <td>
                                                {if $milestone->displayName}
                                                    {$milestone->rewardName}
                                                {/if}
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
                                {if $campaign->displayName}
                                    {$campaign->rewardName}
                                {/if}
                                {if $campaign->rewardType == 1 && $campaign->rewardExists}
                                    <img src="{$campaign->rewardImage}" alt="{$campaign->rewardName}" style="max-width:100px; max-height:100px;" />
                                {/if}<br>
                                {if $campaign->campaignRewardGiven}
                                    <strong>{translate text="Reward Received"}<br></strong>
                                {/if}
                                 {if $campaign->campaignRewardGiven && $campaign->rewardType == 1}
                                    <a href="/Search/ShareCampaigns?rewardName={$campaign->rewardName}&rewardImage={$campaign->rewardImage}&rewardId={$campaign->rewardId}">
                                        {translate text="Share on Social Media" isPublicFacing=true}
                                    </a>
                                {/if}
                                </td>
                                <td>
                                    <button class="btn btn-primary btn-sm" aria-label="{translate text="See data for {$campaign->name}" isPublicFacing=true}" onclick="toggleYourPastCampaignInfo({$resultIndex});">{translate text="Campaign Information" isPublicFacing=true}</button>
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
                                                <td>
                                                    {$milestone->name}
                                                </td>
                                                <td style="position: relative; text-align: center; vertical-align: middle; >
                                                    <div class="progress" style="width:100%; border:1px solid black; border-radius:4px;height:20px;">
                                                        <div class="progress-bar" role="progressbar" aria-valuenow="{$milestone->progress}" aria-valuemin="0"
                                                        aria-valuemax="100" style="width: {$milestone->progress}%; line-height: 20px; text-align: center; color: #fff;">
                                                            {$milestone->progress}%
                                                        </div>
                                                    </div>

                                                {if $milestone->progressBeyondOneHundredPercent && $milestone->extraProgress > 0}
                                                    <div class="extra-progress" aria-valuenow="{$milestone->extraProgress}" style="margin-top: 10px; font-weight: bold; display: flex; justify-content: center; align-items: center;">
                                                        <span style="background-color: #3174AF;  color: white; border-radius: 50%; width: 60px; height: 60px; text-align: center; display: flex; align-items: center; justify-content: center;">
                                                            {$milestone->extraProgress}%
                                                        </span>
                                                    </div>
                                                {/if}
                                            </td>
                                            <td>
                                                {if $milestone->displayName}
                                                    {$milestone->rewardName}
                                                {/if}
                                                {if $milestone->rewardType == 1 && $milestone->rewardExists}
                                                    <img src="{$milestone->rewardImage}" alt="{$milestone->rewardName}" style="max-width:100px; max-height:100px;" />
                                                {/if}
                                            </td>
                                            <td>
                                                {if $milestone->rewardGiven}
                                                    {translate text="Reward Given" isPublicFacing=true}<br>
                                                    {if $milestone->rewardType == 1}
                                                        <a href="/Search/ShareCampaigns?rewardName={$milestone->rewardName}&rewardImage={$milestone->rewardImage}&rewardId={$milestone->rewardId}">
                                                            {translate text="Share on Social Media" isPublicFacing=true}
                                                        </a>
                                                    {/if}
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

        function toggleActionButtons(rowIndex) {
            console.log('Toggling action buttons for row' + rowIndex);
            var actionButtons = document.getElementById('actions-' + rowIndex);
            var toggleButton = document.getElementById('toggle-actions-' + rowIndex);

            if (actionButtons.style.display === 'none' || actionButtons.style.display === ""){
                actionButtons.style.display = "block";
                toggleButton.setAttribute("aria-expanded", "true");
            } else {
                actionButtons.style.display = "none";
                toggleButton.setAttribute("aria-expanded", "false");
            }
        }
    </script>
    <style>
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .action-buttons button {
            max-width: 250px;
            margin: 5px;
            padding: 5px;
        }
    </style>
{/literal}