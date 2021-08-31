{strip}
<div id="record{if $summShortId}{$summShortId}{else}{$summId|escape}{/if}" class="resultsList row">
	{if $showCovers}
		<div class="coversColumn col-xs-3 text-center">
			{if $disableCoverArt != 1}
				<a href="{$summUrl}" aria-hidden="true">
					<img src="{$bookCoverUrl}" class="listResultImage img-thumbnail" alt="{translate text='Cover Image' inAttribute=true isPublicFacing=true}">
				</a>
			{/if}
		</div>
	{/if}
	<div class="{if $showCovers}col-xs-9{else}col-xs-12{/if}">
		<div class="row">
			<div class="col-xs-12">
				<span class="result-index">{$resultIndex})</span>&nbsp;
				<a href="{$summUrl}" class="result-title notranslate">
					{if !$summTitle|removeTrailingPunctuation} {translate text='Title not available' isPublicFacing=true}{else}{$summTitle|removeTrailingPunctuation|truncate:180:"..."|highlight}{/if}
				</a>
			</div>
		</div>

		{if $summAuthor}
			<div class="row">
				<div class="result-label col-tn-3"> {translate text='Author' isPublicFacing=true}</div>
				<div class="col-tn-9 result-value">{$summAuthor|escape}</div>
			</div>
		{/if}

		{if strlen($summFormats)}
			<div class="row">
				<div class="result-label col-tn-3">Format: </div>
				<div class="col-tn-9 result-value">
					<span>{translate text=$summFormats}</span>
				</div>
			</div>
		{/if}

		<div class="row">
			<div class="result-label col-tn-3">{translate text='Full Text'}:</div>
			<div class="col-tn-9 result-value">{if $summHasFullText}Full text available{else}Full text not available{/if}</div>
		</div>

		{if $summDescription}
			{* Standard Description *}
			<div class="row visible-xs">
				<div class="result-label col-tn-3">{translate text='Description'}</div>
				<div class="result-value col-tn-8"><a id="descriptionLink{$summId|escape}" href="#" onclick="$('#descriptionValue{$summId|escape},#descriptionLink{$summId|escape}').toggleClass('hidden-xs');return false;">Click to view</a></div>
			</div>

			{* Mobile Description *}
			<div class="row hidden-xs">
				{* Hide in mobile view *}
				<div class="result-value col-sm-12" id="descriptionValue{$summId|escape}">
					{$summDescription|highlight|truncate_html:450:"..."}
				</div>
			</div>
		{/if}
	</div>
</div>
{/strip}