<div id="main-content">
	{if !empty($loggedIn)}
		<p class="lead">{translate text="Are you sure you want to turn off two-factor authentication?" isPublicFacing=true}</p>
		<p>
		{if $methodToCancel == 'email'}
			{translate text="You’ll need to reconfigure email verification if you decide to start using it again." isPublicFacing=true}
		{else}
			{translate text="You’ll need to reconfigure your authentictor app if you decide to start using it again." isPublicFacing=true}
		{/if}
		{if !$willHave2FAActiveAfterRemoval}
			&nbsp;{translate text="Any unused backup codes will be deleted." isPublicFacing=true}
		{/if}
		</p>
	{/if}
</div>
