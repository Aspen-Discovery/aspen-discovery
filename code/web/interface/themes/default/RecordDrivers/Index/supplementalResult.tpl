<div id="supplementalRecord{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}" class="resultsList">
	<div class="imageColumn">
		{if $disableCoverArt != 1}
		<a href="{$summUrl}">
		<img src="{$bookCoverUrl}" class="listResultImage" alt="{translate text='Cover Image'}"/>
		</a>
		{/if}
	</div>

	<div class="resultDetails">
		<div class="resultItemLine1">
		{if $summScore}({$summScore}) {/if}
		<a href="{$summUrl}" class="title">{if !$summTitle|removeTrailingPunctuation}{translate text='Title not available'}{else}{$summTitle|removeTrailingPunctuation|truncate:180:"..."|highlight}{/if}</a>
		{if $summTitleStatement}
			<div class="searchResultSectionInfo">
				{$summTitleStatement|removeTrailingPunctuation|truncate:180:"..."|highlight}
			</div>
			{/if}
		</div>

		<div class="resultItemLine2">
			{if $summAuthor}
				{translate text='by'}
				{if is_array($summAuthor)}
					{foreach from=$summAuthor item=author}
						<a href='{$path}/Author/Home?author="{$author|escape:"url"}&amp;searchSource=marmot"'>{$author|highlight}</a>
					{/foreach}
				{else}
					<a href='{$path}/Author/Home?author="{$summAuthor|escape:"url"}&amp;searchSource=marmot"'>{$summAuthor|highlight}</a>
				{/if}
			{/if}

			{if $summDate}{translate text='Published'} {$summDate.0|escape}{/if}
		</div>

		<div class="resultItemLine3">
			{if !empty($summSnippetCaption)}<b>{translate text=$summSnippetCaption}:</b>{/if}
			{if !empty($summSnippet)}<span class="quotestart">&#8220;</span>...{$summSnippet|highlight}...<span class="quoteend">&#8221;</span><br />{/if}
		</div>

		{if is_array($summFormats)}
			{foreach from=$summFormats item=format}
				<span class="iconlabel" >{translate text=$format}</span>&nbsp;
			{/foreach}
		{else}
			<span class="iconlabel">{translate text=$summFormats}</span>
		{/if}
	</div>

</div>