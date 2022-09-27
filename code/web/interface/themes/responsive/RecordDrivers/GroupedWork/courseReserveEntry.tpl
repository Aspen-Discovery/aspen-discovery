{strip}
<div id="listEntry{$listEntryId}" class="resultsList listEntry" data-order="{$resultIndex}" data-list_entry_id="{$listEntryId}">
	<div class="row">
		{if $showCovers}
			<div class="col-xs-3 col-sm-3 col-md-3 col-lg-2 text-center">
				<a href="{$summUrl}" aria-hidden="true">
					<img src="{$bookCoverUrlMedium}" class="listResultImage img-thumbnail{* img-responsive*} {$coverStyle}" alt="{translate text='Cover Image' inAttribute=true isPublicFacing=true}">
				</a>
				{if $showRatings}
					{include file="GroupedWork/title-rating.tpl" id=$summId ratingData=$summRating showNotInterested=false}
				{/if}
			</div>
		{/if}
		<div class="{if $showCovers}col-xs-9 col-sm-9 col-md-9 col-lg-10{else}col-xs-12{/if}">
			<div class="row">
				<div class="col-xs-12">
					<span class="result-index">{$resultIndex})</span>&nbsp;
					<a href="{$summUrl}" class="result-title notranslate">
						{$summTitle|removeTrailingPunctuation|escape}
						{if $summSubTitle|removeTrailingPunctuation}: {$summSubTitle|removeTrailingPunctuation|highlight|truncate:180:"..."}{/if}
					</a>
				</div>
			</div>

			{if $summAuthor}
				<div class="row">
					<div class="result-label col-tn-3 col-xs-3">{translate text="Author" isPublicFacing=true} </div>
					<div class="result-value col-tn-9 col-xs-9 notranslate">
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

			{if $summSeries}
				<div class="series{$summISBN} row">
					<div class="result-label col-xs-3">{translate text="Series" isPublicFacing=true} </div>
					<div class="result-value col-xs-9">
						<a href="/GroupedWork/{$summId}/Series">{$summSeries.seriesTitle}</a>{if $summSeries.volume}<strong> {translate text="volume %1%" 1=$summSeries.volume isPublicFacing=true}</strong>{/if}
					</div>
				</div>
			{/if}

			{if $listEntryNotes}
				<div class="row">
					<div class="result-label col-md-3">{translate text="Notes" isPublicFacing=true} </div>
					<div class="user-list-entry-note result-value col-md-9">
						{$listEntryNotes}
					</div>
				</div>
			{/if}

			{* Short Mobile Entry for Formats when there aren't hidden formats *}
			<div class="row visible-xs">

				{* Determine if there were hidden Formats for this entry *}
				{assign var=hasHiddenFormats value=false}
				{foreach from=$relatedManifestations item=relatedManifestation}
					{if $relatedManifestation->hasHiddenFormats()}
						{assign var=hasHiddenFormats value=true}
					{/if}
				{/foreach}

				{* If there weren't hidden formats, show this short Entry (mobile view only). The exception is single format manifestations, they
					 won't have any hidden formats and will be displayed *}
				{if !$hasHiddenFormats && count($relatedManifestations) != 1}
					<div class="hidethisdiv{$summId|escape} result-label col-tn-3 col-xs-3">
						Formats:
					</div>
					<div class="hidethisdiv{$summId|escape} result-value col-tn-9 col-xs-9">
						<a href="#" onclick="$('#relatedManifestationsValue{$summId|escape},.hidethisdiv{$summId|escape}').toggleClass('hidden-xs');return false;">
							{implode subject=$relatedManifestations|@array_keys glue=", "}
						</a>
					</div>
				{/if}

			</div>

			{* Formats Section *}
			<div class="row">
				<div class="{if !$hasHiddenFormats && count($relatedManifestations) != 1}hidden-xs {/if}col-sm-12" id="relatedManifestationsValue{$summId|escape}">
					{* Hide Formats section on mobile view, unless there is a single format or a format has been selected by the user *}
					{* relatedManifestationsValue ID is used by the Formats button *}

					{include file="GroupedWork/relatedManifestations.tpl" id=$summId workId=$summId}

				</div>
			</div>

			{* Description Section *}
			{if $summDescription}
				<div class="row visible-xs">
					<div class="result-label col-tn-3 col-xs-3">{translate text="Description" isPublicFacing=true}</div>
					<div class="result-value col-tn-9 col-xs-9"><a id="descriptionLink{$summId|escape}" href="#" onclick="$('#descriptionValue{$summId|escape},#descriptionLink{$summId|escape}').toggleClass('hidden-xs');return false;">{translate text="Click to view" isPublicFacing=true}</a></div>
				</div>
			{/if}

			{* Description Section *}
			{if $summDescription}
				<div class="row">
					{* Hide in mobile view *}
					<div class="result-value hidden-xs col-sm-12" id="descriptionValue{$summId|escape}">
						{$summDescription|highlight|truncate_html:450:"..."}
					</div>
				</div>
			{/if}


			<div class="resultActions row">
				{include file='GroupedWork/result-tools-horizontal.tpl' ratingData=$summRating recordUrl=$summUrl showMoreInfo=true showNotInterested=false}
			</div>
		</div>
	</div>
</div>
{/strip}