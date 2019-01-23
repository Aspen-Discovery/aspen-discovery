{strip}
	{* TODO: put this in a help pop-up
	<div class="alert alert-info">NoveList provides detailed suggestions for series you might like if you enjoyed this book.  Suggestions are based on recommendations from librarians and other contributors.</div>
	*}
	<div id="similarSeriesNoveList" class="jcarousel ajax-carousel col-tn-12">
		<ul>
			{foreach from=$similarSeries item=series name="recordLoop"}
				<li{* class="novelist-similar-item"*}>
					<div class="novelist-similar-item-header notranslate"><a href="/Search/Results?lookfor={$series.title|escape:url}">{$series.title}</a> by <a class="notranslate" href="Search/Results?lookfor={$series.author|escape:url}">{$series.author}</a></div>
					<div class="novelist-similar-item-reason">
						{$series.reason}
					</div>
				</li>
			{/foreach}
		</ul>
	</div>
{/strip}