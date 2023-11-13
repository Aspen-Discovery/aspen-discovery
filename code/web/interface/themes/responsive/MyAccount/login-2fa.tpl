{strip}
	<div id="page-content" class="col-xs-12">
		<h1>{translate text='Two-factor Authentication' isPublicFacing=true}</h1>
		<div id="loginFormWrapper">
			{if !$codeSent}
				<div class="alert alert-warning">{translate text="Unable to send your authentication code. Verify your account has a valid email address. To access your account now, you may provide a backup code." isPublicFacing=true}</div>
			{else}
				<p>{translate text="Enter the code sent to your authentication method or provide a backup code." isPublicFacing=true}</p>
			{/if}
			<p class="alert alert-danger" id="codeVerificationFailedPlaceholder" style="display: none;"></p>
			<p id="newCodeSentPlaceholder" class="alert alert-info" style="display: none;"></p>
			<p class="alert alert-info" id="loading" style="display: none">
				{translate text="Logging you in now. Please wait." isPublicFacing=true}
			</p>
			<form id="twoFactorAuthForm" class="form-horizontal">
				<div id="loginFormFields">
					<div id="loginAuthCodeRow" class="form-group">
						<div class="col-xs-12 col-sm-4 text-right">
							<label for="code" class="control-label">{translate text="6-digit code" isPublicFacing=true}</label>
						</div>
						<div class="col-xs-12 col-sm-8">
							<input type="text" class="form-control" id="code" name="code" maxlength="6" spellcheck="false" autocomplete="false">
						</div>
					</div>
				</div>
				<div id="loginActions" class="form-group">
					<div class="col-xs-12 col-sm-offset-4 col-sm-8">
						{if !empty($followupModule)}<input type="hidden" name="followupModule" value="{$followupModule}">{/if}
						{if !empty($followupAction)}<input type="hidden" name="followupAction" value="{$followupAction}">{/if}
						{if !empty($recordId)}<input type="hidden" name="recordId" value="{$recordId|escape:"html"}">{/if}
						{if !empty($comment)}<input type="hidden" id="comment" name="comment" value="{$comment|escape:"html"}">{/if}
						{if !empty($cardNumber)}<input type="hidden" name="cardNumber" value="{$cardNumber|escape:"html"}">{/if}
						<input type="hidden" id="myAccountAuth" value="true">
						<input type="submit" name="submit" value="{translate text="Verify" isPublicFacing=true}" id="loginFormVerify" class="btn btn-primary" onclick="return AspenDiscovery.Account.verify2FALogin();">
						&nbsp;<a id="loginFormCancelLogin" class="btn btn-warning" href="/MyAccount/Logout">{translate text="Cancel Sign In" isPublicFacing=true}</a>
					</div>
					<div class="col-xs-12 col-sm-offset-4 col-sm-8">
						<a class="btn btn-xs btn-link" style="display: inline-block; margin-top: 1em" onclick="return AspenDiscovery.Account.new2FACode();">{translate text="Code expired? Send another" isPublicFacing=true}</a>

					</div>
				</div>
			</form>
		</div>
	</div>
{/strip}