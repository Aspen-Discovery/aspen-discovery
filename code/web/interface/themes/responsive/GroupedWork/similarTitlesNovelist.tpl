{strip}
	<div class="alert alert-info">NoveList provides detailed suggestions for titles you might like if you enjoyed this book.  Suggestions are based on recommendations from librarians and other contributors.</div>
	<div id="similarTitlesNovelist" class="striped div-striped">
		{foreach from=$similarTitles item=similarTitle name="recordLoop"}
			<div class="novelist-similar-item row">
				<div class="coversColumn col-xs-3 col-sm-3 col-md-3 col-lg-2 text-center">
					{if isset($similarTitle.fullRecordLink)}
						<a href='{$similarTitle.fullRecordLink}' aria-hidden="true"><img src="{$similarTitle.smallCover}" alt="{translate text='Cover Image' inAttribute=true}" class="listResultImage img-thumbnail"/></a>
					{/if}
				</div>
				<div class="col-xs-9 col-lg-10">
					<div class="novelist-similar-item-header notranslate">{if isset($similarTitle.fullRecordLink)}<a href='{$similarTitle.fullRecordLink}'>{/if}{$similarTitle.title|removeTrailingPunctuation}{if isset($similarTitle.fullRecordLink)}</a>{/if}
						&nbsp;by <a href="/Search/Results?lookfor={$similarTitle.author|escape:url}" class="notranslate">{$similarTitle.author}</a></div>
					<div class="novelist-similar-item-reason">
						{$similarTitle.reason}
					</div>
				</div>
			</div>
		{/foreach}
	</div>
{/strip}