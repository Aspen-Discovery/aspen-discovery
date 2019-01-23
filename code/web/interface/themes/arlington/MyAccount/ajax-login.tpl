<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal">×</button>
	<h4 class="modal-title" id="myModalLabel">Login</h4>
</div>
<div class="modal-body">
	<p class="alert alert-danger" id="loginError" style="display: none"></p>
	<p class="alert alert-danger" id="cookiesError" style="display: none">It appears that you do not have cookies enabled on this computer.  Cookies are required to access account information.</p>
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
		<form method="post" action="{$path}/MyAccount/Home" id="loginForm" class="form-horizontal" role="form" onsubmit="return VuFind.Account.processAjaxLogin()">
			<div id="missingLoginPrompt" style="display: none">Please enter both {$usernameLabel} and {$passwordLabel}.</div>
			<div id="loginUsernameRow" class="form-group">
				<label for="username" class="control-label col-xs-12 col-sm-4">{$usernameLabel}:</label>
				<div class="col-xs-12 col-sm-8">
					<input type="text" name="username" id="username" value="{$username|escape}" size="28" class="form-control">
				</div>
			</div>
			<div id="loginPasswordRow" class="form-group">
				<label for="password" class="control-label col-xs-12 col-sm-4">{$passwordLabel}: </label>
				<div class="col-xs-12 col-sm-8">
					<input type="password" name="password" id="password" size="28" onkeypress="return VuFind.submitOnEnter(event, '#loginForm');" class="form-control">
				</div>
			</div>
			<div id ='loginHelpRow' class='form-group'>
				<div class='col-xs-12 col-sm-offset-4 col-sm-8'>
					<p class='help-block'>
						<a href="{$path}/MyAccount/RequestPinReset">Forgot your PIN or need a PIN?</a><br/>
						<a href='http://library.arlingtonva.us/services/accounts-and-borrowing/get-a-free-library-card/'>Get a Card</a>
					</p>

					<label for="showPwd" class="checkbox">
						<input type="checkbox" id="showPwd" name="showPwd" onclick="return VuFind.pwdToText('password')"/>
						{translate text="Reveal Password"}
					</label>

					{if !$isOpac}
						<label for="rememberMe" class="checkbox">
							<input type="checkbox" id="rememberMe" name="rememberMe"/>
							{translate text="Remember Me"}
						</label>
					{/if}
				</div>
			</div>
		</form>
	{/if}
</div>
<div class="modal-footer">
	<button class="btn" data-dismiss="modal" id="modalClose">Close</button>
	{if !$offline || $enableLoginWhileOffline}
		<span class="modal-buttons">
			<input type="submit" name="submit" value="{if $multistep}Continue{else}Login{/if}" id="loginFormSubmit" class="btn btn-primary extraModalButton" onclick="return VuFind.Account.processAjaxLogin()">
		</span>
	{/if}
</div>
{literal}
<script type="text/javascript">
	$('#username').focus().select();
	$(document).ready(
		function (){
			VuFind.Account.validateCookies();
			var haslocalStorage = false;
			if ("localStorage" in window) {
				try {
					window.localStorage.setItem('_tmptest', 'temp');
					haslocalStorage = (window.localStorage.getItem('_tmptest') == 'temp');
{/literal}{* // if we get the same info back, we are good. Otherwise, we don't have localStorage.*}{literal}
					window.localStorage.removeItem('_tmptest');
				} catch(error) {} // something failed, so we don't have localStorage available.
			}

			if (haslocalStorage) {
				var rememberMe = (window.localStorage.getItem('rememberMe') == 'true'); // localStorage saves everything as strings
				if (rememberMe) {
					var lastUserName = window.localStorage.getItem('lastUserName'),
							lastPwd = window.localStorage.getItem('lastPwd');
{/literal}{*// showPwd = (window.localStorage.getItem('showPwd') == 'true'); // localStorage saves everything as strings *}{literal}
					$("#username").val(lastUserName);
					$("#password").val(lastPwd);
{/literal}{*// $("#showPwd").prop("checked", showPwd  ? "checked" : '');
//					if (showPwd) VuFind.pwdToText('password');*}{literal}
				}
				$("#rememberMe").prop("checked", rememberMe ? "checked" : '');
			} else {
{/literal}{* // disable, uncheck & hide RememberMe checkbox if localStorage isn't available.*}{literal}
				$("#rememberMe").prop({checked : '', disabled: true}).parent().hide();
			}
{/literal}{* // Once Box is shown, focus on username input and Select the text;*}{literal}
			$("#modalDialog").on('shown.bs.modal', function(){
				$('#username').focus().select();
			})
		}
	);
</script>
{/literal}