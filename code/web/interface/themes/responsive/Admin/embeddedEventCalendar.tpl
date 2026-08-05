<div id="main-content" class="col-md-12">
	<h1>{translate text="Embed Event Calendar" isAdminFacing=true}</h1>
	<div class="btn-group">
		<a class="btn btn-default" href="{$returnToListUrl|default:'/Events/Events'}"><i class="fas fa-arrow-alt-circle-left" role="presentation"></i> {translate text="Return to List" isAdminFacing=true}</a>
	</div>
	<div id="embeddedEventCalendarHelp">
		<h4>{translate text="Integration Notes" isAdminFacing=true}</h4>
		<div class="well">
			<p>{translate text="To integrate this calendar into another site, insert an iframe into your site with the following source." isAdminFacing=true}</p>
			<blockquote class="alert-info">{$url}/Events/Calendar?embed=true</blockquote>
			<p>
				<code style="white-space: normal">&lt;iframe src=&quot;{$url}/Events/Calendar?embed=true&quot;
					width=&quot;{$width}&quot; height=&quot;{$height}&quot;
					scrolling=&quot;yes&quot;&gt;&lt;/iframe&gt;
				</code>
			</p>
			<p>{translate text="Width and height can be adjusted as needed to fit within your site." isAdminFacing=true}</p>
			<blockquote class="alert-warning">{translate text="Note: Percentage-based values for iframe width and height are not consistently honored on iPads and other iOS devices or browsers. It is recommended to use fixed pixel values instead." isAdminFacing=true}</blockquote>
			<blockquote class="alert-warning">{translate text='Recommendation: Set iframe attribute frameborder="0" and apply any desired border styling through your stylesheet (CSS file).' isAdminFacing=true}</blockquote>
		</div>
	</div>

	<h4>{translate text="Live Preview" isAdminFacing=true}</h4>

	<iframe src="{$url}/Events/Calendar?embed=true&reload=true" width="{$width}" height="{$height}" scrolling="yes" >
		<p>{translate text="Your browser does not support iframes." isAdminFacing=true}</p>
	</iframe>

	<hr>

	<h3>{translate text="Event Calendar with Resizing" isAdminFacing=true}</h3>
	<h4>{translate text="Integration Notes" isAdminFacing=true}</h4>
	<div class="well">
		<p>
			{translate text="To have an event calendar that adjusts its height based on the HTML content within the page, use the following source URL." isAdminFacing=true}
		</p>
		<blockquote class="alert-info">
			{$url}/Events/Calendar?embed=true<span style="font-weight: bold;">&resizeIframe=on</span>
		</blockquote>
		<p>
			{translate text="As shown below, include the iframe tag and JavaScript tags in the site." isAdminFacing=true}
		</p>

		<code style="white-space: normal">
			&lt;iframe id=&quot;eventCalendar{$object->id}&quot;  onload=&quot;setCalendarSizing(this, 30)&quot;  src=&quot;{$url}/Events/Calendar?embed=true&amp;resizeIframe=on&quot;
			width=&quot;{$width}&quot; scrolling=&quot;yes&quot;&gt;&lt;/iframe&gt;
		</code>

		<blockquote class="alert-warning">
			{translate text="Note: This functionality requires that the site embedding the event calendar includes the jQuery library." isAdminFacing=true}
		</blockquote>
	</div>

	<h4>{translate text="Live Preview" isAdminFacing=true}</h4>
	<iframe id="eventCalendar{$object->id}" onload="setCalendarSizing(this, 30)" src="{$url}/Events/Calendar?embed=true&resizeIframe=on&reload=true" width="{$width}" {*height="{$height}"*} scrolling="yes">
		<p>{translate text="Your browser does not support iframes." isAdminFacing=true}</p>
	</iframe>
</div>

{* Iframe dynamic Height Re-sizing script *}
<script type="text/javascript" src="/js/iframeResizer/iframeResizer.min.js"></script>

{* Width Resizing Code *}
<script type="text/javascript">
	$('#eventCalendar{$object->id}').iFrameResize();
</script>

{literal}
	<script type="text/javascript">
		function resizeCalendarWidth(iframe, padding = 4) {
			const $el = $(iframe);
			if (!$el.length) return;

			const data = $el.data('spot') || { original: $el.width(), resized: false };
			const vpw = $(window).width();
			const max = Math.max(0, vpw - 2 * padding);
			const cur = $el.width();

			if (cur > max) {
				$el.width(max);
				data.resized = true;
			} else if (data.resized && data.original + 2 * padding < vpw) {
				$el.width(data.original);
				data.resized = false;
			}

			$el.data('spot', data);
		}

		function setCalendarSizing(iframe, padding = 4) {
			const $el = $(iframe);
			if (!$el.length) return () => {};

			$el.data('spot', { original: $el.width(), resized: false });
			const prevNs = $el.data('spotNs');
			if (prevNs) $(window).off(`resize${prevNs}`);
			// Create a unique namespace for the resize event so just this handler can be unbound later.
			const ns = `.spot${Math.random().toString(36).slice(2, 8)}`;
			const onResize = () => resizeCalendarWidth($el, padding);

			$(window).on(`resize${ns}`, onResize);
			$el.data('spotNs', ns);
			onResize();

			return () => $(window).off(`resize${ns}`);
		}

		window.resizeCalendarWidth = resizeCalendarWidth;
		window.setCalendarSizing = setCalendarSizing;
	</script>
{/literal}

<script type="text/javascript">
	{literal}
	$(() => {
		AspenDiscovery.Admin.initializeScrollPositioning();
	});
	{/literal}
</script>
