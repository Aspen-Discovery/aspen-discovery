{strip}
	<div id="main-content">
		{if $accessWarningMessage}
			<div class="alert alert-danger" role="alert">{$accessWarningMessage}</div>
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
			if (!yesButton || !noButton) {
				return;
			}

			let continueButton = document.getElementById('continueButton');
			let affirmationInput = document.getElementById('userAgrees');
			let warningMessage = document.getElementById('client-warning-message');

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