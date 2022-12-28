{strip}
	<div id="page-content" class="content">
		<div id="main-content">
			<div class="resultHead"><h1>{translate text='PIN Reset' isPublicFacing=true}</h1></div>
			<div class="page">
				{if !empty($resetPinResult.error)}
					<p class="alert alert-danger">{$resetPinResult.error}</p>
					{if !empty($resetToken) && $userID}
						<div>
							<a class="btn btn-primary" role="button" href="/MyAccount/ResetPin?resetToken={$resetToken}&uid={$userID}">{translate text="Try Again" isPublicFacing=true}</a>
						</div>
					{/if}
				{else}
					<p class="alert alert-success">{translate text="Your PIN number has been reset." isPublicFacing=true}</p>
					<p>
						<a class="btn btn-primary" role="button" href="/MyAccount/Login">{translate text='Sign in' isPublicFacing=true}</a>
					</p>
				{/if}
			</div>
		</div>
	</div>
{/strip}