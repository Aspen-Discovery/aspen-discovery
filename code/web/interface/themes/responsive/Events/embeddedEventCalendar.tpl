<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html lang="{$userLang->code}" class="embeddedEventCalendar">
{strip}
	<head>
		<title>{translate text="Event Calendar" isPublicFacing=true isAdminEnteredData=true}</title>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />

		{include file="cssAndJsIncludes.tpl" includeAutoLogoutCode=false}
		{$themeCss}

		{if !empty($resizeIframe)}
		<script type="text/javascript" src="/js/iframeResizer/iframeResizer.contentWindow.min.js"></script>
		{/if}

		<base href="" target="_parent" />
	</head>

	<body class="embeddedEventCalendarBody">
		<div class="container-fluid">
			{include file='Events/calendar.tpl'}
		</div>
	</body>
</html>
{/strip}
