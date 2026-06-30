{strip}
	<p>{translate text="{$currentStep.description}" isPublicFacing=true}</p>
    {if $currentStep.isInformationStep}
		<span for="userAgrees" class="label label-danger" style="margin-right: .5em;">{translate text="Required" isAdminFacing=true}</span>
    	<input type="hidden" name="userAgrees" id="userAgrees" value="" required>
    	<button type="button" id="noButton" class="btn btn-default">{translate text="No" isPublicFacing=true}</button>
    	<button type="button" id="yesButton" class="btn btn-default">{translate text="Yes" isPublicFacing=true}</button>
		<br>
		<br>
    {elseif $currentStep.name == 'verifyContactInformation'}
		{if !empty($patronUpdateForm)}
			{$patronUpdateForm}
		{else}
			{include file="MyAccount/contactInformationForm.tpl"}
		{/if}
	{elseif $currentStep.name == 'done'}
		{if $renewalSuccess}
			<div class="alert alert-success">
				{translate text="Your account has been successfully renewed." isPublicFacing=true}
				{if $renewalData.expiry_date}
					<br/>{translate text="New expiration date: " isPublicFacing=true} {$renewalData.expiry_date}
				{/if}
			</div>
		{else}
			<div class="alert alert-warning">
				{translate text="There was an error renewing your account: " isPublicFacing=true} {$renewalError}
			</div>
		{/if}
	{/if}
{/strip}				
