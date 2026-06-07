{strip}
	{if !empty($loggedIn)}

		<h1>{translate text='Titles On Hold' isPublicFacing=true}</h1>
		{if !empty($profile->_web_note)}
			<div class="row">
				<div id="web_note" class="alert alert-info text-center col-xs-12">{$profile->_web_note}</div>
			</div>
		{/if}
		{if !empty($accountMessages)}
			{include file='systemMessages.tpl' messages=$accountMessages}
		{/if}
		{if !empty($ilsMessages)}
			{include file='ilsMessages.tpl' messages=$ilsMessages}
		{/if}

		{* Check to see if there is data for the section *}
		{if !empty($libraryHoursMessage)}
			<div class="libraryHours alert alert-success">{$libraryHoursMessage}</div>
		{/if}
		{if !empty($offline) && !$enableEContentWhileOffline}
			<div class="alert alert-warning"><strong>{translate text=$offlineMessage isPublicFacing=true}</strong></div>
		{else}
		{if count($linkedUsers) > 0 && $allowFilteringOfLinkedAccountsInHolds}
			{assign var="filterType" value="holds"}
			{include file="./linkedUsersDropdown.tpl"}
		{/if}
            <div id="holdsFiltersBar"></div>

			<div id="holds">
                {if empty($offline)}
					<div><div id="allHoldsPlaceholder" aria-label="All Holds List">{translate text="Loading holds from all sources" isPublicFacing=true}</div></div>
                {/if}
			</div>
			<script type="text/javascript">
				{literal}
				$(document).ready(function() {
					{/literal}
					AspenDiscovery.Account.loadHolds('all');
					{literal}
				});
				{/literal}
			</script>
		{/if}
	{else} {* Check to see if user is logged in *}
		{translate text="You must sign in to view this information." isPublicFacing=true}<a href='/MyAccount/Login' class="btn btn-primary">{translate text="Sign In" isPublicFacing=true}</a>
	{/if}
{/strip}
