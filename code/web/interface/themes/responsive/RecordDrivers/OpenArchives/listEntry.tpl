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
					<a href="{$openArchiveUrl}" class="alignleft listResultImage" onclick="AspenDiscovery.OpenArchives.trackUsage('{$id}')" target="_blank">
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
					<a href="{$openArchiveUrl}" class="result-title notranslate" onclick="AspenDiscovery.OpenArchives.trackUsage('{$id}')" target="_blank">
						{if !$title|removeTrailingPunctuation} {translate text='Title not available' isPublicFacing=true}{else}{$title|removeTrailingPunctuation|highlight|truncate:180:"..."}{/if}
					</a>
					{if isset($summScore)}
						&nbsp;(<a href="#" onclick="return AspenDiscovery.showElementInPopup('Score Explanation', '#scoreExplanationValue{$summId|escape}');">{$summScore}</a>)
					{/if}
				</div>
			</div>

			{if !empty($type)}
				<div class="row">
					<div class="result-label col-tn-3">{translate text="Type" isPublicFacing=true} </div>
					<div class="result-value col-tn-8 notranslate">
						{implode subject=$type}
					</div>
				</div>
			{/if}

			{if !empty($source)}
				<div class="row">
					<div class="result-label col-tn-3">{translate text="Source" isPublicFacing=true} </div>
					<div class="result-value col-tn-8 notranslate">
						{implode subject=$source glue="<br/>"}
					</div>
				</div>
			{/if}

			{if !empty($publisher)}
				<div class="row">
					<div class="result-label col-tn-3">{translate text="Publisher" isPublicFacing=true} </div>
					<div class="result-value col-tn-8 notranslate">
						{implode subject=$publisher}
					</div>
				</div>
			{/if}

			{if !empty($date)}
				<div class="row">
					<div class="result-label col-tn-3">{translate text="Date" isPublicFacing=true} </div>
					<div class="result-value col-tn-8 notranslate">
						{implode subject=$date}
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
			{if $description}
				<div class="row visible-xs">
					<div class="result-label col-tn-3 col-xs-3">{translate text="Description" isPublicFacing=true}</div>
					<div class="result-value col-tn-9 col-xs-9"><a id="descriptionLink{$resultIndex|escape}" href="#" onclick="$('#descriptionValue{$resultIndex|escape},#descriptionLink{$resultIndex|escape}').toggleClass('hidden-xs');return false;">Click to view</a></div>
				</div>

				<div class="row">
					{* Hide in mobile view *}
					<div class="result-value hidden-xs col-sm-12" id="descriptionValue{$resultIndex|escape}">
						{$description|highlight|truncate_html:450:"..."}
					</div>
				</div>
			{/if}

			<div class="row">
				<div class="col-xs-12">
					{include file='OpenArchives/result-tools-horizontal.tpl' recordUrl=$openArchiveUrl showMoreInfo=true}
				</div>
			</div>
		</div>

		<div class="col-xs-2 col-sm-2 col-md-2 col-lg-2 text-right">
			{if $listEditAllowed}
				<div class="btn-group-vertical" role="group">
					{if $userSort && $resultIndex != '1'}<span class="btn btn-xs btn-default" onclick="return AspenDiscovery.Lists.changeWeight('{$listEntryId}', 'up');" title="{translate text="Move Up" isPublicFacing=true}">&#x25B2;</span>{/if}
					<a href="/MyAccount/Edit?listEntryId={$listEntryId|escape:"url"}{if !is_null($listSelected)}&amp;listId={$listSelected|escape:"url"}{/if}" class="btn btn-default">{translate text='Edit' isPublicFacing=true}</a>
					{* Use a different delete URL if we're removing from a specific list or the overall favorites: *}
					<a href="/MyAccount/MyList/{$listSelected|escape:"url"}?delete={$listEntryId|escape:"url"}" onclick="return confirm('Are you sure you want to delete this?');" class="btn btn-danger">{translate text='Delete' isPublicFacing=true}</a>
					{if $userSort && ($resultIndex != $listEntryCount)}<span class="btn btn-xs btn-default" onclick="return AspenDiscovery.Lists.changeWeight('{$listEntryId}', 'down');" title="{translate text="Move Down" isPublicFacing=true}">&#x25BC;</span>{/if}
				</div>

			{/if}
		</div>
	</div>
</div>
{/strip}