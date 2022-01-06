{strip}
	<div id="main-content">
		{if $loggedIn}
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
            {if $allowPinReset}
			<div class="row" style="margin-bottom: 3em">
				<div class="col-xs-6">
					<label for="password" style="font-size: 18px">{translate text='PIN/Password' isPublicFacing=true}</label>
				</div>
				<div class="col-xs-6 text-right">
					<button type="button" name="resetPinPassword" class="btn btn-default" {if $offline}disabled{/if}>{translate text='Reset PIN/Password' isPublicFacing=true}</button>{if $offline}<small class="muted help-block">Catalog is currently offline, please try again later.</small>{/if}
				</div>
			</div>
            {/if}
			{if $twoFactorEnabled}
			<div class="row">
				<div class="col-xs-6">
					<label for="2faStatus" style="font-size: 18px">{translate text='2-Factor Authentication' isPublicFacing=true}</label>
					<small class="text-muted help-block">{translate text="Two-factor authentication is an enhanced security measure. Once enabled, you'll be required to give two types of identification when you log into the catalog." isPublicFacing=true}</small>
					<small class="text-muted help-block bold">{translate text="Email is currently the only authentication method available." isPublicFacing=true}</small>
				</div>
				<div class="col-xs-6 text-right">
					{if $twoFactorStatus == '0'}
						<button type="button" name="2faStatus" class="btn btn-primary" onclick="return AspenDiscovery.Account.show2FAEnrollment(false);">{translate text="Set up" isPublicFacing=true}</button>
                    {else}
						<button type="button" name="2faStatus" class="btn btn-primary" onclick="return AspenDiscovery.Account.showCancel2FA();" {if !$enableDeactivation}disabled{/if}>{translate text="Turn off" isPublicFacing=true}</button>
                        {if !$enableDeactivation}<small class="help-block">{translate text="Your account is required to have 2FA enabled" isPublicFacing=true}</small>{/if}
                    {/if}
				</div>
			</div>
	        {if $twoFactorStatus == '1'}
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
