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
	{if $debugJs}

		<script src="/js/jquery-1.11.0.min.js?v={$gitBranch|urlencode}"></script>
		{* Load Libraries*}
		<script src="/interface/themes/responsive/js/lib/jquery.tablesorter.min.js?v={$gitBranch|urlencode}"></script>
		<script src="/interface/themes/responsive/js/lib/jquery.tablesorter.pager.min.js?v={$gitBranch|urlencode}"></script>
		<script src="/interface/themes/responsive/js/lib/jquery.tablesorter.widgets.min.js?v={$gitBranch|urlencode}"></script>
		{*<script src="/interface/themes/responsive/js/lib/jquery.validate.js"></script>*}
		<script src="/interface/themes/responsive/js/lib/jquery.validate.min.js?v={$gitBranch|urlencode}"></script>

		<script src="/interface/themes/responsive/js/lib/recaptcha_ajax.js?v={$gitBranch|urlencode}"></script>
		{* Combined into ratings.js (part of the aspen.min.js)*}
		{*<script src="/interface/themes/responsive/js/lib/rater.min.js"></script>*}
		{*<script src="/interface/themes/responsive/js/lib/rater.js"></script>*}
		<script src="/interface/themes/responsive/js/lib/bootstrap.min.js?v={$gitBranch|urlencode}"></script>
		<script src="/interface/themes/responsive/js/lib/jcarousel.js?v={$gitBranch|urlencode}"></script>
		<script src="/interface/themes/responsive/js/lib/bootstrap-datepicker.js?v={$gitBranch|urlencode}"></script>
		<script src="/interface/themes/responsive/js/lib/jquery-ui-1.10.4.custom.min.js?v={$gitBranch|urlencode}"></script>
		<script src="/interface/themes/responsive/js/lib/bootstrap-switch.min.js?v={$gitBranch|urlencode}"></script>
		<script src="/interface/themes/responsive/js/lib/jquery.touchwipe.min.js?v={$gitBranch|urlencode}"></script>
		<script src="/interface/themes/responsive/js/lib/jquery.rwdImageMaps.min.js?v={$gitBranch|urlencode}"></script>

		{* Load application specific Javascript *}
		<script src="/interface/themes/responsive/js/aspen/globals.js?v={$gitBranch|urlencode}"></script>
		<script src="/interface/themes/responsive/js/aspen/base.js?v={$gitBranch|urlencode}"></script>
		<script src="/interface/themes/responsive/js/aspen/account.js?v={$gitBranch|urlencode}"></script>
		<script src="/interface/themes/responsive/js/aspen/admin.js?v={$gitBranch|urlencode}"></script>
		<script src="/interface/themes/responsive/js/aspen/archive.js?v={$gitBranch|urlencode}"></script>
		<script src="/interface/themes/responsive/js/aspen/authors.js?v={$gitBranch|urlencode}"></script>
		<script src="/interface/themes/responsive/js/aspen/browse.js?v={$gitBranch|urlencode}"></script>
		<script src="/interface/themes/responsive/js/aspen/cloud-library.js?v={$gitBranch|urlencode}"></script>
		<script src="/interface/themes/responsive/js/aspen/dpla.js?v={$gitBranch|urlencode}"></script>
		<script src="/interface/themes/responsive/js/aspen/econtent-record.js?v={$gitBranch|urlencode}"></script>
		<script src="/interface/themes/responsive/js/aspen/grouped-work.js?v={$gitBranch|urlencode}"></script>
		<script src="/interface/themes/responsive/js/aspen/lists.js?v={$gitBranch|urlencode}"></script>
		<script src="/interface/themes/responsive/js/aspen/collection-spotlights.js?v={$gitBranch|urlencode}"></script>
		<script src="/interface/themes/responsive/js/aspen/materials-request.js?v={$gitBranch|urlencode}"></script>
		<script src="/interface/themes/responsive/js/aspen/menu.js?v={$gitBranch|urlencode}"></script>
		<script src="/interface/themes/responsive/js/aspen/overdrive.js?v={$gitBranch|urlencode}"></script>
		<script src="/interface/themes/responsive/js/aspen/open-archives.js?v={$gitBranch|urlencode}"></script>
		<script src="/interface/themes/responsive/js/aspen/hoopla.js?v={$gitBranch|urlencode}"></script>
		<script src="/interface/themes/responsive/js/aspen/prospector.js?v={$gitBranch|urlencode}"></script>
		<script src="/interface/themes/responsive/js/aspen/ratings.js?v={$gitBranch|urlencode}"></script>
		<script src="/interface/themes/responsive/js/aspen/rbdigital.js?v={$gitBranch|urlencode}"></script>
		<script src="/interface/themes/responsive/js/aspen/reading-history.js?v={$gitBranch|urlencode}"></script>
		<script src="/interface/themes/responsive/js/aspen/record.js?v={$gitBranch|urlencode}"></script>
		<script src="/interface/themes/responsive/js/aspen/responsive.js?v={$gitBranch|urlencode}"></script>
		<script src="/interface/themes/responsive/js/aspen/results-list.js?v={$gitBranch|urlencode}"></script>
		<script src="/interface/themes/responsive/js/aspen/searches.js?v={$gitBranch|urlencode}"></script>
		<script src="/interface/themes/responsive/js/aspen/title-scroller.js?v={$gitBranch|urlencode}"></script>
		<script src="/interface/themes/responsive/js/aspen/websites.js?v={$gitBranch|urlencode}"></script>
		<script src="/interface/themes/responsive/js/aspen/wikipedia.js?v={$gitBranch|urlencode}"></script>
	{else}
		{* This is all merged using the merge_javascript.php file called automatically with a File Watcher*}
		{* Code is minified using uglify.js *}
		<script src="/interface/themes/responsive/js/aspen.min.js?v={$gitBranch|urlencode}"></script>
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
			{*Globals.masqueradeMode = {if $masqueradeMode}true{else}false{/if};*}
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
			{if !$onInternalIP}AspenDiscovery.Searches.getPreferredDisplayMode();AspenDiscovery.Archive.getPreferredDisplayMode();{/if}
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