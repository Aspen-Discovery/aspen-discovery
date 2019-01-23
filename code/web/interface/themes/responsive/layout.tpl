<!DOCTYPE html>
<html lang="{$userLang}">
	<head prefix="og: http://ogp.me/ns#">{strip}
		<title>{$pageTitle|truncate:64:"..."}</title>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
		{if $google_translate_key}
			<meta name="google-translate-customization" content="{$google_translate_key}">
		{/if}
		{if $google_verification_key}
			<meta name="google-site-verification" content="{$google_verification_key}">
		{/if}
		{if $addHeader}{$addHeader}{/if}

		{if $metadataTemplate}
			{include file=$metadataTemplate}
		{/if}
		<meta property="og:site_name" content="{$site.title|removeTrailingPunctuation|escape:html}" />
		{if $og_title}
			<meta property="og:title" content="{$og_title|removeTrailingPunctuation|escape:html}" />
		{/if}
		{if $og_type}
			<meta property="og:type" content="{$og_type|escape:html}" />
		{/if}
		{if $og_image}
			<meta property="og:image" content="{$og_image|escape:html}" />
		{/if}
		{if $og_url}
			<meta property="og:url" content="{$og_url|escape:html}" />
		{/if}
		<link type="image/x-icon" href="{img filename=favicon.png}" rel="shortcut icon">
		<link rel="search" type="application/opensearchdescription+xml" title="{$site.title} Catalog Search" href="{$path}/Search/OpenSearch?method=describe">

		{include file="cssAndJsIncludes.tpl"}
		{/strip}
	</head>
	<body class="module_{$module} action_{$action}{if $masqueradeMode} masqueradeMode{/if}" id="{$module}-{$action}">
		{if $masqueradeMode}
			{include file="masquerade-top-navbar.tpl"}
		{/if}
		{strip}
			<div class="container">
			{if $systemMessage}
				<div id="system-message-header" class="row">{$systemMessage}</div>
			{/if}
			<div class="row breadcrumbs">
				<div class="col-xs-12 col-sm-9">
					{* if $showBreadcrumbs}
					<ul class="breadcrumb">
						<li><a href="{$homeBreadcrumbLink}" id="home-breadcrumb"> {translate text=$homeLinkText}</a> <span class="divider">&raquo;</span></li>
						{include file="$module/breadcrumbs.tpl"}
					</ul>
					{/if *}
				</div>
				<a id="top"></a>
				<div class="col-xs-12 col-sm-3 text-right">
					{if $google_translate_key}
						{literal}
						<div id="google_translate_element">
						</div>
						{/literal}
					{/if}
				</div>
			</div>

			<div id="header-wrapper" class="row">
				<div id="header-container">
					{include file='header_responsive.tpl'}
				</div>
			</div>

			<div id="horizontal-menu-bar-wrapper" class="row visible-xs">
				<div id="horizontal-menu-bar-container" class="col-tn-12 col-xs-12 menu-bar">
					{include file='horizontal-menu-bar.tpl'}
				</div>
			</div>

			{if $horizontalSearchBar}
				<div id="horizontal-search-wrapper" class="row">
					<div id="horizontal-search-container" class="col-xs-12">
						{include file="Search/horizontal-searchbox.tpl"}
					</div>
				</div>
			{/if}

			<div id="content-container">
				<div class="row">

					{if !empty($sidebar)} {* Main Content & Sidebars *}

						{if $sideBarOnRight}  {*Sidebar on the right *}
							<div class="rightSidebar col-xs-12 col-sm-4 col-sm-push-8 col-md-3 col-md-push-9 col-lg-3 col-lg-push-9" id="side-bar">
								{include file="sidebar.tpl"}
							</div>
							<div class="rightSidebar col-xs-12 col-sm-8 col-sm-pull-4 col-md-9 col-md-pull-3 col-lg-9 col-lg-pull-3" id="main-content-with-sidebar" style="overflow-x: auto;">
								{* If main content overflows, use a scrollbar *}
								{if $showBreadcrumbs}
									{include file="breadcrumbs.tpl"}
								{/if}
								{if $module}
									{include file="$module/$pageTemplate"}
								{else}
									{include file="$pageTemplate"}
								{/if}
							</div>

						{else} {* Sidebar on the left *}
							<div class="col-xs-12 col-sm-4 col-md-3 col-lg-3 " id="side-bar">
								{include file="sidebar.tpl"}
							</div>
							<div class="col-xs-12 col-sm-8 col-md-9 col-lg-9" id="main-content-with-sidebar">
								{if $showBreadcrumbs}
									{include file="breadcrumbs.tpl"}
								{/if}
								{if $module}
									{include file="$module/$pageTemplate"}
								{else}
									{include file="$pageTemplate"}
								{/if}
							</div>
						{/if}

					{else} {* Main Content Only, no sidebar *}
						{if $module}
							{include file="$module/$pageTemplate"}
						{else}
							{include file="$pageTemplate"}
						{/if}
					{/if}
				</div>
			</div>

			<div id="footer-container" class="row">
				{include file="footer_responsive.tpl"}
			</div>

		</div>

		{include file="modal_dialog.tpl"}

		{include file="tracking.tpl"}

			{if $semanticData}
				{include file="jsonld.tpl"}
			{/if}
		{/strip}

		{if $google_translate_key}
		{literal}
			<script type="text/javascript">
				function googleTranslateElementInit() {
				new google.translate.TranslateElement({
				pageLanguage: 'en',
				layout: google.translate.TranslateElement.InlineLayout.SIMPLE
		{/literal}
			{if $google_included_languages}
			, includedLanguages: '{$google_included_languages}'
			{/if}
		{literal}
				}, 'google_translate_element');
				}
			</script>
			<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
		{/literal}
		{/if}
	</body>
</html>