{strip}
	<div id="groupedRecord{$summId|escape}" class="resultsList" data-order="{$resultIndex}">
		<a name="record{$summId|escape:"url"}"></a>
		<div class="row">
		{if $showCovers}
		<div class="col-xs-3 col-sm-3 col-md-3 col-lg-2 text-center">
			<img src="{$bookCoverUrlMedium}" class="listResultImage img-thumbnail{* img-responsive*}" alt="{translate text='Cover Image'}">
			{include file="GroupedWork/title-rating.tpl" ratingClass="" id=$summId ratingData=$summRating showNotInterested=false}
		</div>
		{/if}
		<div class="{if !$showCovers}col-xs-10 col-sm-10 col-md-10 col-lg-11{else}col-xs-7 col-sm-7 col-md-7 col-lg-9{/if}">
			<div class="row">
				<div class="col-xs-12">
					<span class="result-index">{$resultIndex})</span>&nbsp;
					<a href="{$summUrl}" class="result-title notranslate">
						{$summTitle|removeTrailingPunctuation|escape}
						{if $summSubTitle|removeTrailingPunctuation}: {$summSubTitle|removeTrailingPunctuation|highlight|truncate:180:"..."}{/if}
					</a><br>
					{if $summTitleStatement}
						&nbsp;-&nbsp;{$summTitleStatement|removeTrailingPunctuation|truncate:180:"..."|highlight}
					{/if}
				</div>
			</div>

			{if $summAuthor}
				<div class="row">
					<div class="result-label col-tn-3 col-xs-3">Author: </div>
					<div class="result-value col-tn-9 col-xs-9 notranslate">
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

			{if $summSeries}
				<div class="series{$summISBN} row">
					<div class="result-label col-xs-3">Series: </div>
					<div class="result-value col-xs-9">
						<a href="{$path}/GroupedWork/{$summId}/Series">{$summSeries.seriesTitle}</a>{if $summSeries.volume} volume {$summSeries.volume}{/if}
					</div>
				</div>
			{/if}

			{if $listEntryNotes}
				<div class="row">
					<div class="result-label col-md-3">Notes: </div>
					<div class="user-list-entry-note result-value col-md-9">
						{$listEntryNotes}
					</div>
				</div>
			{/if}

			{* Short Mobile Entry for Formats when there aren't hidden formats *}
			<div class="row visible-xs">

				{* TODO: Is this every needed on lists. Don't think formats get hidden *}
				{* Determine if there were hidden Formats for this entry *}
				{assign var=hasHiddenFormats value=false}
				{foreach from=$relatedManifestations item=relatedManifestation}
					{if $relatedManifestation.hideByDefault}
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

					{include file="GroupedWork/relatedManifestations.tpl" id=$summId}

				</div>
			</div>

			{* Description Section *}
			{if $summDescription}
				<div class="row visible-xs">
					<div class="result-label col-tn-3 col-xs-3">Description:</div>
					<div class="result-value col-tn-9 col-xs-9"><a id="descriptionLink{$summId|escape}" href="#" onclick="$('#descriptionValue{$summId|escape},#descriptionLink{$summId|escape}').toggleClass('hidden-xs');return false;">Click to view</a></div>
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
				{include file='GroupedWork/result-tools-horizontal.tpl' id=$summId shortId=$shortId summTitle=$summTitle ratingData=$summRating recordUrl=$summUrl}
			</div>
		</div>

		<div class="col-xs-2 col-sm-2 col-md-2 col-lg-1">
			{if $listEditAllowed}
				<div class="btn-group-vertical" role="group">
					<a href="{$path}/MyAccount/Edit?id={$summId|escape:"url"}{if !is_null($listSelected)}&amp;list_id={$listSelected|escape:"url"}{/if}" class="btn btn-default">{translate text='Edit'}</a>
					{* Use a different delete URL if we're removing from a specific list or the overall favorites: *}
					<a href="{$path}/MyAccount/MyList/{$listSelected|escape:"url"}?delete={$summId|escape:"url"}" onclick="return confirm('Are you sure you want to delete this?');" class="btn btn-default">{translate text='Delete'}</a>
				</div>

			{/if}
		</div>

		</div>
	</div>
{/strip}