{strip}
	<div id="page-content" class="content">
		<div id="main-content">
			<div class="resulthead"><h3>{translate text='Password Recovery Results'}</h3></div>
			<div class="page">
				{if !empty($error)}
					<div class="alert alert-danger">{$error}</div>
					<div>
						<a class="btn btn-primary" role="button" href="/MyAccount/EmailResetPin">Try Again</a>
					</div>
				{elseif $result.message}
					<div class="alert alert-success">{$result.message}</div>
					<p>
						<a class="btn btn-primary" role="button" href="/MyAccount/Login">{translate text='Login'}</a>
					</p>
				{/if}
			</div>
		</div>
	</div>
{/strip}