{strip}
<div id="page-content" class="col-xs-12">
	<h2>{translate text='Login to your account'}</h2>
	<div id="loginFormWrapper">
		{if $message}{* Errors for Full Login Page *}
			<p class="alert alert-danger" id="loginError" >{$message|translate}</p>
		{else}
			<p class="alert alert-danger" id="loginError" style="display: none"></p>
		{/if}
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
			<form method="post" action="{$path}/MyAccount/Home" id="loginForm" class="form-horizontal">
				<div id="missingLoginPrompt" style="display: none">Please enter both {$usernameLabel} and {$passwordLabel}.</div>
				<div id="loginFormFields">
					<div id="loginUsernameRow" class="form-group">
						<label for="username" class="control-label col-xs-12 col-sm-4">{$usernameLabel}: </label>
						<div class="col-xs-12 col-sm-8">
							<input type="text" name="username" id="username" value="{$username|escape}" size="28" class="form-control">
						</div>
					</div>
					<div id="loginPasswordRow" class="form-group">
						<label for="password" class="control-label col-xs-12 col-sm-4">{$passwordLabel}: </label>
						<div class="col-xs-12 col-sm-8">
							<input type="password" title="PIN should be 4 numbers" pattern="[0-9]{ldelim}4,{rdelim}" name="password" id="password" size="28" class="form-control">
						</div>
					</div>
	        
					<div id="loginPasswordConfirmRow" class="form-group" style="display:none">
						<label for="password2" class='control-label col-xs-12 col-sm-4'>{translate text='Confirm pin #'}: </label>
						<div class='col-xs-12 col-sm-8'>
							<input type="password" pattern="[0-9]{ldelim}4,{rdelim}" name="password2" id="password2" size="28" class="form-control">
						</div>
					</div>
					<div id="loginHelpRow" class="form-group">
						<div class="col-xs-12 col-sm-offset-4 col-sm-8">
							{*<p class="help-block"><a href="{$path}/MyAccount/RequestPinReset">Forgot your PIN?</a></p>*}
							{*<p class="help-block"><a href="#" onclick="document.getElementById('loginPasswordConfirmRow').style.display='block';">Create new PIN</p>*}
							{*<p class="help-block"><a href="#" onclick="$('#loginPasswordConfirmRow').show();">Create new PIN</p>*}
							{if $enableSelfRegistration == 1}
								<p class="help-block">
									<a href="http://library.nashville.org/card/crd_getcard.asp">Get a Card</a>
								</p>
							{/if}

							<label for="showPwd" class="checkbox">
								<input type="checkbox" id="showPwd" name="showPwd" onclick="return VuFind.pwdToText('password')">
								{translate text="Reveal Password"}
							</label>

							<label for="rememberMe" class="checkbox">
								<input type="checkbox" id="rememberMe" name="rememberMe">
								{translate text="Remember Me"}
							</label>
						</div>
					</div>

					<div id="loginSubmitRow" class="form-group">
					{*<div id="loginPasswordRow2" class="form-group">*}
						<div class="col-xs-12 col-sm-offset-4 col-sm-8">
							<input type="submit" name="submit" value="Login" id="loginFormSubmit" class="btn btn-primary" onclick="return VuFind.Account.preProcessLogin();">
							{if $followup}<input type="hidden" name="followup" value="{$followup}">{/if}
							<input type="cancel" name="cancel" value="Cancel" id="loginFormCancel" class="btn btn-primary" onclick="Location.reload()" style="display:none;">
							{if $followupModule}<input type="hidden" name="followupModule" value="{$followupModule}">{/if}
							{if $followupAction}<input type="hidden" name="followupAction" value="{$followupAction}">{/if}
							{if $recordId}<input type="hidden" name="recordId" value="{$recordId|escape:"html"}">{/if}
							{if $comment}<input type="hidden" id="comment" name="comment" value="{$comment|escape:"html"}">{/if}
							{if $cardNumber}<input type="hidden" name="cardNumber" value="{$cardNumber|escape:"html"}">{/if}
							{if $returnUrl}<input type="hidden" name="returnUrl" value="{$returnUrl}">{/if}
						</div>
					</div>

				</div>
			</form>
		{/if}
	</div>
</div>
{/strip}
{* TODO: // QUESTION : This doesn't look to be used at all *}
{literal}
	<script>
		function resetPinReset(){
			var barcode = $('#card_number').val();
			if (barcode.length == 0){
				alert("Please enter your library card number");
			}else{
				var url = path + '/MyAccount/AJAX?method=requestPinReset&barcode=' + barcode;
				$.getJSON(url, function(data){
					if (data.error == false){
						alert(data.message);
						if (data.success == true){
							hideLightbox();
						}
					}else{
						alert("There was an error requesting your pin reset information.  Please contact the library for additional information.");
					}
				});
			}
			return false;
		}
	</script>
{/literal}

{literal}
<script type="text/javascript">
	$('#username').focus().select();
	$(function(){
		VuFind.Account.validateCookies();
		var haslocalStorage = VuFind.hasLocalStorage() || false;
		if (haslocalStorage) {
			var rememberMe = (window.localStorage.getItem('rememberMe') == 'true'), // localStorage saves everything as strings
					showCovers = window.localStorage.getItem('showCovers') || false;
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
			if (showCovers.length > 0) {
				$("<input>").attr({
					type: 'hidden',
					name: 'showCovers',
					value: showCovers
				}).appendTo('#loginForm');
			}
		} else {
			{/literal}{* // disable, uncheck & hide RememberMe checkbox if localStorage isn't available.*}{literal}
			$("#rememberMe").prop({checked : '', disabled: true}).parent().hide();
		}
		{/literal}{* // Once Box is shown, focus on username input and Select the text;
			$("#modalDialog").on('shown.bs.modal', function(){
				$('#username').focus().select();
			})*}{literal}
	});
</script>
{/literal}
