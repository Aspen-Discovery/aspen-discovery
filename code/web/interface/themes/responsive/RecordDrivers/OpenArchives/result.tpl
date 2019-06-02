{strip}
<div id="openArchivesResult{$resultIndex|escape}" class="resultsList row">
	{if $showCovers}
		<div class="coversColumn col-xs-3 col-sm-3 col-md-3 col-lg-2 text-center">
			{if $disableCoverArt != 1}
				<a href="{$openArchiveUrl}" class="alignleft listResultImage" onclick="AspenDiscovery.OpenArchives.trackUsage('{$id}')">
					<img src="{$bookCoverUrl}" class="listResultImage img-thumbnail" alt="{translate text='Cover Image'}">
				</a>
			{/if}
		</div>
	{/if}


	<div class="{if !$showCovers}col-xs-12{else}col-xs-9 col-sm-9 col-md-9 col-lg-10{/if}">{* May turn out to be more than one situation to consider here *}
		{* Title Row *}

		<div class="row">
			<div class="col-xs-12">
				<span class="result-index">{$resultIndex})</span>&nbsp;
				<a href="{$openArchiveUrl}" class="result-title notranslate" onclick="AspenDiscovery.OpenArchives.trackUsage('{$id}')">
					{if !$title|removeTrailingPunctuation}{translate text='Title not available'}{else}{$title|removeTrailingPunctuation|highlight|truncate:180:"..."}{/if}
				</a>
				{if isset($summScore)}
					&nbsp;(<a href="#" onclick="return AspenDiscovery.showElementInPopup('Score Explanation', '#scoreExplanationValue{$summId|escape}');">{$summScore}</a>)
				{/if}
			</div>
		</div>

		{if !empty($summSnippets)}
			{foreach from=$summSnippets item=snippet}
				<div class="row">
					<div class="result-label col-tn-3 col-xs-3">{translate text=$snippet.caption}: </div>
					<div class="result-value col-tn-9 col-xs-9">
						{if !empty($snippet.snippet)}<span class="quotestart">&#8220;</span>...{$snippet.snippet|highlight}...<span class="quoteend">&#8221;</span><br />{/if}
					</div>
				</div>
			{/foreach}
		{/if}

		{if !empty($type)}
			<div class="row">
				<div class="result-label col-tn-3">Type: </div>
				<div class="result-value col-tn-8 notranslate">
					{implode subject=$type}
				</div>
			</div>
		{/if}

		{if !empty($source)}
			<div class="row">
				<div class="result-label col-tn-3">Source: </div>
				<div class="result-value col-tn-8 notranslate">
					{implode subject=$source glue="<br/>"}
				</div>
			</div>
		{/if}

		{if !empty($publisher)}
			<div class="row">
				<div class="result-label col-tn-3">Publisher: </div>
				<div class="result-value col-tn-8 notranslate">
					{implode subject=$publisher}
				</div>
			</div>
		{/if}

		{if !empty($date)}
			<div class="row">
				<div class="result-label col-tn-3">Date: </div>
				<div class="result-value col-tn-8 notranslate">
					{implode subject=$date}
				</div>
			</div>
		{/if}

		{* Description Section *}
		{if $description}
			<div class="row visible-xs">
				<div class="result-label col-tn-3 col-xs-3">Description:</div>
				<div class="result-value col-tn-9 col-xs-9"><a id="descriptionLink{$resultIndex|escape}" href="#" onclick="$('#descriptionValue{$resultIndex|escape},#descriptionLink{$resultIndex|escape}').toggleClass('hidden-xs');return false;">Click to view</a></div>
			</div>

			<div class="row">
				{* Hide in mobile view *}
				<div class="result-value hidden-xs col-sm-12" id="descriptionValue{$resultIndex|escape}">
					{$description|highlight|truncate_html:450:"..."}
				</div>
			</div>
		{/if}
	</div>
</div>
{/strip}