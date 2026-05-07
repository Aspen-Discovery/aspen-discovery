{strip}
	{if empty($loggedIn)}
		{translate text="You must sign in to view this information." isPublicFacing=true}<a href='/MyAccount/Login' class="btn btn-primary">{translate text="Sign In" isPublicFacing=true}</a>
	{else}
		{* alerts *}
		{if !empty($profile->_web_note)}<div class="row"> <div id="web_note" class="alert alert-info text-center col-xs-12">{$profile->_web_note}</div></div>{/if}
		{if !empty($accountMessages)}{include file='systemMessages.tpl' messages=$accountMessages}{/if}
		{if !empty($ilsMessages)}{include file='ilsMessages.tpl' messages=$ilsMessages}{/if}

		{* page container *}
		<h1>{translate text='My Bookings' isPublicFacing=true}</h1>
		<div id="bookingsPlaceholder" aria-label="Bookings List">{translate text="Loading bookings" isPublicFacing=true}</div>

		<script type="text/javascript">
			$(document).ready(function() {
				AspenDiscovery.Account.loadBookings();
			});
		</script>
	{/if}
{/strip}



