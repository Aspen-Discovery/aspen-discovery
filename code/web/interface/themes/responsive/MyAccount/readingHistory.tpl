<div class="col-xs-12">
	{if !empty($loggedIn)}

		<h1>{translate text='My Reading History' isPublicFacing = true} {if $historyActive == true}
				<small><a id="readingListWhatsThis" href="#" onclick="$('#readingListDisclaimer').toggle();return false;">({translate text="What's This?" isPublicFacing=true})</a></small>
			{/if}
		</h1>

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
		{if !empty($updateMessage)}
			<div class="row">
				<div class="col-xs-12">
					<div class="alert {if $updateMessageIsError}alert-danger{else}alert-success{/if}">{translate text=$updateMessage isPublicFacing=true isMetadata=true}</div>
				</div>
			</div>
		{/if}

		{if !empty($offline)}
			<div class="alert alert-warning">{translate text="<strong>The library system is currently offline.</strong> We are unable to retrieve information about any titles currently checked out." isPublicFacing=true}</div>
		{/if}

		{strip}
			{if !empty($masqueradeMode) && !$allowReadingHistoryDisplayInMasqueradeMode}
				<div class="row">
					<div class="alert alert-warning">
						{translate text="Display of the patron's reading history is disabled in Masquerade Mode." isPublicFacing=true}
					</div>
				</div>
			{/if}

			<div class="row">
				<div id="readingListDisclaimer" {if $historyActive == true}style="display: none"{/if} class="alert alert-info">
					{* some necessary white space in notice was previously stripped out when needed. *}
					{/strip}
					{translate text='The library takes seriously the privacy of your library records. Therefore, we do not keep track of what you borrow after you return it. However, our automated system has a feature called "My Reading History" that allows you to track items you check out. Participation in the feature is entirely voluntary. You may start or stop using it, as well as delete any or all entries in "My Reading History" at any time. If you choose to start recording "My Reading History," you agree to allow our automated system to store this data. The library staff does not have access to your "My Reading History", however, it is subject to all applicable local, state, and federal laws, and under those laws, could be examined by law enforcement authorities without your permission. If this is of concern to you, you should not use the "My Reading History" feature.' isPublicFacing=true}
					{strip}
				</div>
			</div>

			{if !empty($enableCostSavings) && $userHasCatalogConnection}
				<div id="costSavingsPlaceholder"></div>
			{/if}

			{if !$initialReadingHistoryLoaded && $historyActive}
				<div class="alert alert-info">
					<i class="fas fa-info-circle"></i> {translate text="Your ILS reading history is scheduled to be loaded in the background. This initial process may take a long while, depending on the number of titles to be imported, but you'll have access to your complete reading history soon. Please return to this page later and contact a librarian if the import fails." isPublicFacing=true}
				</div>
			{/if}

			<div id="readingHistoryListPlaceholder">
				<div class="text-center" style="padding-top: 50px;">
					<i class="fas fa-spinner fa-spin fa-3x" aria-hidden="true"></i> {* Should be replaced with border-spinner on Bootstrap upgrade *}
					<span class="sr-only">{translate text="Loading..." isPublicFacing=true}</span>
				</div>
			</div>

			<script type="text/javascript">
				{literal}
				$(document).ready(function() {
					AspenDiscovery.Account.loadReadingHistory({/literal}{$selectedUser}, undefined, {$page}, undefined, '{$readingHistoryFilter|escape}'{literal});
				});
				{/literal}
			</script>
		{/strip}
	{else}
		<div class="page">
			{translate text="You must sign in to view this information." isPublicFacing=true}<a href='/MyAccount/Login' class="btn btn-primary">{translate text="Sign In" isPublicFacing=true}</a>
		</div>
	{/if}
</div>
