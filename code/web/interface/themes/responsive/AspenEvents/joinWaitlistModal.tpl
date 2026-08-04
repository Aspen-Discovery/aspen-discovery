{strip}
	<div>
		<input type="hidden" id="eventRegistrationUserId-{$eventSourceId}" value="{$userId}">
		<div class="form-group">
			<label for="eventUserSelector-{$eventSourceId}" class="control-label">{translate text="Add to waiting list for" isPublicFacing=true}</label>
			<select id="eventUserSelector-{$eventSourceId}" name="selectedUser" class="form-control" onchange="AspenDiscovery.Account.updateEventRegistrationUser(this, '{$eventSourceId}');">
				<option value="{$userId}">{$userDisplayName|escape}</option>
				{foreach from=$linkedUsers item=linkedUser}
					<option value="{$linkedUser->id}">{$linkedUser->getDisplayName()|escape}</option>
				{/foreach}
			</select>
		</div>
		<button id="aspen-events-toggle-registration-button-{$eventSourceId}" type="button" class="btn btn-primary" onclick="return AspenDiscovery.Account.submitJoinWaitlist('{$eventSourceId}');">
			{translate text='Join Waiting List' isPublicFacing=true}
		</button>
	</div>
{/strip}
