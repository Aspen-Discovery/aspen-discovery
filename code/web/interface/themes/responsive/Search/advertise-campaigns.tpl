{strip}
	<div class="container mt-5">
		<div class="row justify-content-center">
			<div class="col-12 col-md-8">
				<h2 class="text-center mb-4">{$campaignName}</h2>
				<h3 class="text-center mb-4">{$campaignDescription}</h3>
				<a class="text-center mb-4" href="/services/MyAccount/MyCampaigns">{translate text="Visit your campaigns section to join!" isPublicFacing=true}</a>

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
										{if $milestone->rewardExists && $milestone->rewardType ==1}
											<img src="{$milestone->rewardImage}" alt="{$milestone->rewardName}" style="max-width:100px; max-height:100px;" />
										{/if}
									</div>
								</li>
							{/foreach}
							</ul>
						</div>
						<div style="display: flex; justify-content: space-between; align-items: center;">
							<span style="font-weight: bold">{translate text="Campaign Reward: " isPublicFacing=true}</span>
							<div>
								<span>{$campaignRewardName}</span><br/>
								{if $campaignRewardType == 1 && $campaignRewardExists}
									<img src="{$campaignRewardImage}" alt="{$campaignRewardName}" style="max-width:100px; max-height:100px;" />
								{/if}
							</div>
						</div>
					</div>
				{/if}
			<div>
		</div>
	</div>
{/strip}