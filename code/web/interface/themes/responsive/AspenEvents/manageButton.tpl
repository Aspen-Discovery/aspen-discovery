{strip}
	<button id="aspen-events-manage-button-{$event.sourceId}" type="button" class="btn btn-xs btn-primary" onclick="return AspenDiscovery.Account.toggleManageEvent('{$event.sourceId|escape}');">
		{translate text = 'Manage' isPublicFacing=true}
	</button>	
{/strip}
