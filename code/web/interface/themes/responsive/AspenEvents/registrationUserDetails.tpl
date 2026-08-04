{strip}
	<div id="eventRegistrationUserDetails-{$eventSourceId}" class="well well-sm" style="margin-top: 10px;">
		<div><strong>{translate text="Email" isPublicFacing=true}:</strong> <span id="eventUserEmail-{$eventSourceId}">{$userEmail|escape}</span> <a id="eventUserEmailChangeLink-{$eventSourceId}" href="/MyAccount/ContactInformation" class="btn btn-xs btn-warning">{translate text="change" isPublicFacing=true}</a></div>
		<div><strong>{translate text="Home Branch" isPublicFacing=true}:</strong> <span id="eventUserLocation-{$eventSourceId}">{$userHomeLocation|escape}</span></div>
	</div>
{/strip}
