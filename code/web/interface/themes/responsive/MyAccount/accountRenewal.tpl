{strip}
	<div id="main-content">
		{if empty($loggedIn)}
			{translate text="You must sign in to view this information." isPublicFacing=true}<a href='/MyAccount/Login' class="btn btn-primary">{translate text="Sign In" isPublicFacing=true}</a>
		{elseif $ilsUnsupported} 
			{translate text="Card and account renewals are not supported." isPublicFacing=true}
		{else}
			<h1>{translate text='Account Renewal' isPublicFacing=true}</h1>
			<h2>{translate text="{$currentStep.title}" isPublicFacing=true}</h2>
			{include file="MyAccount/accountRenewal/warnings.tpl"}
			<form method="post" action="/MyAccount/AccountRenewal">
				{include file="MyAccount/accountRenewal/step.tpl"}
				{include file="MyAccount/accountRenewal/navigation.tpl"}
			</form>
		{/if}
	</div>
{/strip}

{literal}
	<script type="text/javascript">
		document.addEventListener('DOMContentLoaded', function() {
			let yesButton = document.getElementById('yesButton');
			let noButton = document.getElementById('noButton');
			let continueButton = document.getElementById('continueButton');
			let affirmationInput = document.getElementById('userAgrees');
			let warningMessage = document.getElementById('client-warning-message');
			let h2 = document.querySelector('h2');

			yesButton.addEventListener('click', function() {
				affirmationInput.value = 'yes';
				continueButton.disabled = false;
				warningMessage.hidden = true;
			});
			noButton.addEventListener('click', function() {
				affirmationInput.value = 'no';
				continueButton.disabled = true;
				warningMessage.hidden = false;
			});
		});
	</script>
{/literal}