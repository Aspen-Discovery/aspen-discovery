{strip}
	{foreach from=$campaigns item=campaignData}
		{assign var=campaign value=$campaignData.campaign}

		<div class="dashboardCategory" style="border: 1px solid #3174AF; padding: 15px; margin-bottom: 20px;">

			<h5>
				<a href="/CommunityEngagement/CampaignTable?id={$campaign->id}">
					{$campaign->name|escape}
				</a>
			</h5>

			<table class="table table-bordered table-sm">
				<thead>
				<tr>
					<th>{translate text="Milestone" isPublicFacing=true}</th>
					<th>{translate text="Progress" isPublicFacing=true}</th>
					<th>{translate text="Status" isPublicFacing=true}</th>
					<th>{translate text="Reward" isPublicFacing=true}</th>
				</tr>
				</thead>
				<tbody>

				{if $campaignData.milestones|@count > 0}
					{foreach from=$campaignData.milestones item=milestone}
						<tr>
							<td>{$milestone.name|escape}</td>

							<td>
								{$milestone.completed} / {$milestone.total}

								<div class="progress" style="width:100%; border:1px solid black; border-radius:4px;height:20px;">
									<div class="progress-bar"
									     role="progressbar"
									     aria-valuenow="{$milestone.percentage}"
									     aria-valuemin="0"
									     aria-valuemax="100"
									     style="width: {$milestone.percentage}%; background-color: blue; color: white; text-align: center;">
										{$milestone.percentage}%
									</div>
								</div>
							</td>

							<td>
								{if $milestone.isComplete}
									{translate text="Complete" isPublicFacing=true}
								{else}
									{translate text="Incomplete" isPublicFacing=true}
								{/if}

								{if $milestone.milestoneType == 'manual'}
									<br>
									{if $campaign->enrolled}
										<button class="btn btn-primary btn-sm"
										        onclick="AspenDiscovery.CommunityEngagement.adminManuallyProgressMilestone({$milestone.id}, {$userId}, {$campaign->id}); return false;">
											{translate text="Add Progress" isPublicFacing=true}
										</button>
									{else}
										<button class="btn btn-secondary btn-sm" disabled>
											{translate text="Add Progress" isPublicFacing=true}
										</button>
									{/if}
								{/if}
							</td>

							<td>
								{if $milestone.rewardGiven}
									{translate text="Reward Given" isPublicFacing=true}
								{else}
									<button class="btn btn-primary btn-sm"
									        onclick="AspenDiscovery.CommunityEngagement.adminMilestoneRewardGiven({$userId}, {$campaign->id}, {$milestone.id}); return false;">
										{translate text="Give Reward" isPublicFacing=true}
									</button>
								{/if}
							</td>
						</tr>
					{/foreach}
				{else}
					<tr>
						<td colspan="4">
							{translate text="No milestones defined for this campaign." isPublicFacing=true}
						</td>
					</tr>
				{/if}

				</tbody>
			</table>

			{* Campaign Completion Section *}
			<p>
				<strong>{translate text="Campaign Complete:" isPublicFacing=true}</strong>
				{if $campaign->isComplete}
					{translate text="Yes" isPublicFacing=true}
				{else}
					{translate text="No" isPublicFacing=true}
				{/if}
			</p>

			<p>
				<strong>{translate text="Reward Given:" isPublicFacing=true}</strong>
				{if $campaign->campaignRewardGiven}
					{translate text="Yes" isPublicFacing=true}
				{else}
					{translate text="No" isPublicFacing=true}
				{/if}
			</p>

			{if !$campaign->campaignRewardGiven}
				<button class="btn btn-primary"
				        onclick="AspenDiscovery.CommunityEngagement.adminCampaignRewardGiven({$userId}, {$campaign->id}); return false;">
					{translate text="Give Campaign Reward" isPublicFacing=true}
				</button>
			{/if}

			{if $campaignData.isRemoved}
				<button class="btn btn-warning"
				        onclick="AspenDiscovery.CommunityEngagement.restoreCampaignForUser({$campaign->id}, {$userId}); return false;">
					{translate text="Restore Campaign for User" isPublicFacing=true}
				</button>
			{/if}

			{* Enrollment *}
			{if ($campaign->isActive || $campaign->isUpcoming) && $campaign->canEnroll}
				{if $campaign->enrolled}
					<button type="button"
					        class="btn btn-danger"
					        onclick="AspenDiscovery.CommunityEngagement.adminUnenroll({$campaign->id}, {$userId}); return false;">
						{translate text="Unenroll" isPublicFacing=true}
					</button>
				{else}
					<button type="button"
					        class="btn btn-success"
					        onclick="AspenDiscovery.CommunityEngagement.adminEnrollPatron({$campaign->id}, {$userId}, {$userEmailOptInSetting}); return false;">
						{translate text="Enroll" isPublicFacing=true}
					</button>
				{/if}
			{/if}

		</div>
	{/foreach}
{/strip}
