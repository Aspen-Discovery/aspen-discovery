{strip}
<div id="record{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}" class="resultsList row">
	{if $showCovers}

	<div class="col-xs-12 col-sm-3{if !$viewingCombinedResults} col-md-3 col-lg-2{/if} text-center">
		{*TODO: show covers *}
		{if $disableCoverArt != 1}
			{*<div class='descriptionContent{$summShortId|escape}' style='display:none'>{$summDescription}</div>*}
			<a href="{$summUrl}">
				<img src="{$bookCoverUrlMedium}" class="listResultImage img-thumbnail img-responsive" alt="{translate text='Cover Image'}">
			</a>
		{/if}
	</div>
{/if}
	{if isset($summExplain)}
		<div class="hidden" id="scoreExplanationValue{$summId|escape}">{$summExplain}</div>
	{/if}

	<div class="{if !$showCovers}col-xs-12 col-sm-12{if !$viewingCombinedResults} col-md-12 col-lg-12{/if}{else}col-xs-12 col-sm-9{if !$viewingCombinedResults} col-md-9 col-lg-10{/if}{/if}col-xs-12 col-sm-9{if !$viewingCombinedResults} col-md-9 col-lg-10{/if}">
		<div class="row">
			<div class="col-xs-12">
				<span class="result-index">{$resultIndex})</span>&nbsp;
				<a href="{$summUrl}" class="result-title notranslate">{if !$summTitle|removeTrailingPunctuation}{translate text='Title not available'}{else}{$summTitle|removeTrailingPunctuation|highlight|truncate:180:"..."}{/if}</a>
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
				<div class="result-label col-tn-3">Author: </div>
				<div class="col-tn-9 result-value  notranslate">
					{if is_array($summAuthor)}
						{foreach from=$summAuthor item=author}
							<a href='{$path}/Author/Home?author="{$author|escape:"url"}"'>{$author|highlight}</a>
						{/foreach}
					{else}
						<a href='{$path}/Author/Home?author="{$summAuthor|escape:"url"}"'>{$summAuthor|highlight}</a>
					{/if}
				</div>
			</div>
		{/if}

		{if $summPublisher}
			<div class="row">
				<div class="result-label col-tn-3">Publisher: </div>
				<div class="col-tn-9 result-value">
					{$summPublisher}
				</div>
			</div>
		{/if}

		{if $summFormat}
			<div class="row">
				<div class="result-label col-tn-3">Format: </div>
				<div class="col-tn-9 result-value">
					{$summFormat}
				</div>
			</div>
		{/if}

		{if $summPubDate}
			<div class="row">
				<div class="result-label col-tn-3">Pub. Date: </div>
				<div class="col-tn-9 result-value">
					{$summPubDate|escape}
				</div>
			</div>
		{/if}

		{if $summSnippets}
			{foreach from=$summSnippets item=snippet}
				<div class="row">
					<div class="result-label col-tn-3">{translate text=$snippet.caption}: </div>
					<div class="col-tn-9 result-value">
						{if !empty($snippet.snippet)}<span class="quotestart">&#8220;</span>...{$snippet.snippet|highlight}...<span class="quoteend">&#8221;</span><br />{/if}
					</div>
				</div>
			{/foreach}
		{/if}

		<div class="row well-small">
			<div class="col-tn-12 result-value" id="descriptionValue{$summId|escape}">{$summDescription|highlight|html_entity_decode|truncate_html:450:"..."|strip_tags|htmlentities}</div>
		</div>

		<div class="row">
			<div class="col-tn-12">
				{include file='Archive/result-tools-horizontal.tpl'}
			</div>
		</div>

	</div>

	{if $summCOinS}<span class="Z3988" title="{$summCOinS|escape}" style="display:none">&nbsp;</span>{/if}
</div>
{/strip}