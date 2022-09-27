<div class="resultsList">
	<div class="row">
		{if $showCovers}
			<div class="coversColumn col-xs-3 col-sm-3{if !empty($viewingCombinedResults)} col-md-3 col-lg-2{/if} text-center" aria-hidden="true" role="presentation">
				<img src="/bookcover.php?isn={$record.isbn|@formatISBN}&amp;issn={$record.issn}&amp;size=medium&amp;upc={$record.upc}" class="listResultImage img-thumbnail img-responsive {$coverStyle}" alt="{translate text='Cover Image' inAttribute=true isPublicFacing=true}" tabindex="-1"/>
			</div>
		{/if}

		<div class="{if !$showCovers}col-xs-12{else}col-xs-9 col-sm-9{if !empty($viewingCombinedResults)} col-md-9 col-lg-10{/if}{/if}">{* May turn out to be more than one situation to consider here *}
			<div class="row">
				<div class="col-xs-12">
					<span class="result-index">{$resultIndex})</span>&nbsp;
					<span class="result-title notranslate">
					{if !$record.title|removeTrailingPunctuation} {translate text='Title not available' isPublicFacing=true}{else}{$record.title|removeTrailingPunctuation|truncate:180:"..."|highlight}{/if}
					{if $record.volume}
						, {$record.series}{if $record.volume}<strong> {translate text="volume %1%" 1=$record.volume isPublicFacing=true}</strong>{/if}&nbsp;
					{/if}
					</span>
				</div>
			</div>

			{if $record.author}
				<div class="row">
					<div class="result-label col-md-3">{translate text="Author" isPublicFacing=true} </div>
					<div class="col-md-9 result-value  notranslate">
						{if is_array($record.author)}
							{foreach from=$summAuthor item=author}
								<a href='/Author/Home?author="{$author|escape:"url"}"'>{$author|highlight}</a>
							{/foreach}
						{else}
							<a href='/Author/Home?author="{$record.author|escape:"url"}"'>{$record.author|highlight}</a>
						{/if}
					</div>
				</div>
			{/if}

			{if $record.publicationDate}
				<div class="row">
					<div class="result-label col-md-3">Published: </div>
					<div class="col-md-9 result-value">{$record.publicationDate|escape}</div>
				</div>
			{/if}

			<div class="row related-manifestations-header">
				<div class="col-xs-12 result-label related-manifestations-label">
					{translate text="Choose a Format" isPublicFacing=true}
				</div>
			</div>
			<div class="row related-manifestation">
				<div class="col-sm-12">
					{translate text="The library does not own any copies of this title." isPublicFacing=true}
				</div>
			</div>
		</div>
	</div>
</div>