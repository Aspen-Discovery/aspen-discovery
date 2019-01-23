{strip}
<div id="record{$summId|escape}" class="resultsList row">
	{if $showCovers}
		<div class="coversColumn col-xs-3 col-sm-3 col-md-3 col-lg-2 text-center">
			{if $disableCoverArt != 1}
				<a href="{$path}/MyAccount/MyList/{$summShortId}" class="alignleft listResultImage">
					<img src="{img filename="lists.png"}" class="listResultImage img-thumbnail" alt="{translate text='No Cover Image'}">
				</a>

				{* From Grouped Work results.tpl *}
				{*<a href="{$summUrl}">*}
					{*<img src="{$bookCoverUrlMedium}" class="listResultImage img-thumbnail*}{* img-responsive // shouldn't be needed *}{*" alt="{translate text='No Cover Image'}">*}
				{*</a>*}
			{/if}
		</div>
	{/if}


	<div class="{if !$showCovers}col-xs-12{else}col-xs-9 col-sm-9 col-md-9 col-lg-10{/if}">{* May turn out to be more than one situation to consider here *}
		{* Title Row *}

		<div class="row">
			<div class="col-xs-12">
				<span class="result-index">{$resultIndex})</span>&nbsp;
				<a href="{$path}/MyAccount/MyList/{$summShortId}" class="result-title notranslate">
					{if !$summTitle|removeTrailingPunctuation}{translate text='Title not available'}{else}{$summTitle|removeTrailingPunctuation|highlight|truncate:180:"..."}{/if}
				</a>
				{if $summTitleStatement}
					&nbsp;-&nbsp;{$summTitleStatement|removeTrailingPunctuation|highlight|truncate:180:"..."}
				{/if}
				{if isset($summScore)}
					&nbsp;(<a href="#" onclick="return VuFind.showElementInPopup('Score Explanation', '#scoreExplanationValue{$summId|escape}');">{$summScore}</a>)
				{/if}
			</div>
		</div>

		{if $summAuthor}
			<div class="row">
				<div class="result-label col-tn-3">Created By: </div>
				<div class="result-value col-tn-9 notranslate">
					{if is_array($summAuthor)}
						{foreach from=$summAuthor item=author}
							{$author|highlight}
						{/foreach}
					{else}
						{$summAuthor|highlight}
					{/if}
				</div>
			</div>
		{/if}

		{if $summNumTitles}
			<div class="row">
				<div class="result-label col-tn-3">Number of Titles: </div>
				<div class="result-value col-tn-9 notranslate">
					{$summNumTitles} titles are in this list.
				</div>
			</div>
		{/if}

		{if $summSnippets}
			{foreach from=$summSnippets item=snippet}
				<div class="row">
					<div class="result-label col-tn-3 col-xs-3">{translate text=$snippet.caption}: </div>
					<div class="result-value col-tn-9 col-xs-9">
						{if !empty($snippet.snippet)}<span class="quotestart">&#8220;</span>...{$snippet.snippet|highlight}...<span class="quoteend">&#8221;</span><br />{/if}
					</div>
				</div>
			{/foreach}
		{/if}

		{* Description Section *}
		{if $summDescription}
			<div class="row visible-xs">
				<div class="result-label col-tn-3 col-xs-3">Description:</div>
				<div class="result-value col-tn-9 col-xs-9"><a id="descriptionLink{$summId|escape}" href="#" onclick="$('#descriptionValue{$summId|escape},#descriptionLink{$summId|escape}').toggleClass('hidden-xs');return false;">Click to view</a></div>
			</div>

			<div class="row">
				{* Hide in mobile view *}
				<div class="result-value hidden-xs col-sm-12" id="descriptionValue{$summId|escape}">
					{$summDescription|highlight|truncate_html:450:"..."}
				</div>
			</div>
		{/if}


		<div class="resultActions row">
			{include file='List/result-tools.tpl' id=$summId shortId=$shortId module=$summModule summTitle=$summTitle ratingData=$summRating recordUrl=$summUrl}
		</div>
	</div>
</div>
{/strip}