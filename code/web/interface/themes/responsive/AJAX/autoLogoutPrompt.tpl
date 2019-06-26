{strip}
{if $masqueradeMode}
	<div id="autoLogoutMessage">{translate text="auto_logout_prompt_masquerade" defaultText="Are you still there?  Click <strong>Continue</strong> to stay in Masquerade Mode, <strong>End Masquerade</strong> to exit Masquerade Mode or <strong>Logout</strong> to log out completely."}</div>
{else}
	<div id="autoLogoutMessage">{translate text="auto_logout_prompt" defaultText="Are you still there?  Click <strong>Continue</strong> to keep using the catalog or <strong>Logout</strong> to end your session immediately."}</div>
{/if}
{/strip}