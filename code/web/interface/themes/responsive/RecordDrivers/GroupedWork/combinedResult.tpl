{strip}
	<div id="groupedRecord{$summId|escape}" class="resultsList">
		<a id="record{$summId|escape}"></a>
		{if isset($summExplain)}
			<div class="hidden" id="scoreExplanationValue{$summId|escape}">{$summExplain}</div>
		{/if}

		<div class="row">
			<div class="col-xs-12">{* May turn out to be more than one situation to consider here *}
				{* Title Row *}
				<div class="row">
					<div class="col-xs-12">
						<span class="result-index">{$resultIndex})</span>&nbsp;
						<a href="{$summUrl}" class="result-title notranslate">
							{if !$summTitle|removeTrailingPunctuation}{translate text='Title not available'}{else}{$summTitle|removeTrailingPunctuation|highlight|truncate:180:"..."}{/if}
							{if $summSubTitle|removeTrailingPunctuation}: {$summSubTitle|removeTrailingPunctuation|highlight|truncate:180:"..."}{/if}
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
						<div class="result-label col-tn-3">Author: </div>
						<div class="result-value col-tn-8 notranslate">
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

				{* Short Mobile Entry for Formats when there aren't hidden formats *}
				<div class="row">

					{* Determine if there were hidden Formats for this entry *}
					{foreach from=$relatedManifestations item=relatedManifestation}
					{if $relatedManifestation.hideByDefault}
						{assign var=hasHiddenFormats value=true}
					{/if}
					{/foreach}

					<div class="result-label col-tn-3">
						Format{if count($relatedManifestations) > 1}s{/if}:
					</div>
					<div class="result-value col-tn-8">
						{implode subject=$relatedManifestations|@array_keys glue=", "}
					</div>

				</div>

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


		{if $summCOinS}<span class="Z3988" title="{$summCOinS|escape}" style="display:none">&nbsp;</span>{/if}
	</div>
{/strip}
