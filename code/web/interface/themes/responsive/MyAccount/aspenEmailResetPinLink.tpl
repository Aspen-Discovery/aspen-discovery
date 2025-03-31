{strip}
	<div id="page-content" class="col-xs-12">

		<h1>{translate text='Reset My PIN' isPublicFacing=true}</h1>
		<div class="alert alert-info">{translate text="To reset your PIN, enter your barcode.  You must have an email associated with your account to reset your PIN.  If you do not, please contact the library." isPublicFacing=true}</div>

		<form id="emailResetPin" method="POST" action="/MyAccount/InitiateResetPin" class="form-horizontal">
			<div class="form-group">
				<label for="username" class="control-label col-xs-12 col-sm-4">{translate text=$usernameLabel isPublicFacing=true}</label>
				<div class="col-xs-12 col-sm-8">
					<input id="reset_username" name="reset_username" type="text" size="14" maxlength="50" required class="form-control" {if !empty($username)}value="{$username}"{/if}>
				</div>
			</div>
			<div class="form-group">
				<div class="col-xs-12 col-sm-offset-4 col-sm-8">
					<button type="submit" id="emailPinSubmit" name="submit" class="btn btn-primary">
						{translate text="Reset My PIN" isPublicFacing=true}
					</button>
				</div>
			</div>
		</form>
	</div>
{/strip}
