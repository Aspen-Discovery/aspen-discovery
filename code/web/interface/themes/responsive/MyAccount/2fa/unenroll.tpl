<div id="main-content">
    {if !empty($loggedIn)}
		<p class="lead">{translate text="Are you sure you want to turn off two-factor authentication?" isPublicFacing=true}</p>
		<p>{translate text="You’ll need to reconfigure email verification if you decide to start using it again. Any unused backup codes will be deleted." isPublicFacing=true}</p>
    {/if}
</div>