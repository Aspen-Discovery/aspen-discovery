{strip}
	<div class="alert alert-info">{translate text="NoveList provides detailed suggestions for series you might like if you enjoyed this book.  Suggestions are based on recommendations from librarians and other contributors." isPublicFacing=true}</div>
	<div id="similarSeriesNoveList" class="striped div-striped">
		{foreach from=$similarSeries item=series name="recordLoop"}
			<div class="novelist-similar-item">
				<div class="novelist-similar-item-header notranslate">
					<a href="/Search/Results?lookfor={$series.title|escape:url}">{$series.title}</a>{if !empty($series.author)} - <a class="notranslate" href="/Search/Results?lookfor={$series.author|escape:url}">{$series.author}{/if}</a>
				</div>
				<div class="novelist-similar-item-reason">
					{$series.reason}
				</div>
			</div>
		{/foreach}
	</div>
{/strip}