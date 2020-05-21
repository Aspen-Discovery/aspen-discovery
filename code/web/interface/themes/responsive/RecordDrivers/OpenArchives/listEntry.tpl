{strip}
<div id="openArchivesResult{$resultIndex|escape}" class="resultsList" data-order="{$resultIndex}">
	<div class="row">
		{if $showCovers}
			<div class="col-xs-3 col-sm-3 col-md-3 col-lg-2 text-center">
				{if $disableCoverArt != 1}
					<a href="{$openArchiveUrl}" class="alignleft listResultImage" onclick="AspenDiscovery.OpenArchives.trackUsage('{$id}')">
						<img src="{$bookCoverUrl}" class="listResultImage img-thumbnail" alt="{translate text='Cover Image' inAttribute=true}">
					</a>
				{/if}
			</div>
		{/if}


		<div class="{if !$showCovers}col-xs-10 col-sm-10 col-md-10 col-lg-11{else}col-xs-7 col-sm-7 col-md-7 col-lg-9{/if}">
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

			{if !empty($type)}
				<div class="row">
					<div class="result-label col-tn-3">{translate text="Type"} </div>
					<div class="result-value col-tn-8 notranslate">
						{implode subject=$type}
					</div>
				</div>
			{/if}

			{if !empty($source)}
				<div class="row">
					<div class="result-label col-tn-3">{translate text="Source"} </div>
					<div class="result-value col-tn-8 notranslate">
						{implode subject=$source glue="<br/>"}
					</div>
				</div>
			{/if}

			{if !empty($publisher)}
				<div class="row">
					<div class="result-label col-tn-3">{translate text="Publisher"} </div>
					<div class="result-value col-tn-8 notranslate">
						{implode subject=$publisher}
					</div>
				</div>
			{/if}

			{if !empty($date)}
				<div class="row">
					<div class="result-label col-tn-3">{translate text="Date"} </div>
					<div class="result-value col-tn-8 notranslate">
						{implode subject=$date}
					</div>
				</div>
			{/if}

			{if $listEntryNotes}
				<div class="row">
					<div class="result-label col-md-3">{translate text="Notes"} </div>
					<div class="user-list-entry-note result-value col-md-9">
						{$listEntryNotes}
					</div>
				</div>
			{/if}

			{* Description Section *}
			{if $description}
				<div class="row visible-xs">
					<div class="result-label col-tn-3 col-xs-3">{translate text="Description"}</div>
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

		<div class="col-xs-2 col-sm-2 col-md-2 col-lg-1">
			{if $listEditAllowed}
				<div class="btn-group-vertical" role="group">
					<a href="/MyAccount/Edit?id={$summId|escape:"url"}{if !is_null($listSelected)}&amp;list_id={$listSelected|escape:"url"}{/if}" class="btn btn-default">{translate text='Edit'}</a>
					{* Use a different delete URL if we're removing from a specific list or the overall favorites: *}
					<a href="/MyAccount/MyList/{$listSelected|escape:"url"}?delete={$summId|escape:"url"}" onclick="return confirm('Are you sure you want to delete this?');" class="btn btn-default">{translate text='Delete'}</a>
				</div>

			{/if}
		</div>
	</div>
</div>
{/strip}