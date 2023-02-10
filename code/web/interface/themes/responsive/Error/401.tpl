{strip}
	<div id="main-content" class="col-tn-12">
		<h1>{translate text='Oops' isPublicFacing=true}</h1>
		<div>
			<div class="alert alert-warning">{translate text="We're sorry, but it looks like you don't have access to this page." isPublicFacing=true}</div>
			{if empty($loggedIn) && $showLoginButton}
				<div class="alert alert-info">
					{translate text="You may be able to view this page if you sign in." isPublicFacing=true}
				</div>
				{if (!empty($followupModule) && !empty($followupAction))}
					<div>
						<a href='/MyAccount/Login?followupModule={$followupModule}&followupAction={$followupAction}{if !empty($id)}&pageId={$id}{/if}' class="btn btn-primary">{translate text="Sign In" isPublicFacing=true}</a>
					</div>
				{/if}
			{/if}
		</div>
	</div>
{/strip}