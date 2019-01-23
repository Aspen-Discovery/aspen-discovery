{strip}
	{if $wikipediaData}
		{$wikipediaData.description}
		<div class="row smallText">
			<div class="col-xs-12">
				<a href="http://{$wiki_lang}.wikipedia.org/wiki/{$wikipediaData.name|escape:"url"}" rel="external" onclick="window.open (this.href, 'child'); return false"><span class="note">{translate text='wiki_link'}</span></a>
			</div>
		</div>
	{/if}
{/strip}