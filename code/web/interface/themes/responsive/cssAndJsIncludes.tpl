{strip}
	{* All CSS should be come before javascript for better browser performance *}
	{* TODO: Fix minification of css *}
	{if !empty($debugCss) || true}
		{css filename="main.css"}
	{else}
		{css filename="main.min.css"}
	{/if}
	{if !empty($additionalCss)}
		<style>
			{$additionalCss}
		</style>
	{/if}

	{* Include correct all javascript *}
	{if !empty($ie8)}
		{* include to give responsive capability to ie8 browsers, but only on successful detection of those browsers. For that reason, don't include in aspen.min.js *}
		<script src="/interface/themes/responsive/js/lib/respond.min.js?v={$aspenVersion|urlencode}.{$cssJsCacheCounter}"></script>
	{/if}

	{* This is all merged using the merge_javascript.php file called automatically with a File Watcher*}
	{* Code is minified using uglify.js *}
	<script src="/interface/themes/responsive/js/aspen.js?v={$aspenVersion|urlencode}.{$cssJsCacheCounter}"></script>

	{/strip}
	<script type="text/javascript">
		{* Override variables as needed *}
		{literal}
			$(document).ready(function(){
		{/literal}
			Globals.url = '{$url}';
			Globals.loggedIn = {if !empty($loggedIn)}true{else}false{/if};
			Globals.opac = {if !empty($onInternalIP)}true{else}false{/if};
			Globals.activeModule = '{$module}';
			Globals.activeAction = '{$action}';
			Globals.masqueradeMode = {if !empty($masqueradeMode)}true{else}false{/if};
			{if !empty($repositoryUrl)}
				Globals.repositoryUrl = '{$repositoryUrl}';
				Globals.encodedRepositoryUrl = '{$encodedRepositoryUrl}';
			{/if}

			{if !empty($automaticTimeoutLength)}
				Globals.automaticTimeoutLength = {$automaticTimeoutLength};
			{/if}
			{if !empty($automaticTimeoutLengthLoggedOut)}
				Globals.automaticTimeoutLengthLoggedOut = {$automaticTimeoutLengthLoggedOut};
			{/if}
			{* Set Search Result Display Mode on Searchbox *}
			{if empty($onInternalIP)}
				AspenDiscovery.Searches.getPreferredDisplayMode();
			{/if}
			{if !empty($userHasCatalogConnection)}
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
			{if array_key_exists('Palace Project', $enabledModules)}
				Globals.hasPalaceProjectConnection = true;
			{/if}
			{if !empty($hasInterlibraryLoanConnection)}
				Globals.hasInterlibraryLoanConnection = true;
			{/if}
			Globals.loadingTitle = '{translate text="Loading" inAttribute=true isPublicFacing=true}';
			Globals.loadingBody = '{translate text="Loading, please wait" inAttribute=true isPublicFacing=true}';
			Globals.requestFailedTitle = '{translate text="Request Failed" inAttribute=true isPublicFacing=true}';
			Globals.requestFailedBody = '{translate text="There was an error with this AJAX Request." inAttribute=true isPublicFacing=true}';
			Globals.rtl = {if $userLang->isRTL()}true{else}false{/if};
			Globals.bypassAspenLoginForSSO = {if $bypassAspenPatronLogin}true{else}false{/if};
			Globals.ssoLoginUrl = '{$bypassLoginUrl}';
			AspenDiscovery.Browse.browseStyle = '{$browseStyle}';
			Globals.cookiePolicyHTML = '{$cookieStorageConsentHTML|escape:javascript|regex_replace:"/[\r\n]/" : " "}';
			{if !empty($timeUntilSessionExpiration)}
				Globals.timeUntilSessionExpiration = {$timeUntilSessionExpiration};
			{/if}
			Globals.language = '{$userLang->code}';
		{literal}
			});
		{/literal}
	</script>{strip}

	{if !empty($includeAutoLogoutCode)}
		{if !empty($debugJs)}
			<script type="text/javascript" src="/interface/themes/responsive/js/aspen/autoLogout.js?v={$aspenVersion|urlencode}.{$cssJsCacheCounter}"></script>
		{else}
			<script type="text/javascript" src="/interface/themes/responsive/js/aspen/autoLogout.min.js?v={$aspenVersion|urlencode}.{$cssJsCacheCounter}"></script>
		{/if}
	{/if}
	<script type="text/javascript">
		{literal}
		$(document).ready(function(){
			if (Globals.timeUntilSessionExpiration > 0) {
				setTimeout(function (){
					// noinspection SillyAssignmentJS
					window.location.reload();
				}, Globals.timeUntilSessionExpiration + 500);
			}
		});
		{/literal}
	</script>
{/strip}
