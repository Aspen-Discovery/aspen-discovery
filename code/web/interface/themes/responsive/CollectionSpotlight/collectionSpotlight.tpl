<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html lang="{$userLang->code}" class="embeddedCollectionSpotlight">
{strip}
	<head>
		{assign var='spotlightName' value=$collectionSpotlight->name}
		<title>{translate text=$spotlightName isPublicFacing=true isAdminEnteredData=true}</title>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />

		{include file="cssAndJsIncludes.tpl" includeAutoLogoutCode=false}
		{$themeCss}

		{if !empty($resizeIframe)}
		<script type="text/javascript" src="/js/iframeResizer/iframeResizer.contentWindow.min.js"></script>
		{/if}

		{if $collectionSpotlight->customCss}
			<link rel="stylesheet" type="text/css" href="{$collectionSpotlight->customCss}" />
		{/if}
		<base href="" target="_parent" />
	</head>

	<body class="embeddedCollectionSpotlightBody">
		<div class="container-fluid">
			{include file='CollectionSpotlight/collectionSpotlightTabs.tpl'}
		</div>
	</body>
</html>
{/strip}