{strip}
<p class="alert alert-danger" id="loginError" style="display: none"></p>
<form method="post" action="{$path}/MyAccount/Home" id="loginForm" class="form-horizontal" role="form" onsubmit="return AspenDiscovery.Account.processAjaxLogin()">
	<div id="missingLoginPrompt" style="display: none">Please enter both {$usernameLabel} and {$passwordLabel}.</div>
	<div id ='loginUsernameRow' class='form-group'>
		<label for="username" class='control-label col-xs-12 col-sm-4'>{$usernameLabel}:</label>
		<div class='col-xs-12 col-sm-8'>
			<input type="text" name="username" id="username" value="{$username|escape}" size="28" class="form-control"/>
		</div>
	</div>
	<div id ='loginPasswordRow' class='form-group'>
		<label for="password" class='control-label col-xs-12 col-sm-4'>{$passwordLabel}: </label>
		<div class='col-xs-12 col-sm-8'>
			<input type="password" name="password" id="password" size="28" onkeypress="return AspenDiscovery.submitOnEnter(event, '#loginForm');" class="form-control"/>
		</div>
	</div>
	<div id ='loginPasswordRow2' class='form-group'>
		<div class='col-xs-12 col-sm-offset-4 col-sm-8'>
			<label for="showPwd" class="checkbox">
				<input type="checkbox" id="showPwd" name="showPwd" onclick="return AspenDiscovery.pwdToText('password')"/>
				{translate text="Reveal Password"}
			</label>
		</div>
	</div>
</form>
{/strip}