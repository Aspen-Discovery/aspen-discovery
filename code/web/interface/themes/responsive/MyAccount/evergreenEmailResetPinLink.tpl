{strip}
	<div id="page-content" class="col-xs-12">

		<h1>{translate text='Reset My PIN/Password' isPublicFacing=true}</h1>
		<div class="alert alert-info">{translate text="To reset your PIN/Password, enter your barcode or your email address.  You must have an email associated with your account to reset your PIN.  If you do not, please contact the library." isPublicFacing=true}</div>

		<form id="emailResetPin" method="POST" action="/MyAccount/EmailResetPin" class="form-horizontal">
			{if !empty($resendEmail)}
				<input type="hidden" name="resendEmail" id="resendEmail" value="true"/>
			{/if}
			<div class="form-group">
				<label for="username" class="control-label col-xs-12 col-sm-4">{translate text=$usernameLabel isPublicFacing=true}</label>
				<div class="col-xs-12 col-sm-8">
					<input id="username" name="username" type="text" size="14" maxlength="50" class="form-control" {if !empty($username)}value="{$username}"{/if}>
				</div>
			</div>
			<div class="form-group">
				<label for="email" class="control-label col-xs-12 col-sm-4">{translate text="Email" isPublicFacing=true}</label>
				<div class="col-xs-12 col-sm-8">
					<input id="email" name="email" type="text" class="form-control" maxlength="254" size="40" {if !empty($email)}value="{$email}"{/if}>
				</div>
			</div>
			<div class="form-group">
				<div class="col-xs-12 col-sm-offset-4 col-sm-8">
					<button type="submit" id="emailPinSubmit" name="submit" class="btn btn-primary">
						{translate text="Reset My PIN" isPublicFacing=true}
					</button>
					{if !empty($resendEmail)}
						<input type="hidden" name="resendEmail" value="true">
					{/if}
				</div>
			</div>
		</form>
	</div>
{/strip}
<script type="text/javascript">
	{literal}
	$(function () {
		$("#emailResetPin").validate();
	});
	{/literal}
</script>
