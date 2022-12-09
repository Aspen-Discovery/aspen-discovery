{strip}
	<p class="alert alert-danger" id="loginError" style="display: none"></p>
	<form method="post" action="/MyAccount/LinkedAccounts" id="loginForm" class="form-horizontal" role="form" onsubmit="AspenDiscovery.Account.processAddLinkedUser()">
		<div id="missingLoginPrompt" style="display: none">{translate text="Please enter both %1% and %2%." 1=$usernameLabel 2=$passwordLabel translateParameters=true isPublicFacing=true}</div>
		<div id='loginUsernameRow' class='form-group'>
			<label for="username" class='control-label col-xs-12 col-sm-4'>{translate text=$usernameLabel isPublicFacing=true}</label>
			<div class='col-xs-12 col-sm-8'>
				<input type="text" name="username" id="username" value="{$username|escape}" size="28" class="form-control"/>
			</div>
		</div>
		<div id='loginPasswordRow' class='form-group'>
			<label for="password" class='control-label col-xs-12 col-sm-4'>{translate text=$passwordLabel isPublicFacing=true} </label>
			<div class='col-xs-12 col-sm-8'>
				<input type="password" name="password" id="password" size="28" maxlength="60" class="form-control"/>
			</div>
		</div>
		<div id='loginPasswordRow2' class='form-group'>
			<div class='col-xs-12 col-sm-offset-4 col-sm-8'>
				<label for="showPwd" class="checkbox">
					<input type="checkbox" id="showPwd" name="showPwd" onclick="return AspenDiscovery.pwdToText('password')"/>
					{translate text="Reveal Password" isPublicFacing=true}
				</label>
			</div>
		</div>
	</form>
{/strip}
{literal}
	<script type="text/javascript">
		$(function () {
			addEventListener("keypress", function(event) {
				// If the user presses the "Enter" key on the keyboard
				if (event.key === "Enter") {
					event.preventDefault();
					// Trigger the button element with a click
					document.getElementById("AddAccountSubmit").click();
				}
			});
		})
	</script>
{/literal}