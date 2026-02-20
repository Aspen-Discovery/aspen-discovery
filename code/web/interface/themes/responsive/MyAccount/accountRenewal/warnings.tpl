{strip}
	{if $validationError}
		<div class="alert alert-danger" role="alert">{$validationError}</div>
	{/if}
	{if $currentWarningMessage}
		<div class="alert alert-warning" role="alert">{$currentWarningMessage}</div>
	{/if}
	<div class="alert alert-warning" role="alert" id="client-warning-message" hidden="true">
		{if isset($selfRenewalSettings.self_renewal_failure_message)} 
			{translate text="{$selfRenewalSettings.self_renewal_failure_message}" isPublicFacing=true}
		{else}
			{translate text='If you have answered no, you are not currently eligible for automated account renewal. Please contact your library.' isPublicFacing=true}
		{/if}
	</div>
{/strip}