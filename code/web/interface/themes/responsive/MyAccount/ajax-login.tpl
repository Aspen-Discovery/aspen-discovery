{strip}
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal">×</button>
		<h4 class="modal-title" id="myModalLabel">Login</h4>
	</div>
	<div class="modal-body">
		<p class="alert alert-danger" id="loginError" style="display: none"></p>
		<p class="alert alert-danger" id="cookiesError" style="display: none">It appears that you do not have cookies enabled on this computer. Cookies are required to access account information.</p>
		<p class="alert alert-info" id="loading" style="display: none">
			Logging you in now. Please wait.
		</p>
		{if $offline && !$enableLoginWhileOffline}
			<div class="alert alert-warning">
				<p>
					The Library’s accounts system is down. Tech support is working to assess and fix the problem as quickly as possible.
				</p>
				<p>
					Thank you for your patience and understanding.
				</p>
			</div>
		{else}
			<form method="post" action="/MyAccount/Home" id="loginForm" class="form-horizontal" role="form" onsubmit="return AspenDiscovery.Account.processAjaxLogin()">
				<div id="missingLoginPrompt" style="display: none">Please enter both {$usernameLabel} and {$passwordLabel}.</div>
				<div id="loginUsernameRow" class="form-group">
					<label for="username" class="control-label col-xs-12 col-sm-4">{$usernameLabel}</label>
					<div class="col-xs-12 col-sm-8">
						<input type="text" name="username" id="username" value="{if !empty($username)}{$username|escape}{/if}" size="28" class="form-control" maxlength="60">
					</div>
				</div>
				<div id="loginPasswordRow" class="form-group">
					<label for="password" class="control-label col-xs-12 col-sm-4">{$passwordLabel} </label>
					<div class="col-xs-12 col-sm-8">
						<input type="password" name="password" id="password" size="28" onkeypress="return AspenDiscovery.submitOnEnter(event, '#loginForm');" class="form-control" maxlength="60">
						{if $forgotPasswordType != 'null' && $forgotPasswordType != 'none'}
							<p class="text-muted help-block">
								<strong>{translate text="forgot_pin" defaultText="Forgot %1%?" 1=$passwordLabel}</strong>&nbsp;
								{if $forgotPasswordType == 'emailResetLink'}
									<a href="/MyAccount/EmailResetPin">{translate text="reset_pin" defaultText="Reset My %1%" 1=$passwordLabel}</a>
								{else}
									<a href="/MyAccount/EmailPin">{translate text="email_pin" defaultText="Email my %1%" 1=$passwordLabel}</a>
								{/if}
							</p>
						{/if}
						{if $enableSelfRegistration == 1}
							<p class="help-block">
								Don't have a library card? <a href="/MyAccount/SelfReg">Register for a new Library Card</a>.
							</p>
						{elseif $enableSelfRegistration == 2}
							<p class="help-block">
								Don't have a library card? <a href="{$selfRegistrationUrl}">Register for a new Library Card</a>.
							</p>
						{/if}
					</div>
				</div>
				<div id="loginPasswordRow2" class="form-group">
					<div class="col-xs-12 col-sm-offset-4 col-sm-8">
						<label for="showPwd" class="checkbox">
							<input type="checkbox" id="showPwd" name="showPwd" onclick="return AspenDiscovery.pwdToText('password')">
							{translate text="Reveal Password"}
						</label>

						{if !$isOpac}
							<label for="rememberMe" class="checkbox">
								<input type="checkbox" id="rememberMe" name="rememberMe">
								{translate text="Remember Me"}
							</label>
						{/if}
					</div>
				</div>
			</form>
		{/if}
	</div>
	<div class="modal-footer">
		<button class="btn" data-dismiss="modal" id="modalClose">{translate text=Close}</button>
		<span class="modal-buttons">
		<input type="submit" name="submit" value="{if !empty($multiStep)}Continue{else}Login{/if}" id="loginFormSubmit" class="btn btn-primary extraModalButton" onclick="return AspenDiscovery.Account.processAjaxLogin()">
	</span>
	</div>
{/strip}
{literal}
<script type="text/javascript">
    $('#username').focus().select();
    $(function () {
        AspenDiscovery.Account.validateCookies();
        var haslocalStorage = AspenDiscovery.hasLocalStorage() || false;
        if (haslocalStorage) {
            var rememberMe = (window.localStorage.getItem('rememberMe') == 'true'); // localStorage saves everything as strings
            if (rememberMe) {
                var lastUserName = window.localStorage.getItem('lastUserName'),
                    lastPwd = window.localStorage.getItem('lastPwd');
				{/literal}{*// showPwd = (window.localStorage.getItem('showPwd') == 'true'); // localStorage saves everything as strings *}{literal}
                $("#username").val(lastUserName);
                $("#password").val(lastPwd);
				{/literal}{*// $("#showPwd").prop("checked", showPwd  ? "checked" : '');
//					if (showPwd) AspenDiscovery.pwdToText('password');*}{literal}
            }
            $("#rememberMe").prop("checked", rememberMe ? "checked" : '');
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