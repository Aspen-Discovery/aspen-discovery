{strip}
	<div id="page-content" class="content">
		<div id="main-content">
			<div class="resultHead"><h1>{translate text='Reset My PIN' isPublicFacing=true}</h1></div>
			<div class="page">
				{if empty($result.success) && $result.error}
					<div class="alert alert-danger">{$result.error}</div>{* Translation should be done prior to display *}
					<div>
						<a class="btn btn-primary" role="button" href="/MyAccount/EmailResetPin">{translate text='Try Again' isPublicFacing=true}</a>
					</div>
				{elseif !empty($result.message)}
					<div class="alert alert-success">{$result.message}</div>{* Translation should be done prior to display *}
					<p class="alert alert-warning">
						{translate text="If you do not receive an email within a few minutes, please check any spam folder your email service may have.   If you do not receive any email, please contact your library to have them reset your pin." isPublicFacing=true}
					</p>
					<p>
						<a class="btn btn-primary" role="button" href="/MyAccount/Login">{translate text='Sign in' isPublicFacing=true}</a>
					</p>
				{else}
					<p class="alert alert-success">
						{translate text="An email has been sent to the email address on the circulation system for your account containing a link to reset your PIN." isPublicFacing=true}
					</p>
					<p class="alert alert-warning">
						{translate text="If you do not receive an email within a few minutes, please check any spam folder your email service may have.   If you do not receive any email, please contact your library to have them reset your pin." isPublicFacing=true}
					</p>
					<p>
						<a class="btn btn-primary" role="button" href="/MyAccount/Login">{translate text='Sign in' isPublicFacing=true}</a>
					</p>
				{/if}
			</div>
		</div>
	</div>
{/strip}