{strip}
{if !empty($masqueradeMode)}
	<div id="autoLogoutMessage">{translate text="Are you still there?  Click <strong>Continue</strong> to stay in Masquerade Mode, <strong>End Masquerade</strong> to exit Masquerade Mode or <strong>Logout</strong> to sign out completely." isPublicFacing=true}</div>
{else}
	<div id="autoLogoutMessage">{translate text="Are you still there?  Click <strong>Continue</strong> to keep using the catalog or <strong>Logout</strong> to end your session immediately." isPublicFacing=true}</div>
{/if}
{/strip}