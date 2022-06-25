{strip}
<div id="listEntry{$listEntryId}" class="resultsList listEntry" data-order="{$resultIndex}" data-list_entry_id="{$listEntryId}">
	<div class="row">
		{if $listEditAllowed}
			<div class="selectTitle col-xs-12 col-sm-1">
				<input type="checkbox" name="selected[{$listEntryId}]" class="titleSelect" id="selected{$listEntryId}">
			</div>
		{/if}
		{if $showCovers}
			<div class="col-xs-3 col-sm-3 col-md-3 col-lg-2 text-center">
				{if $disableCoverArt != 1 && !empty($bookCoverUrlMedium)}
					<a href="{$summUrl}" onclick="AspenDiscovery.EBSCO.trackEdsUsage('{$summId}')" target="_blank" aria-hidden="true">
						<img src="{$bookCoverUrlMedium}" class="listResultImage img-thumbnail" alt="{translate text='Cover Image' inAttribute=true isPublicFacing=true}">
					</a>
				{/if}
			</div>
		{/if}

		<div class="{if !$showCovers}col-xs-9 col-sm-9 col-md-9 col-lg-10{elseif $listEditAllowed}col-xs-6 col-sm-6 col-md-6 col-lg-7{else}col-xs-6 col-sm-6 col-md-6 col-lg-8{/if}">
			<div class="row">
				<div class="col-xs-12">
					<span class="result-index">{$resultIndex})</span>&nbsp;
					<a href="{$summUrl}" class="result-title notranslate" onclick="AspenDiscovery.EBSCO.trackEdsUsage('{$summId}')" target="_blank">
						{if !$summTitle|removeTrailingPunctuation} {translate text='Title not available' isPublicFacing=true}{else}{$summTitle|removeTrailingPunctuation|truncate:180:"..."|highlight}{/if}
					</a>
				</div>
			</div>

			{if $summAuthor}
				<div class="row">
					<div class="result-label col-sm-3"> {translate text='Author' isPublicFacing=true}</div>
					<div class="col-sm-9 result-value">{$summAuthor|escape}</div>
				</div>
			{/if}

			{if strlen($summSourceDatabase)}
				<div class="row hidden-phone">
					<div class="result-label col-sm-3">{translate text='Found in' isPublicFacing=true}</div>
					<div class="col-sm-9 result-value">{$summSourceDatabase|escape}</div>
				</div>
			{/if}

			{if $summPublicationDates || $summPublishers || $summPublicationPlaces}
				<div class="row">

					<div class="result-label col-sm-3">{translate text='Published' isPublicFacing=true}</div>
					<div class="col-sm-9 result-value">
						{$summPublicationPlaces.0|escape}{$summPublishers.0|escape}{$summPublicationDates.0|escape}
					</div>
				</div>
			{/if}

			{if strlen($summFormats)}
				<div class="row">
					<div class="result-label col-sm-3">{translate text='Format' isPublicFacing=true}</div>
					<div class="col-sm-9 result-value">
						<span>{translate text=$summFormats isPublicFacing=true}</span>
					</div>
				</div>
			{/if}

			{if $summPhysical}
				<div class="row hidden-phone">
					<div class="result-label col-sm-3">{translate text='Physical Desc' isPublicFacing=true}</div>
					<div class="col-sm-9 result-value">{$summPhysical.0|escape}</div>
				</div>
			{/if}

			<div class="row hidden-phone">
				<div class="result-label col-sm-3">{translate text='Full Text' isPublicFacing=true}</div>
				<div class="col-sm-9 result-value">{if $summHasFullText}{translate text="Yes" isPublicFacing=true}{else}{translate text="No" isPublicFacing=true}{/if}</div>
			</div>

			{if $listEntryNotes}
				<div class="row">
					<div class="result-label col-sm-3">{translate text="Notes" isPublicFacing=true} </div>
					<div class="user-list-entry-note result-value col-sm-9">
						{$listEntryNotes}
					</div>
				</div>
			{/if}

			{if $summDescription}
				{* Standard Description *}
				<div class="row visible-xs">
					<div class="result-label col-tn-3">{translate text='Description' isPublicFacing=true}</div>
					<div class="result-value col-tn-8"><a id="descriptionLink{$summId|escape}" href="#" onclick="$('#descriptionValue{$summId|escape},#descriptionLink{$summId|escape}').toggleClass('hidden-xs');return false;">Click to view</a></div>
				</div>

				{* Mobile Description *}
				<div class="row">
					{* Hide in mobile view *}
					<div class="hidden-xs result-value col-sm-12" id="descriptionValue{$summId|escape}">
						{$summDescription|highlight|truncate_html:450:"..."}
					</div>
				</div>
			{/if}

			<div class="row">
				<div class="col-xs-12">
					{include file='EBSCO/result-tools-horizontal.tpl' recordUrl=$summUrl showMoreInfo=true}
				</div>
			</div>
		</div> {* End of main section *}

		{if $listEditAllowed}
			<div class="col-xs-2 col-sm-2 col-md-2 col-lg-2 text-right">
				<div class="btn-group-vertical" role="group">
					{if $userSort && ($resultIndex != '1')}<span class="btn btn-xs btn-default" onclick="return AspenDiscovery.Lists.changeWeight('{$listEntryId}', 'up');" title="{translate text="Move Up" isPublicFacing=true}">&#x25B2;</span>{/if}
					<a href="#" onclick="return AspenDiscovery.Account.getEditListForm({$listEntryId},{$listSelected})" class="btn btn-default">{translate text="Edit" isPublicFacing=true}</a>
					{* Use a different delete URL if we're removing from a specific list or the overall favorites: *}
					<a href="/MyAccount/MyList/{$listSelected|escape:"url"}?delete={$listEntryId|escape:"url"}" onclick="return confirm('{translate text="Are you sure you want to delete this?" isPublicFacing=true inAttribute=true}');" class="btn btn-danger">{translate text='Delete' isPublicFacing=true}</a>
					{if $userSort && ($resultIndex != $listEntryCount)}<span class="btn btn-xs btn-default" onclick="return AspenDiscovery.Lists.changeWeight('{$listEntryId}', 'down');" title="{translate text="Move Down" isPublicFacing=true}">&#x25BC;</span>{/if}
				</div>
			</div>
		{/if}
	</div>
</div>
{/strip}