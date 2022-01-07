{strip}
	<div id="page-content" class="col-xs-12">
		<h1>{translate text='Two-factor Authentication' isPublicFacing=true}</h1>
		<div id="loginFormWrapper">
			<p>{translate text="You must enroll into two-factor authentication before logging in." isPublicFacing=true}</p>
			<input type="submit" name="submit" value="{translate text="Start" isPublicFacing=true}" id="loginFormEnroll" class="btn btn-primary" onclick="return AspenDiscovery.Account.show2FAEnrollment(true);">
		</div>
	</div>
{/strip}