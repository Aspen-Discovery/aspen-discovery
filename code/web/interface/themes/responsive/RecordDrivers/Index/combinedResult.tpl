{strip}
	<div id="groupedRecord{$summId|escape}" class="resultsList">
		<a id="record{$summId|escape}"></a>
		{if isset($summExplain)}
			<div class="hidden" id="scoreExplanationValue{$summId|escape}">{$summExplain}</div>
		{/if}

		<div class="row">
			{if $showCovers}
				<div class="coversColumn col-xs-3 text-center">
					{if $disableCoverArt != 1}
						<a href="{$summUrl}" aria-hidden="true">
							<img src="{$bookCoverUrl}" class="listResultImage img-thumbnail" alt="{translate text='Cover Image' inAttribute=true isPublicFacing=true}">
						</a>
					{/if}
				</div>
			{/if}
			<div class="{if $showCovers}col-xs-9{else}col-xs-12{/if}">{* May turn out to be more than one situation to consider here *}
				{* Title Row *}
				<div class="row">
					<div class="col-xs-12">
						<span class="result-index">{$resultIndex})</span>&nbsp;
						<a href="{$summUrl}" class="result-title notranslate">
							{if !$summTitle|removeTrailingPunctuation}{translate text='Title not available'}{else}{$summTitle|removeTrailingPunctuation|highlight|truncate:180:"..."}{/if}
							{if $summSubTitle|removeTrailingPunctuation}: {$summSubTitle|removeTrailingPunctuation|highlight|truncate:180:"..."}{/if}
						</a>
						{if isset($summScore)}
							&nbsp;(<a href="#" onclick="return AspenDiscovery.showElementInPopup('Score Explanation', '#scoreExplanationValue{$summId|escape}');">{$summScore}</a>)
						{/if}
					</div>
				</div>

				{if $summAuthor}
					<div class="row">
						<div class="result-label col-tn-3">Author: </div>
						<div class="result-value col-tn-8 notranslate">
							{if is_array($summAuthor)}
								{foreach from=$summAuthor item=author}
									<a href='/Author/Home?author="{$author|escape:"url"}"'>{$author|highlight}</a>
								{/foreach}
							{else}
								<a href='/Author/Home?author="{$summAuthor|escape:"url"}"'>{$summAuthor|highlight}</a>
							{/if}
						</div>
					</div>
				{/if}

				{* Description Section *}
				{if $summDescription}
					<div class="row">
						{* Hide in mobile view *}
						<div class="result-value col-sm-12" id="descriptionValue{$summId|escape}">
							{$summDescription|highlight|truncate_html:450:"..."}
						</div>
					</div>
				{/if}

			</div>

		</div>
	</div>
{/strip}
