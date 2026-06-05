{strip}
	<div id="main-content">
		{if !empty($loggedIn)}
			{if !empty($profile->_web_note)}
				<div class="row">
					<div id="web_note" class="alert alert-info text-center col-xs-12">{$profile->_web_note}</div>
				</div>
			{/if}
			{if !empty($accountMessages)}
				{include file='systemMessages.tpl' messages=$accountMessages}
			{/if}
			{if !empty($ilsMessages)}
				{include file='ilsMessages.tpl' messages=$ilsMessages}
			{/if}

			<h1>{translate text='Security Settings' isPublicFacing=true}</h1>
			{if !empty($allowPinReset)}
			<div class="row" style="margin-bottom: 3em">
				<div class="col-xs-6">
					<label for="password" style="font-size: 18px">{translate text='PIN/Password' isPublicFacing=true}</label>
				</div>
				<div class="col-xs-6 text-right">
					<a href="/MyAccount/ResetPinPage" id="resetPinPassword" class="btn btn-default {if !empty($offline)}disabled{/if}">{translate text='Reset PIN/Password' isPublicFacing=true}</a>{if !empty($offline)}<small class="muted help-block">{translate text="Catalog is currently offline, please try again later." isPublicFacing=true}</small>{/if}
				</div>
			</div>
			{/if}
			{if !empty($twoFactorEnabled)}
			<div class="row" style="padding-bottom: 1em;">
				<div class="col-xs-12">
					<h2>{translate text='2-Factor Authentication' isPublicFacing=true}</h2>
					{if !empty($requiredSetupWarning)}<p>{$requiredSetupWarning}</p>{/if}
					<small class="text-muted help-block">{translate text="Two-factor authentication is an enhanced security measure. Once enabled, you'll be required to give two types of identification when you log into the catalog." isPublicFacing=true}</small>
				</div>
			</div>

			{if $allowTOTP2FA}
				<div class="row">
					<div class="col-xs-6">
						<p>{translate text='Authenticator app' isPublicFacing=true} (Recommended)<br/>
						<small class="help-block">{translate text='Examples: Google Authenticator, Microsoft Authenticator, Authy, etc.' isPublicFacing=true}</small>
						</p>
					</div>
					<div class="col-xs-6 text-right">
                        {if $showSetupTotp}
							<button type="button" name="enableTOTP" class="btn btn-primary" onclick="return AspenDiscovery.Account.show2FAEnrollment(false, 'totp');">{translate text="Set up" isPublicFacing=true}</button>
                        {else}
							<button type="button" name="disableTOTP" class="btn btn-primary" onclick="return AspenDiscovery.Account.showCancel2FA('totp');" {if !$canDisableTotp}disabled{/if}>{translate text="Disable" isPublicFacing=true}</button>
                        {/if}
					</div>
				</div>
            {/if}
	        {if $allowEmail2FA}
				<div class="row" {if $allowTOTP2FA && $allowEmail2FA}style="padding-top:.5em"{/if}>
					<div class="col-xs-6">
						<p>{translate text='Email' isPublicFacing=true}<br/>
							<small class="help-block">{translate text='Get a code sent to your email address.' isPublicFacing=true}</small>
						</p>
					</div>
					<div class="col-xs-6 text-right">
	                    {if $showSetupEmail}
							<button type="button" name="enableEmail" class="btn btn-primary" onclick="return AspenDiscovery.Account.show2FAEnrollment(false, 'email');">{translate text="Set up" isPublicFacing=true}</button>
	                    {else}
							<button type="button" name="disableEmail" class="btn btn-primary" onclick="return AspenDiscovery.Account.showCancel2FA('email');" {if !$canDisableEmail}disabled{/if}>{translate text="Disable" isPublicFacing=true}</button>
	                    {/if}
					</div>
				</div>
	        {/if}

            {if !empty($migrationRequired)}
			<div class="row">
				<div class="col-xs-12">
					<div class="alert alert-warning">
						<strong>{translate text="Action Required" isPublicFacing=true}</strong><br>
                        {$migrationMessage}
					</div>
				</div>
			</div>
			{/if}
            {if $twoFactorStatus == 1 && empty($migrationRequired)}
				<div class="row">
					<div class="col-xs-6">
						<label for="2faStatus">{translate text='Backup codes' isPublicFacing=true}</label>
						<small class="text-muted help-block">{translate text="Backup codes are an extra set of one-time-use codes that you should keep with you physically. You can use one of these when logging in if your other verification method is unavailable." isPublicFacing=true}</small>
					</div>
					<div class="col-xs-6 text-right">
						<button type="button" name="2faStatus" class="btn btn-primary" onclick="return AspenDiscovery.Account.showNewBackupCodes();">{translate text="Generate new backup codes" isPublicFacing=true}</button>
						<small class="help-block">{translate text="%1% codes remaining" 1=$numBackupCodes isPublicFacing=true}</small>
					</div>
				</div>
			{/if}
			{/if}

			<script type="text/javascript">
				{* Initiate any checkbox with a data attribute set to data-switch=""  as a bootstrap switch *}
				{literal}
				$(function(){ $('input[type="checkbox"][data-switch]').bootstrapSwitch()});
				{/literal}
			</script>
		{else}
			<div class="page">
				{translate text="You must sign in to view this information." isPublicFacing=true}<a href='/MyAccount/Login' class="btn btn-primary">{translate text="Sign In" isPublicFacing=true}</a>
			</div>
		{/if}
	</div>
{/strip}
