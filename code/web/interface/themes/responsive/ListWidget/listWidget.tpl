<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html lang="{$userLang}" class="embeddedListWidget">
{strip}
<head>
	<title>{$widget->name}</title>
  <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />

	{include file="cssAndJsIncludes.tpl" includeAutoLogoutCode=false}
	{*TODO a smaller suite of javascript for List Widgets*}

	{if $resizeIframe}
	<script type="text/javascript" src="{$path}/js/iframeResizer/iframeResizer.contentWindow.min.js"></script>
	{/if}

  {if $widget->customCss}
  	<link rel="stylesheet" type="text/css" href="{$widget->customCss}" />
  {/if}
  <base href="{$path}" target="_parent" />
</head>

<body class="embeddedListWidgetBody">
	<div class="container-fluid">
		{include file='ListWidget/listWidgetTabs.tpl'}
  </div>
</body>
</html>
{/strip}