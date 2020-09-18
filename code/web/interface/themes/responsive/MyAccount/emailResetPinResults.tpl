{strip}
	<div id="page-content" class="content">
		<div id="main-content">
			<div class="resultHead"><h1>{translate text='Reset My PIN'}</h1></div>
			<div class="page">
				{if !$result.success && $result.error}
					<div class="alert alert-danger">{$result.error}</div>{* Translation should be done prior to display *}
					<div>
						<a class="btn btn-primary" role="button" href="/MyAccount/EmailResetPin">{translate text='Try Again'}</a>
					</div>
				{elseif $result.message}
					<div class="alert alert-success">{$result.message}</div>{* Translation should be done prior to display *}
					<p>
						<a class="btn btn-primary" role="button" href="/MyAccount/Login">{translate text='Sign in'}</a>
					</p>
				{else}
					<p class="alert alert-success">
						{translate text="email_pin_reset_success" defaultText="A email has been sent to the email address on the circulation system for your account containing a link to reset your PIN."}
					</p>
					<p class="alert alert-warning">
						{translate text="email_pin_reset_check_spam" defaultText="If you do not receive an email within a few minutes, please check any spam folder your email service may have.   If you do not receive any email, please contact your library to have them reset your pin."}
					</p>
					<p>
						<a class="btn btn-primary" role="button" href="/MyAccount/Login">{translate text='Sign in'}</a>
					</p>
				{/if}
			</div>
		</div>
	</div>
{/strip}