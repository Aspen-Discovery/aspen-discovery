<div id="page-content" class="content">
	<div id="main-content">
		<div class="resultHead"><h1>{translate text='Forgot Your PIN?' isPublicFacing=true}</h1></div>
		<div class="page">
			<p>{$result.message}</p>

			{if !empty($result.error)}
				<div>
					<a href="/MyAccount/RequestPinReset">{translate text="Try Again" isPublicFacing=true}</a>
				</div>
			{else}
				<div>
					<a href="/MyAccount/Login">{translate text="Sign in here" isPublicFacing=true}</a>
				</div>
			{/if}
		</div>
	</div>
</div>
