{strip}
<div id="listEntry{$listEntryId}" class="resultsList listEntry" data-order="{$resultIndex}" data-list_entry_id="{$listEntryId}">
	<div class="row">
		{if !empty($listEditAllowed) && $printInterface === false}
			<div class="selectTitle col-xs-12 col-sm-1">
				<input type="checkbox" name="selected[{$listEntryId}]" class="titleSelect" id="selected{$listEntryId}">
			</div>
		{/if}
		{if (!empty($showCovers) && $printInterface === false) || ($printInterface === true && $printEntryCovers === true)}
			<div class="col-xs-3 col-sm-3 col-md-3 col-lg-2 text-center">
				<a href="{$summUrl}" aria-hidden="true">
					<img src="{$bookCoverUrlMedium}" class="listResultImage img-thumbnail{* img-responsive*} {$coverStyle}" alt="{translate text='Cover Image' inAttribute=true isPublicFacing=true}">
				</a>
				{if (!empty($showRatings) && $printInterface === false) || ($printInterface === true && $printEntryRating === true)}
					{include file="GroupedWork/title-rating.tpl" id=$summId ratingData=$summRating showNotInterested=false}
				{/if}
			</div>
		{/if}
		<div class="{if empty($showCovers) && $printInterface === false}col-xs-9 col-sm-9 col-md-9 col-lg-10{elseif $listEditAllowed && $printInterface === false}col-xs-6 col-sm-6 col-md-6 col-lg-7{elseif $printInterface === true && $printEntryCovers === false}col-xs-12{elseif $printInterface === true && $printEntryCovers === true}col-xs-9 col-sm-9 col-md-9 col-lg-10{else}col-xs-6 col-sm-6 col-md-6 col-lg-8{/if}">
			<div class="row">
				<div class="col-xs-12">
					<span class="result-index">{$resultIndex})</span>&nbsp;
					<a href="{$summUrl}" class="result-title notranslate">
						{$summTitle|removeTrailingPunctuation|escape}
						{if !empty($summSubTitle)}{if $summSubTitle|removeTrailingPunctuation}: {$summSubTitle|removeTrailingPunctuation|highlight|truncate:180:"..."}{/if}{/if}
					</a>
				</div>
			</div>

            {if !empty($showRatings) && $printInterface === true && $printEntryRating === true && $printEntryCovers === false}
                {include file="GroupedWork/title-rating.tpl" id=$summId ratingData=$summRating showNotInterested=false}
            {/if}

			{if !empty($summAuthor)}
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

			{if (!empty($summSeries) && $printInterface === false) || (!empty($summSeries) && $printInterface === true && $printEntrySeries === true)}
				{* If the series has an ISBN, use it to make the class unique to this series *}
				<div class="series{$summISBN} row">
					<div class="result-label col-xs-3">{translate text="Series" isPublicFacing=true} </div>
					<div class="result-value col-xs-9">
						<a href="/GroupedWork/{$summId}/Series">{$summSeries.seriesTitle}</a>{if !empty($summSeries.volume)}<strong> {translate text="volume %1%" 1=$summSeries.volume|format_float_with_min_decimals isPublicFacing=true}</strong>{/if}
					</div>
				</div>
			{/if}

			{if (!empty($listEntryNotes) && $printInterface === false) || (!empty($listEntryNotes) && $printInterface === true && $printEntryNotes === true)}
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
				{if empty($hasHiddenFormats) && count($relatedManifestations) != 1}
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
				<div class="{if empty($hasHiddenFormats) && count($relatedManifestations) != 1}list-entry-hidden-format hidden-xs {/if}col-sm-12" id="relatedManifestationsValue{$summId|escape}">
					{* Hide Formats section on mobile view, unless there is a single format or a format has been selected by the user *}
					{* relatedManifestationsValue ID is used by the Formats button *}

					{include file="GroupedWork/relatedManifestations.tpl" id=$summId workId=$summId}

				</div>
			</div>

			{* Description Section *}
			{if !empty($summDescription) && $printInterface === false}
				{* Show link to view description in mobile view *}
				<div class="row visible-xs list-entry-desc-toggle">
					<div class="result-label col-tn-3 col-xs-3">{translate text="Description" isPublicFacing=true}</div>
					<div class="result-value col-tn-9 col-xs-9"><a id="descriptionLink{$summId|escape}" href="#" onclick="$('#descriptionValue{$summId|escape},#descriptionLink{$summId|escape}').toggleClass('hidden-xs');return false;">{translate text="Click to view" isPublicFacing=true}</a></div>
				</div>
			{/if}

			{* Description Section *}
			{if (!empty($summDescription) && $printInterface === false) || ($printInterface === true && $printEntryDescription === true)}
				<div class="row">
					{* Hide in mobile view *}
					<div class="list-entry-hidden-desc result-value hidden-xs col-sm-12" id="descriptionValue{$summId|escape}">
						{$summDescription|highlight|truncate_html:450:"..."}
					</div>
				</div>
			{/if}


			{if $printInterface === false}
			<div class="resultActions row">
				{include file='GroupedWork/result-tools-horizontal.tpl' ratingData=$summRating recordUrl=$summUrl showMoreInfo=true showNotInterested=false}
			</div>
            {/if}
		</div>

		{if !empty($listEditAllowed) && $printInterface === false}
			<div class="col-xs-2 col-sm-2 col-md-2 col-lg-2 text-right">
				<div class="btn-group-vertical" role="group">
					{if !empty($userSort) && ($resultIndex != '1')}<span class="btn btn-xs btn-default" onclick="return AspenDiscovery.Lists.changeWeight('{$listEntryId}', 'up');" title="{translate text="Move Up" isPublicFacing=true}">&#x25B2;</span>{/if}
					<a href="#" onclick="return AspenDiscovery.Account.getEditListForm({$listEntryId},{$listSelected})" class="btn btn-default">{translate text="Edit" isPublicFacing=true}</a>
					<a href="#" onclick="AspenDiscovery.confirm('Delete Title?', 'Are you sure you want to delete this?', 'Yes', 'No', true, 'AspenDiscovery.Lists.deleteEntryFromList({$listSelected}, {$listEntryId})', 'btn-danger');" class="btn btn-danger">{translate text='Delete' isPublicFacing=true}</a>
					{if !empty($userSort) && ($resultIndex != $listEntryCount)}<span class="btn btn-xs btn-default" onclick="return AspenDiscovery.Lists.changeWeight('{$listEntryId}', 'down');" title="{translate text="Move Down" isPublicFacing=true}">&#x25BC;</span>{/if}
				</div>
			</div>
		{/if}
	</div>
</div>
{/strip}
