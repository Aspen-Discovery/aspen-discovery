<!DOCTYPE html>
<html lang="{$userLang}">
	<head prefix="og: http://ogp.me/ns#">{strip}
		<title>{$pageTitle|truncate:64:"..."}</title>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
		{literal}
		<script src="https://use.typekit.net/uew0ppi.js"></script>
		<script>try{Typekit.load({ async: true });}catch(e){}</script>
		{/literal}
		{if $google_translate_key}
			<meta name="google-translate-customization" content="{$google_translate_key}">
		{/if}
		{if $google_verification_key}
			<meta name="google-site-verification" content="{$google_verification_key}">
		{/if}
		{if $addHeader}{$addHeader}{/if}
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

		{if $metadataTemplate}
			{include file=$metadataTemplate}
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

					{if isset($sidebar)} {* Main Content & Sidebars *}

						{if $sideBarOnRight} {* Sidebar on the right *}
							<div class="col-xs-12 col-sm-8 col-md-9 col-lg-9" id="main-content-with-sidebar" style="overflow-x: scroll;">
								{* If main content overflows, use a scrollbar *}
								{include file="breadcrumbs.tpl"}
								{include file="$module/$pageTemplate"}
							</div>
							<div class="col-xs-12 col-sm-4 col-md-3 col-lg-3" id="side-bar">
								{include file="sidebar.tpl"}
							</div>

						{else} {* Sidebar on the left *}
							<div class="col-xs-12 col-sm-4 col-md-3 col-lg-3" id="side-bar">
								{include file="sidebar.tpl"}
							</div>
							<div class="col-xs-12 col-sm-8 col-md-9 col-lg-9" id="main-content-with-sidebar">
								{include file="breadcrumbs.tpl"}
								{include file="$module/$pageTemplate"}
							</div>
						{/if}

					{else} {* Main Content Only, no sidebar *}
						{include file="$module/$pageTemplate"}
					{/if}
				</div>
			</div>


		</div>

        <div id="footer-container" class="row">
            {include file="footer_responsive.tpl"}
        </div>

		{include file="modal_dialog.tpl"}

		{include file="tracking.tpl"}

		{if $semanticData}
			{include file="jsonld.tpl"}
		{/if}
		{/strip}
	</body>
</html>
