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
		<script src="/interface/themes/responsive/js/lib/respond.min.js?v={$gitBranch|urlencode}.{$cssJsCacheCounter}"></script>
	{/if}

	{* This is all merged using the merge_javascript.php file called automatically with a File Watcher*}
	{* Code is minified using uglify.js *}

{if in_array($userLanguage, $rtl_langs) || in_array($mynewLanguage, $rtl_langs) || in_array($language, $rtl_langs)}

	<script src="/interface/themes/responsive/js-rtl/aspen.js?v={$gitBranch|urlencode}.{$cssJsCacheCounter}"></script>

{else}
<script src="/interface/themes/responsive/js/aspen.js?v={$gitBranch|urlencode}.{$cssJsCacheCounter}"></script>

{/if}

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
			{/if}
			{if $userHasCatalogConnection}
				Globals.hasILSConnection = true;
			{/if}
			{if array_key_exists('Axis 360', $enabledModules)}
				Globals.hasAxis360Connection = true;
			{/if}
			{if array_key_exists('Cloud Library', $enabledModules)}
				Globals.hasCloudLibraryConnection = true;
			{/if}
			{if array_key_exists('Hoopla', $enabledModules)}
				Globals.hasHooplaConnection = true;
			{/if}
			{if array_key_exists('OverDrive', $enabledModules)}
				Globals.hasOverDriveConnection = true;
			{/if}
			Globals.loadingTitle = '{translate text="Loading" inAttribute=true isPublicFacing=true}';
			Globals.loadingBody = '{translate text="Loading, please wait" inAttribute=true isPublicFacing=true}';
			Globals.requestFailedTitle = '{translate text="Request Failed" inAttribute=true isPublicFacing=true}';
			Globals.requestFailedBody = '{translate text="There was an error with this AJAX Request." inAttribute=true isPublicFacing=true}';
			{literal}
		});
		{/literal}
	</script>{strip}

	{if $includeAutoLogoutCode == true}
		{if $debugJs}
			<script type="text/javascript" src="/interface/themes/responsive/js/aspen/autoLogout.js?v={$gitBranch|urlencode}.{$cssJsCacheCounter}"></script>
		{else}
			<script type="text/javascript" src="/interface/themes/responsive/js/aspen/autoLogout.min.js?v={$gitBranch|urlencode}.{$cssJsCacheCounter}"></script>
		{/if}
	{/if}
{/strip}