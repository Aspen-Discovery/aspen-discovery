{* Add Google Analytics*}
{if $googleAnalyticsId || $googleAnalyticsLinkingId}
	<script type="text/javascript">
		{if $googleAnalyticsId}
		{literal}
		var _gaq = _gaq || [];
		_gaq.push(['_setAccount', '{/literal}{$googleAnalyticsId}{literal}']);
		_gaq.push(['_setCustomVar', 1, 'theme', {/literal}'{$primaryTheme}'{literal}, '2']);
		_gaq.push(['_setCustomVar', 2, 'mobile', {/literal}'{$isMobile}'{literal}, '2']);
		_gaq.push(['_setCustomVar', 3, 'physicalLocation', {/literal}'{$physicalLocation}'{literal}, '2']);
		_gaq.push(['_setCustomVar', 4, 'pType', {/literal}'{$pType}'{literal}, '2']);
		_gaq.push(['_setCustomVar', 5, 'homeLibrary', {/literal}'{$homeLibrary}'{literal}, '2']);
		_gaq.push(['_setDomainName', {/literal}'{$googleAnalyticsDomainName}'{literal}]);
		_gaq.push(['_trackPageview']);
		_gaq.push(['_trackPageLoadTime']);

		(function() {
			var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
			ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
			var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		})();

		{/literal}
		{/if}
		{if $googleAnalyticsLinkingId}
		{* Multi-site linking code for Arlingtion *}
		{literal}

		(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');
		ga('create', '{/literal}{$googleAnalyticsLinkingId}{literal}', 'auto', {'allowLinker': true});
		ga('require', 'linker');
		ga('linker:autoLink', ['library.arlingtonva.us', 'libsys.arlingtonva.us', 'm.libsys.arlingtonva.us', 'arlingtonva.libcal.com', 'library.arlingtonva.libguides.com', 'libraryarchives.arlingtonva.us', 'projectdaps.org', 'nauck.omeka.net'] );
		ga('send', 'pageview');
		{/literal}
		{/if}
	</script>
{/if}