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
				{if $disableCoverArt != 1}
					<a href="/MyAccount/MyList/{$summShortId}" class="alignleft listResultImage">
						<img src="{$bookCoverUrl}" class="listResultImage img-thumbnail" alt="{translate text='Cover Image' inAttribute=true isPublicFacing=true}">
					</a>
				{/if}
			</div>
		{/if}


		<div class="{if !$showCovers}col-xs-9 col-sm-9 col-md-9 col-lg-10{elseif $listEditAllowed}col-xs-6 col-sm-6 col-md-6 col-lg-7{else}col-xs-6 col-sm-6 col-md-6 col-lg-8{/if}">
			{* Title Row *}

			<div class="row">
				<div class="col-xs-12">
					<span class="result-index">{$resultIndex})</span>&nbsp;
					<a href="/MyAccount/MyList/{$summShortId}" class="result-title notranslate">
						{if !$summTitle|removeTrailingPunctuation} {translate text='Title not available' isPublicFacing=true}{else}{$summTitle|removeTrailingPunctuation|highlight|truncate:180:"..."}{/if}
					</a>
					{if isset($summScore)}
						&nbsp;(<a href="#" onclick="return AspenDiscovery.showElementInPopup('Score Explanation', '#scoreExplanationValue{$summId|escape}');">{$summScore}</a>)
					{/if}
				</div>
			</div>

			{if $summAuthor}
				<div class="row">
					<div class="result-label col-tn-3">{translate text="Created By" isPublicFacing=true} </div>
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
					<div class="result-label col-tn-3">{translate text="Number of Titles" isPublicFacing=true} </div>
					<div class="result-value col-tn-9 notranslate">
						{translate text="%1% titles are in this list." 1=$summNumTitles isPublicFacing=true}
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

			{* Description Section *}
			{if $summDescription}
				<div class="row visible-xs">
					<div class="result-label col-tn-3 col-xs-3">{translate text="Description" isPublicFacing=true}</div>
					<div class="result-value col-tn-9 col-xs-9"><a id="descriptionLink{$summId|escape}" href="#" onclick="$('#descriptionValue{$summId|escape},#descriptionLink{$summId|escape}').toggleClass('hidden-xs');return false;">{translate text="Click to view" isPublicFacing=true}</a></div>
				</div>

				<div class="row">
					{* Hide in mobile view *}
					<div class="result-value hidden-xs col-sm-12" id="descriptionValue{$summId|escape}">
						{$summDescription|highlight|truncate_html:450:"..."}
					</div>
				</div>
			{/if}


			<div class="resultActions row">
				{include file='Lists/result-tools.tpl' id=$summId shortId=$shortId module=$summModule summTitle=$summTitle ratingData=$summRating recordUrl=$summUrl}
			</div>
		</div>

		{if $listEditAllowed}
			<div class="col-xs-2 col-sm-2 col-md-2 col-lg-2 text-right">
				<div class="btn-group-vertical" role="group">
					{if $userSort && $resultIndex != '1'}<span class="btn btn-xs btn-default" onclick="return AspenDiscovery.Lists.changeWeight('{$listEntryId}', 'up');" title="{translate text="Move Up" isPublicFacing=true}">&#x25B2;</span>{/if}
					<a href="#" onclick="return AspenDiscovery.Account.getEditListForm({$listEntryId},{$listSelected})" class="btn btn-default">{translate text='Edit' isPublicFacing=true}</a>
					{* Use a different delete URL if we're removing from a specific list or the overall favorites: *}
					<a href="/MyAccount/MyList/{$listSelected|escape:"url"}?delete={$listEntryId|escape:"url"}" onclick="return confirm('Are you sure you want to delete this?');" class="btn btn-danger">{translate text='Delete' isPublicFacing=true}</a>
					{if $userSort && ($resultIndex != $listEntryCount)}<span class="btn btn-xs btn-default" onclick="return AspenDiscovery.Lists.changeWeight('{$listEntryId}', 'down');" title="{translate text="Move Down" isPublicFacing=true}">&#x25BC;</span>{/if}
				</div>
			</div>
		{/if}
	</div>
</div>
{/strip}