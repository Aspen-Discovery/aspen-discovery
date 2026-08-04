{strip}
	<div class="btn-group" role="group">
		{if $registrationAction == 'registered'}
			<button id="aspen-events-toggle-registration-button-{$eventSourceId}" type="button" class="btn btn-primary" onclick="return AspenDiscovery.Account.toggleUserEventRegistration('{$eventSourceId}');">
				{translate text='Unregister' isPublicFacing=true}
			</button>
		{elseif $registrationAction == 'completeRegistration' || $registrationAction == 'registrationAvailable'}
			<button id="aspen-events-toggle-registration-button-{$eventSourceId}" type="button" class="btn btn-primary" onclick="return AspenDiscovery.Account.toggleUserEventRegistration('{$eventSourceId}');">
				{if $registrationAction == 'completeRegistration'}
					{translate text='Complete Your Registration' isPublicFacing=true}
				{else}
					{translate text='Register' isPublicFacing=true}
				{/if}
			</button>
		{elseif $registrationAction == 'joinWaitingList'}
			<button id="aspen-events-toggle-registration-button-{$eventSourceId}" type="button" class="btn btn-primary" onclick="return AspenDiscovery.Account.joinEventWaitingList('{$eventSourceId}');">
				{translate text='Join Waiting List' isPublicFacing=true}
			</button>
		{elseif $registrationAction == 'showPosition'}
			<button id="aspen-events-waiting-list-position-{$eventSourceId}" class="btn btn-primary" disabled>
				{translate text='You are number %1% on the waiting list' 1=$userWaitingListPosition isPublicFacing=true}
			</button>
			<button id="aspen-events-toggle-registration-button-{$eventSourceId}" type="button" class="btn btn-warning" onclick="return AspenDiscovery.Account.leaveEventWaitingList('{$eventSourceId}');">
				{translate text='Leave Waiting List' isPublicFacing=true}
			</button>
		{elseif $registrationAction == 'eventFull'}
			<button id="aspen-events-toggle-registration-button-{$eventSourceId}" type="button" class="btn btn-primary" disabled>
				{translate text='Cannot Register - Event Full' isPublicFacing=true}
			</button>
		{/if}
	</div>
{/strip}