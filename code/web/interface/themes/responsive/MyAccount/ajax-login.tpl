{strip}
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal">×</button>
		<h4 class="modal-title" id="myModalLabel">{translate text="Sign In" isPublicFacing=true}</h4>
	</div>
	<div class="modal-body">
		<p class="alert alert-danger" id="loginError" style="display: none"></p>
		<p class="alert alert-danger" id="cookiesError" style="display: none">{translate text="It appears that you do not have cookies enabled on this computer.  Cookies are required to access account information." isPublicFacing=true}</p>
		<p class="alert alert-info" id="loading" style="display: none">
			{translate text="Logging you in now. Please wait." isPublicFacing=true}
		</p>
		{if $offline}
			<div class="alert alert-warning">
				{translate text="$offlineMessage" isPublicFacing=true}
			</div>
		{/if}
		{if !(empty($ssoService))}
            {include file='MyAccount/sso-login.tpl'}
            {if $ssoLoginOptions == 0}
	            <div class="hr-label">
	                <span class="text">{translate text="or" isPublicFacing=true}</span>
	            </div>
            {/if}
        {/if}
        {if $ssoLoginOptions == 0}
		<form method="post" action="/MyAccount/Home" id="loginForm" class="form-horizontal" role="form" onsubmit="return AspenDiscovery.Account.processAjaxLogin()">
			<div id="missingLoginPrompt" style="display: none">{translate text="Please enter both %1% and %2%." 1=$usernameLabel 2=$passwordLabel isPublicFacing=true translateParameters=true}</div>
			<div id="loginUsernameRow" class="form-group">
				<label for="username" class="control-label col-xs-12 col-sm-4">{translate text=$usernameLabel isPublicFacing=true}</label>
				<div class="col-xs-12 col-sm-8">
					<input type="text" name="username" id="username" value="{if !empty($username)}{$username|escape}{/if}" size="28" class="form-control" maxlength="60">
				</div>
			</div>
			<div id="loginPasswordRow" class="form-group">
				<label for="password" class="control-label col-xs-12 col-sm-4">{translate text=$passwordLabel isPublicFacing=true} </label>
				<div class="col-xs-12 col-sm-8">
					<input type="password" name="password" id="password" size="28" onkeypress="return AspenDiscovery.submitOnEnter(event, '#loginForm');" class="form-control" maxlength="60">
					{if $forgotPasswordType != 'null' && $forgotPasswordType != 'none'}
						<p class="text-muted help-block">
							<strong>{translate text="Forgot %1%?" 1=$passwordLabel isPublicFacing=true translateParameters=true}</strong>&nbsp;
							{if $forgotPasswordType == 'emailAspenResetLink'}
								<a href="/MyAccount/InitiateResetPin">{translate text="Reset My %1%" 1=$passwordLabel isPublicFacing=true}</a>
							{elseif $forgotPasswordType == 'emailResetLink'}
								<a href="/MyAccount/EmailResetPin">{translate text="Reset My %1%" 1=$passwordLabel isPublicFacing=true}</a>
							{else}
								<a href="/MyAccount/EmailPin">{translate text="Email my %1%" 1=$passwordLabel isPublicFacing=true}</a>
							{/if}
						</p>
					{/if}
					{if $enableSelfRegistration == 1}
						<p class="help-block">
							{translate text="Don't have a library card?" isPublicFacing=true} <a href="/MyAccount/SelfReg">{translate text="Register for a new Library Card" isPublicFacing=true}</a>.
						</p>
					{elseif $enableSelfRegistration == 2}
						<p class="help-block">
							{translate text="Don't have a library card?" isPublicFacing=true} <a href="{$selfRegistrationUrl}">{translate text="Register for a new Library Card" isPublicFacing=true}</a>.
						</p>
					{elseif $enableSelfRegistration == 3}
						<p class="help-block">
							{translate text="Don't have a library card?" isPublicFacing=true} <a href="/MyAccount/eCARD">{translate text="Register for a new Library Card" isPublicFacing=true}</a>.
						</p>
					{/if}
				</div>
			</div>
			{if !(empty($loginNotes))}
				<div id="loginNotes" class="form-group">
					<div class="col-xs-12 col-sm-offset-4 col-sm-8">
						{translate text=$loginNotes isPublicFacing=true}
					</div>
				</div>
			{/if}
			{if !(empty($ssoXmlUrl))}
			<div id="SAMLLoginRow" class="form-group">
				<div class="col-xs-12 col-sm-offset-4 col-sm-8">
					<p class="help-block">
						<a href="/saml2auth.php?samlLogin=y&idp={$ssoXmlUrl}">Log in using {$ssoName}</a>
					</p>
				</div>
			</div>
			{/if}
			<div id="loginPasswordRow2" class="form-group">
				<div class="col-xs-12 col-sm-offset-4 col-sm-8">
					<label for="showPwd" class="checkbox">
						<input type="checkbox" id="showPwd" name="showPwd" onclick="return AspenDiscovery.pwdToText('password')">
						{translate text="Reveal Password" isPublicFacing=true}
					</label>

					{if !$isOpac}
						<label for="rememberMe" class="checkbox">
							<input type="checkbox" id="rememberMe" name="rememberMe">
							{translate text="Keep Me Signed In" isPublicFacing=true}
						</label>
					{/if}
				</div>
			</div>
		</form>
		{/if}
	</div>
	<div class="modal-footer">
		<button class="btn" data-dismiss="modal" id="modalClose">{translate text=Close isPublicFacing=true}</button>
		<span class="modal-buttons">
		<input type="hidden" id="multiStep" name="multiStep" value="{if !empty($multiStep)}true{else}false{/if}"/>
		{if $ssoLoginOptions == 0}<input type="submit" name="submit" value="{if !empty($multiStep)}{translate text="Continue" isPublicFacing=true inAttribute=true}{else}{translate text="Sign In" isPublicFacing=true inAttribute=true}{/if}" id="loginFormSubmit" class="btn btn-primary extraModalButton" onclick="return AspenDiscovery.Account.processAjaxLogin()">{/if}
	</span>
	</div>
{/strip}
{literal}
<script type="text/javascript">
	$('#username').focus().select();
	$(function () {
		AspenDiscovery.Account.validateCookies();
		var hasLocalStorage = AspenDiscovery.hasLocalStorage() || false;
		if (hasLocalStorage) {
			var rememberMe = (window.localStorage.getItem('rememberMe') === 'true'); // localStorage saves everything as strings
			if (rememberMe) {
				var lastUserName = window.localStorage.getItem('lastUserName');
				var lastPwd = window.localStorage.getItem('lastPwd');
				$("#username").val(lastUserName);
				$("#password").val(lastPwd);
			}
			if(rememberMe || ({/literal}{$checkRememberMe}{literal} === 1)) {
				$("#rememberMe").prop("checked", true);
			} else {
				$("#rememberMe").prop("checked", '');
			}
			//$("#rememberMe").prop("checked", rememberMe ? "checked" : '');
		} else {
			{/literal}{* // disable, uncheck & hide RememberMe checkbox if localStorage isn't available.*}{literal}
			$("#rememberMe").prop({checked: '', disabled: true}).parent().hide();
		}
		{/literal}{* // Once Box is shown, focus on username input and Select the text;*}{literal}
		$("#modalDialog").on('shown.bs.modal', function () {
			$('#username').focus().select();
		})
	});
</script>
{/literal}