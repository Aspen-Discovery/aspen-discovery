{strip}
	<h3 id="campaignNotificationModalTitle">{translate text="Campaign Notification Options" isPublicFacing=true}</h3>
	<div id="campaignEmailOptIn" class="form-check mb-3">
		<input type="checkbox" class="form-check-input me-2" id="emailOptInSlider" {if $isOptedIn}checked{/if}>
		<label id="emailOptInLabel" class="form-check-label" for="emailOptInSlider">{translate text="Opt in to campaign email updates for %1%" 1=$campaignName isPublicFacing=true}</label>&nbsp;
	</div>
	{if !empty($user->email)}
		<p id="addresToSendEmails" class="mt-5">{translate text="Emails will be sent to: %1%" 1=$user->email isPublicFacing=true}</p>
	{else}
		<p id="noAddressToSendEmails" class="mt-5">{translate text="Please update your email address in your %1%" 1='<a href="/MyAccount/ContactInformation">contact information</a>' isPublicFacing=true}</p>
	{/if}
{/strip}