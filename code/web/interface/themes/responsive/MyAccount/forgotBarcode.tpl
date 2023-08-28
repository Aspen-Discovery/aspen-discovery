{strip}
	<div id="page-content" class="col-xs-12">

		<h1>{translate text='Forgot %1%' 1=$usernameLabel isPublicFacing=true translateParameters=true}</h1>
		{if isset($error)}
            <p class="alert alert-warning">
                {$error}
            </p>
        {else}
		<div class="alert alert-info">{translate text="To receive your $usernameLabel to login, provide your phone number.  You must have a text-capable phone number associated with your account to receive your $usernameLabel.  If you do not, please contact the library." isPublicFacing=true}</div>
		<form id="forgotBarcode" method="POST" action="/MyAccount/ForgotBarcode" class="form-horizontal">
			<div class="form-group">
				<label for="username" class="control-label col-xs-12 col-sm-4">{translate text='Phone Number' isPublicFacing=true}</label>
				<div class="col-xs-12 col-sm-8">
					<input id="phone" name="phone" type="text" size="14" maxlength="50" required class="form-control" {if !empty($username)}value="{$username}"{/if}>
				</div>
			</div>
			<div class="form-group">
				<div class="col-xs-12 col-sm-offset-4 col-sm-8">
					<input id="emailPinSubmit" name="submit" class="btn btn-primary" type="submit" value="{translate text='Send My %1%' 1=$usernameLabel isPublicFacing=true}">
				</div>
			</div>
		</form>
		{/if}
	</div>
{/strip}