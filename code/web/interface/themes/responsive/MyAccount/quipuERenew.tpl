{strip}
<h1>{translate text='Register for a Library Card' isPublicFacing=true}</h1>
<div class="page">
	{if !empty($eCardSettings)}
{*		{if !empty($selfRegistrationFormMessage)}*}
{*			<div id="selfRegistrationMessage">*}
{*				{translate text=$selfRegistrationFormMessage isPublicFacing=true isAdminEnteredData=true}*}
{*			</div>*}
{*		{/if}*}

		<div id="eRenewParent">
			<!-- The following script tags can be placed in the library's <head> or <body> tag -->
			<script src="https://{$eCardSettings->server}/js/eRenewEmbed.js"></script>
			<script>loadQGeCARD({$eCardSettings->clientId})</script>

			<!-- The following <div> tag should be placed on the web page where you the library would like the renewal form to display -->
			<div id="eRenew" data-language="{$userLang->code}" data-branchid=""></div>
		</div>
	{else}
		{translate text="eCARD functionality is not properly configured." isPublicFacing=true}
	{/if}
</div>
{/strip}

<!-- The following script tags can be placed in the library's <head> or <body> tag -->
<script id="eRenewServer" src="https://ecard-us2.quipugroup.net/js/eRenewEmbed.js"></script>
<script>loadQGeRenew(55)</script>

<!-- The following <div> tag should be placed on the web page where you the library would like the registration form to display -->
<div id="eRenew" data-language="en" data-branchid=""></div>