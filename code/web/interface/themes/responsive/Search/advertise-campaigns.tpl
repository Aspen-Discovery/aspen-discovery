{strip}
<div class="container mt-5">
	<div class="row justify-content-center">
		<div class="col-12 col-md-8">
			<h2 class="text-center mb-4">{$campaignName}</h2>
			<h3 class="text-center mb-4">{$campaignDescription}</h3>
			<div class="text-center mb-4">
				<a href="/services/MyAccount/MyCampaigns">{translate text="Visit your campaigns section to join!" isPublicFacing=true}</a>
			</div>

			{if $campaignMilestones && count($campaignMilestones) > 0}
				<div class="row">
					<div class="col-12">
						<h3>Milestones</h3>
						<ul class="list-group">
						{foreach from=$campaignMilestones item=milestone}
							<li class="list-group-item" style="display: flex; justify-content: space-between; align-items: center;">
								<span style="font-weight: bold;">{$milestone->name}</span>
								<div style="display: flex; flex-direction: column; align-items: center;">
									<span>{$milestone->rewardName}</span><br/>
									{if $milestone->rewardExists}
										<img src="{$milestone->rewardImage}" alt="{$milestone->rewardName}" style="width:100px; height:100px; padding:10px;" />
									{/if}
									<p>{$milestone->rewardDescription}</p>
								</div>
							</li>
						{/foreach}
						</ul>
					</div>
				</div>
			{/if}

			{if $extraCreditActivities && count($extraCreditActivities) > 0}
				<div class="row mt-4">
					<div class="col-12">
						<h3>Extra Credit Activities</h3>
						<ul class="list-group">
						{foreach from=$extraCreditActivities item=activity}
							<li class="list-group-item" style="display: flex; justify-content: space-between; align-items: center;">
								<span style="font-weight: bold;">{$activity->name}</span>
								<div style="display: flex; flex-direction: column; align-items: center;">
									<span>{$activity->rewardName}</span><br/>
									{if $activity->rewardExists}
										<img src="{$activity->rewardImage}" alt="{$activity->rewardName}" style="width:100px; height:100px; padding:10px;" />
									{/if}
									<p>{$activity->rewardDescription}</p>
								</div>
							</li>
						{/foreach}
						</ul>
					</div>
				</div>
			{/if}

			<div class="row mt-4">
				<div class="col-12">
					<div style="display: flex; justify-content: space-between; align-items: center;">
						<span style="font-weight: bold">{translate text="Campaign Reward: " isPublicFacing=true}</span>
						<div style="display: flex; flex-direction: column; align-items: center;">
							<span>{$campaignRewardName}</span><br/>
							{if $campaignRewardExists}
								<img src="{$campaignRewardImage}" alt="{$campaignRewardName}" style="width:100px; height:100px; padding:10px;" />
							{/if}
							<p>{$campaignRewardDescription}</p>
						</div>
					</div>
				</div>
			</div>

		</div>
	</div>
</div>
{/strip}