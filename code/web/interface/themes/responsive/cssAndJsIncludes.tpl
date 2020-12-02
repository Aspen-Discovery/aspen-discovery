{strip}
	{* All CSS should be come before javascript for better browser performance *}
	{* TODO: Fix minification of css *}
	{if !empty($debugCss) || true}
    	{css filename="main.css"}
	{else}
		{css filename="main.min.css"}
	{/if}
	{if !empty($additionalCss)}
		<style type="text/css">
			{$additionalCss}
		</style>
	{/if}

	{* Include correct all javascript *}
	{if $ie8}
		{* include to give responsive capability to ie8 browsers, but only on successful detection of those browsers. For that reason, don't include in aspen.min.js *}
		<script src="/interface/themes/responsive/js/lib/respond.min.js?v={$gitBranch|urlencode}"></script>
	{/if}

	{* This is all merged using the merge_javascript.php file called automatically with a File Watcher*}
	{* Code is minified using uglify.js *}
	<script src="/interface/themes/responsive/js/aspen.js?v={$gitBranch|urlencode}"></script>

	{/strip}
	<script type="text/javascript">
		{* Override variables as needed *}
		{literal}
		$(document).ready(function(){{/literal}
			Globals.url = '{$url}';
			Globals.loggedIn = {if $loggedIn}true{else}false{/if};
			Globals.opac = {if $onInternalIP}true{else}false{/if};
			Globals.activeModule = '{$module}';
			Globals.activeAction = '{$action}';
			Globals.masqueradeMode = {if $masqueradeMode}true{else}false{/if};
			{if $repositoryUrl}
				Globals.repositoryUrl = '{$repositoryUrl}';
				Globals.encodedRepositoryUrl = '{$encodedRepositoryUrl}';
			{/if}

			{if $automaticTimeoutLength}
			Globals.automaticTimeoutLength = {$automaticTimeoutLength};
			{/if}
			{if $automaticTimeoutLengthLoggedOut}
			Globals.automaticTimeoutLengthLoggedOut = {$automaticTimeoutLengthLoggedOut};
			{/if}
			{* Set Search Result Display Mode on Searchbox *}
			{if !$onInternalIP}
			AspenDiscovery.Searches.getPreferredDisplayMode();
			AspenDiscovery.Archive.getPreferredDisplayMode();
			{/if}
			{literal}
		});
		{/literal}
	</script>{strip}

	{if $includeAutoLogoutCode == true}
		{if $debugJs}
			<script type="text/javascript" src="/interface/themes/responsive/js/aspen/autoLogout.js?v={$gitBranch|urlencode}"></script>
		{else}
			<script type="text/javascript" src="/interface/themes/responsive/js/aspen/autoLogout.min.js?v={$gitBranch|urlencode}"></script>
		{/if}
	{/if}
{/strip}