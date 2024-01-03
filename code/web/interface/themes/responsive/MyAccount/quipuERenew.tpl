{strip}
<h1>{translate text='Renew your Library Card' isPublicFacing=true}</h1>
<div class="page">
	{if !empty($eCardSettings)}
{*		{if !empty($selfRegistrationFormMessage)}*}
{*			<div id="selfRegistrationMessage">*}
{*				{translate text=$selfRegistrationFormMessage isPublicFacing=true isAdminEnteredData=true}*}
{*			</div>*}
{*		{/if}*}

		<div id="eRenewParent">
			{if !empty($patronId)}
				<input type="hidden" id="patronID" value="{$patronId}">

				<!-- The following script tags can be placed in the library's <head> or <body> tag -->
				<script src="https://{$eCardSettings->server}/js/eRenewEmbed.js"></script>
				<script>loadQGeRenew({$eCardSettings->clientId})</script>

				<!-- The following <div> tag should be placed on the web page where you the library would like the renewal form to display -->
				<div id="eRenew" data-language="{$userLang->code}" data-branchid=""></div>
			{else}
				{translate text="Please sign in before renewing you card." isPublicFacing=true}
			{/if}
		</div>
	{else}
		{translate text="eCARD functionality is not properly configured." isPublicFacing=true}
	{/if}
</div>
{/strip}