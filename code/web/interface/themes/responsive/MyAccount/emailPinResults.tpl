{strip}
	<div id="page-content" class="content">
		<div id="main-content">
			<div class="resultHead"><h1>{translate text='PIN Reminder'}</h1></div>
			<div class="page">
				{if $emailResult.error}
					<p class="alert alert-danger">{$emailResult.error}</p>
					<div>
						<a class="btn btn-primary" role="button" href="/MyAccount/EmailPin">Try Again</a>
					</div>
				{else}
					<p class="alert alert-success"> Your PIN number has been sent to the email address we have on file.</p>
					<p>
						<a class="btn btn-primary" role="button" href="/MyAccount/Login">{translate text='Sign in'}</a>
					</p>
				{/if}
			</div>
		</div>
	</div>
{/strip}