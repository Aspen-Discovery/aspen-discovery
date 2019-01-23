<div id="page-content" class="content">
	<div id="main-content">
		<div class="resulthead"><h3>{translate text='Forgot Your PIN?'}</h3></div>
		<div class="page">
			<p>{$requestPinResetResult.message}</p>

			{if $requestPinResetResult.error}
				<div>
					<a href="{$path}/MyAccount/RequestPinReset">Try Again</a>
				</div>
			{else}
				<div>
					<a href="{$path}/MyAccount/Login">Login here</a>
				</div>
			{/if}
		</div>
	</div>
</div>
