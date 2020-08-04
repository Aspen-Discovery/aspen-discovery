{strip}
	<div id="page-content" class="content">
		<div id="main-content">
			<div class="resultHead"><h1>{translate text='PIN Reset'}</h1></div>
			<div class="page">
				{if $resetPinResult.error}
					<p class="alert alert-danger">{$resetPinResult.error}</p>
					{if $resetToken && $userID}
						<div>
							<a class="btn btn-primary" role="button" href="/MyAccount/ResetPin?resetToken={$resetToken}&uid={$userID}">Try Again</a>
						</div>
					{/if}
				{else}
					<p class="alert alert-success">Your PIN number has been reset.</p>
					<p>
						<a class="btn btn-primary" role="button" href="/MyAccount/Login">{translate text='Login'}</a>
					</p>
				{/if}
			</div>
		</div>
	</div>
{/strip}