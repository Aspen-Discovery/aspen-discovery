{strip}
	{* All CSS should be come before javascript for better browser performance *}
	{if $debugCss}
    {css filename="main.css"}
	{else}
		{css filename="main.min.css"}
	{/if}
	{if $additionalCss}
		<style type="text/css">
			{$additionalCss}
		</style>
	{/if}

	{* Include correct all javascript *}
	{if $ie8}
		{* include to give responsive capability to ie8 browsers, but only on successful detection of those browsers. For that reason, don't include in vufind.min.js *}
		<script src="{$path}/interface/themes/responsive/js/lib/respond.min.js?v={$gitBranch|urlencode}"></script>
	{/if}
	{if $debugJs}

		<script src="{$path}/js/jquery-1.9.1.min.js?v={$gitBranch|urlencode}"></script>
		{* Load Libraries*}
		<script src="{$path}/interface/themes/responsive/js/lib/jquery.tablesorter.min.js?v={$gitBranch|urlencode}"></script>
		<script src="{$path}/interface/themes/responsive/js/lib/jquery.tablesorter.pager.min.js?v={$gitBranch|urlencode}"></script>
		<script src="{$path}/interface/themes/responsive/js/lib/jquery.tablesorter.widgets.min.js?v={$gitBranch|urlencode}"></script>
		{*<script src="{$path}/interface/themes/responsive/js/lib/jquery.validate.js"></script>*}
		<script src="{$path}/interface/themes/responsive/js/lib/jquery.validate.min.js?v={$gitBranch|urlencode}"></script>

		<script src="{$path}/interface/themes/responsive/js/lib/recaptcha_ajax.js?v={$gitBranch|urlencode}"></script>
		{* Combined into ratings.js (part of the vufind.min.js)*}
		{*<script src="{$path}/interface/themes/responsive/js/lib/rater.min.js"></script>*}
		{*<script src="{$path}/interface/themes/responsive/js/lib/rater.js"></script>*}
		<script src="{$path}/interface/themes/responsive/js/lib/bootstrap.min.js?v={$gitBranch|urlencode}"></script>
		<script src="{$path}/interface/themes/responsive/js/lib/jcarousel.js?v={$gitBranch|urlencode}"></script>
		<script src="{$path}/interface/themes/responsive/js/lib/bootstrap-datepicker.js?v={$gitBranch|urlencode}"></script>
		<script src="{$path}/interface/themes/responsive/js/lib/jquery-ui-1.10.4.custom.min.js?v={$gitBranch|urlencode}"></script>
		<script src="{$path}/interface/themes/responsive/js/lib/bootstrap-switch.min.js?v={$gitBranch|urlencode}"></script>
		<script src="{$path}/interface/themes/responsive/js/lib/jquery.touchwipe.min.js?v={$gitBranch|urlencode}"></script>
		<script src="{$path}/interface/themes/responsive/js/lib/lightbox.js?v={$gitBranch|urlencode}"></script>
		<script src="{$path}/interface/themes/responsive/js/lib/jquery.rwdImageMaps.min.js?v={$gitBranch|urlencode}"></script>

		{* Load application specific Javascript *}
		<script src="{$path}/interface/themes/responsive/js/vufind/globals.js?v={$gitBranch|urlencode}"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/base.js?v={$gitBranch|urlencode}"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/account.js?v={$gitBranch|urlencode}"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/admin.js?v={$gitBranch|urlencode}"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/archive.js?v={$gitBranch|urlencode}"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/analytic-reports.js?v={$gitBranch|urlencode}"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/browse.js?v={$gitBranch|urlencode}"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/dpla.js?v={$gitBranch|urlencode}"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/econtent-record.js?v={$gitBranch|urlencode}"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/grouped-work.js?v={$gitBranch|urlencode}"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/lists.js?v={$gitBranch|urlencode}"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/lists-widgets.js?v={$gitBranch|urlencode}"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/materials-request.js?v={$gitBranch|urlencode}"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/menu.js?v={$gitBranch|urlencode}"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/overdrive.js?v={$gitBranch|urlencode}"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/hoopla.js?v={$gitBranch|urlencode}"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/prospector.js?v={$gitBranch|urlencode}"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/ratings.js?v={$gitBranch|urlencode}"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/reading-history.js?v={$gitBranch|urlencode}"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/record.js?v={$gitBranch|urlencode}"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/responsive.js?v={$gitBranch|urlencode}"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/results-list.js?v={$gitBranch|urlencode}"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/searches.js?v={$gitBranch|urlencode}"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/title-scroller.js?v={$gitBranch|urlencode}"></script>
		<script src="{$path}/interface/themes/responsive/js/vufind/wikipedia.js?v={$gitBranch|urlencode}"></script>
	{else}
		{* This is all merged using the merge_javascript.php file called automatically with a File Watcher*}
		{* Code is minified using uglify.js *}
		<script src="{$path}/interface/themes/responsive/js/vufind.min.js?v={$gitBranch|urlencode}"></script>
	{/if}

	{/strip}
  <script type="text/javascript">
		{* Override variables as needed *}
		{literal}
		$(document).ready(function(){{/literal}
			Globals.path = '{$path}';
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
			{if !$onInternalIP}VuFind.Searches.getPreferredDisplayMode();VuFind.Archive.getPreferredDisplayMode();{/if}
			{literal}
		});
		{/literal}
	</script>{strip}

	{if $includeAutoLogoutCode == true}
		{if $debugJs}
			<script type="text/javascript" src="{$path}/interface/themes/responsive/js/vufind/autoLogout.js?v={$gitBranch|urlencode}"></script>
		{else}
			<script type="text/javascript" src="{$path}/interface/themes/responsive/js/vufind/autoLogout.min.js?v={$gitBranch|urlencode}"></script>
		{/if}
	{/if}
{/strip}