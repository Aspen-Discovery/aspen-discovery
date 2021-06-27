{strip}
<h1>{translate text='Register for a Library Card'}</h1>
<div class="page">
	{if !empty($eCardSettings)}
		<!-- The following script tags can be placed in the library's <head> or <body> tag -->
		<script src="https://{$eCardSettings->server}/js/eCARDEmbed.js"></script>
		<script>loadQGeCARD({$eCardSettings->clientId})</script>

		<!-- The following <div> tag should be placed on the web page where you the library would like the registration form to display -->
		<div id="eCARD" data-language="{$userLang->code}" data-branchid=""></div>
	{else}
		{translate text="eCARD functionality is not properly configured."}
	{/if}
</div>
{/strip}