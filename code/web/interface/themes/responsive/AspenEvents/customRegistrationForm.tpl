{strip}
	<div class="page-section well well-sm">
		<form method="post" id="eventRegistrationForm-{$eventSourceId}" name="eventRegistrationForm" role="form" onsubmit="return AspenDiscovery.Account.saveEventRegistrationInformation()">
			{include file="AspenEvents/attendeeCategories.tpl"}
			{foreach from=$registrationFormStructure item=property}
				{if is_array($property) && isset($property.property) && isset($property.type)}
					{$property.readOnly = $userIsRegistered}
					{if isset($savedRegistrationFieldValues[$property.fieldId])}
						{$property.default = $savedRegistrationFieldValues[$property.fieldId]}
					{/if}
					{include file="DataObjectUtil/property.tpl"}
				{/if}
			{/foreach}
		</form>
	</div>
{/strip}