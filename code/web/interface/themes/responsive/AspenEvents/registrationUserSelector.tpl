{strip}
	<div class="form-group">
		<label for="eventUserSelector-{$eventSourceId}" class="control-label">{translate text="Register user" isPublicFacing=true}</label>
		<select id="eventUserSelector-{$eventSourceId}" name="selectedUser" class="form-control" onchange="AspenDiscovery.Account.updateEventRegistrationUser(this, '{$eventSourceId}');">
			<option value="{$userId}" data-email="{$userEmail|escape}" data-location="{$userHomeLocation|escape}" selected>{$userDisplayName|escape}</option>
			{foreach from=$linkedUsers item=linkedUser}
				<option value="{$linkedUser->id}" data-email="{$linkedUser->email|escape}" data-location="{$linkedUser->getHomeLocationName()|escape}">{$linkedUser->getDisplayName()|escape}</option>
			{/foreach}
		</select>
	</div>
{/strip}
