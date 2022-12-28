{strip}
	<div id="page-content" class="content">
		<div id="main-content">
			<div class="resultHead"><h1>{translate text='PIN Reminder' isPublicFacing=true}</h1></div>
			<div class="page">
				{if !empty($emailResult.error)}
					<p class="alert alert-danger">{$emailResult.error}</p>
					<div>
						<a class="btn btn-primary" role="button" href="/MyAccount/EmailPin">{translate text="Try Again" isPublicFacing=true}</a>
					</div>
				{else}
					<p class="alert alert-success"> {translate text="Your PIN number has been sent to the email address we have on file." isPublicFacing=true}</p>
					<p>
						<a class="btn btn-primary" role="button" href="/MyAccount/Login">{translate text='Sign in' isPublicFacing=true}</a>
					</p>
				{/if}
			</div>
		</div>
	</div>
{/strip}