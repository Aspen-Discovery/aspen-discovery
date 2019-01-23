<div class="resultsList row">
<div class="selectTitle">&nbsp;</div>
	<div class="col-sm-3 col-md-3 col-lg-2 text-center">
		<img src="{$path}/bookcover.php?isn={$record.isbn|@formatISBN}&amp;issn={$record.issn}&amp;size=medium&amp;upc={$record.upc}" class="listResultImage img-thumbnail img-responsive" alt="{translate text='Cover Image'}"/>
	</div>
	<div class="col-sm-9 col-md-9 col-lg-10">
		<div class="row">
			<div class="col-xs-12">
				<span class="result-index">{$resultIndex})</span>&nbsp;
				<span class="result-title notranslate">
				{if !$record.title|removeTrailingPunctuation}{translate text='Title not available'}{else}{$record.title|removeTrailingPunctuation|truncate:180:"..."|highlight}{/if}
				{if $record.volume}
					, {$record.series} {$record.volume}&nbsp;
				{/if}
					</span>
			</div>
		</div>

		<div class="row">
			{if $record.author}
				<div class="result-label col-md-3">Author: </div>
				<div class="col-md-9 result-value  notranslate">
					{if is_array($record.author)}
						{foreach from=$summAuthor item=author}
							<a href='{$path}/Author/Home?author="{$author|escape:"url"}"'>{$author|highlight}</a>
						{/foreach}
					{else}
						<a href='{$path}/Author/Home?author="{$record.author|escape:"url"}"'>{$record.author|highlight}</a>
					{/if}
				</div>
			{/if}
		</div>


		{if $record.publicationDate}
			<div class="row">
				<div class="result-label col-md-3">Published: </div>
				<div class="col-md-9 result-value">{$record.publicationDate|escape}</div>
			</div>
		{/if}

		<div class="row related-manifestations-header">
			<div class="col-xs-12 result-label related-manifestations-label">
				{translate text="Choose a Format"}
			</div>
		</div>
		<div class="row related-manifestation">
			<div class="col-sm-12">
				The library does not own any copies of this title.
			</div>
		</div>

	</div>
</div>