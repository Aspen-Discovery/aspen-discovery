<div id="page-content" class="content">
	<div id="main-content">
		<div class="resultHead"><h1>{translate text='Forgot Your PIN?'}</h1></div>
		<div class="page">
			<p>{$result.message}</p>

			{if $result.error}
				<div>
					<a href="/MyAccount/RequestPinReset">Try Again</a>
				</div>
			{else}
				<div>
					<a href="/MyAccount/Login">Login here</a>
				</div>
			{/if}
		</div>
	</div>
</div>
