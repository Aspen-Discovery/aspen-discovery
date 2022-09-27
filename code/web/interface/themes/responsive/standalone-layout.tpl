<!DOCTYPE html>
<html lang="{$userLang->code}">
<head prefix="og: http://ogp.me/ns#">
	{strip}
		<title>{$pageTitleShortAttribute|truncate:64:"..."}{if !$isMobile} | {$librarySystemName}{/if}</title>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
		{if !empty($google_verification_key)}
			<meta name="google-site-verification" content="{$google_verification_key}">
		{/if}

		{if !empty($metadataTemplate)}
			{include file=$metadataTemplate}
		{/if}
		<meta property="og:site_name" content="{$site.title|removeTrailingPunctuation|escape:html}"/>
		{if !empty($og_title)}
			<meta property="og:title" content="{$og_title|removeTrailingPunctuation|escape:html}"/>
		{/if}
		{if !empty($og_type)}
			<meta property="og:type" content="{$og_type|escape:html}"/>
		{/if}
		{if !empty($og_image)}
			<meta property="og:image" content="{$og_image|escape:html}"/>
		{/if}
		{if !empty($og_url)}
			<meta property="og:url" content="{$og_url|escape:html}"/>
		{/if}
		<link type="image/x-icon" href="{$favicon}" rel="shortcut icon">
		<link rel="search" type="application/opensearchdescription+xml" title="{$site.title} Catalog Search" href="/Search/OpenSearch?method=describe">
		{include file="cssAndJsIncludes.tpl"}
		{$themeCss}
	{/strip}
</head>
<body class="module_{$module} action_{$action}{if $masqueradeMode} masqueradeMode{/if}{if $loggedIn} loggedIn{else} loggedOut{/if}" id="{$module}-{$action}">
{if $masqueradeMode}
	{include file="masquerade-top-navbar.tpl"}
{/if}
{strip}
	<div {if !$fullWidthTheme}class="container"{/if} id="page-container">
{*
		{if !empty($systemMessage)}
			<div id="system-message-header" class="row">{$systemMessage}</div>
		{/if}
*}

		{if $enableLanguageSelector}
			{include file="language-selection-navbar.tpl"}
		{/if}
		{if $showLanguagePreferencesBar}
			{include file="languagePreferences.tpl"}
		{/if}

		<div {if $fullWidthTheme}class="container-fluid"{/if} id="page-header">
			<div id="header-wrapper" class="row {if $fullWidthTheme}row-no-gutters fullWidth{/if}">
				<div id="header-container" role="banner">
					{include file='standalone-header_responsive.tpl'}
				</div>
			</div>
		</div>

		{if $fullWidthTheme}<div class="container">{/if}
		<div id="content-container">
			<div class="row">
				<div class="col-xs-12" id="main-content">
					<div role="main">
						{if $module}
							{include file="$module/$pageTemplate"}
						{else}
							{include file="$pageTemplate"}
						{/if}
					</div>
				</div>
			</div>
		</div>
		{if $fullWidthTheme}</div>{/if}

		<div {if $fullWidthTheme}class="container-fluid"{/if} id="page-footer">
			<div id="footer-container" class="row {if $fullWidthTheme}row-no-gutters{/if}" role="contentinfo">
				{include file="footer_responsive.tpl"}
			</div>
		</div>

	</div>
	{include file="modal_dialog.tpl"}

	{include file="tracking.tpl"}

	{if !empty($semanticData)}
		{include file="jsonld.tpl"}
	{/if}
{/strip}

</body>
</html>