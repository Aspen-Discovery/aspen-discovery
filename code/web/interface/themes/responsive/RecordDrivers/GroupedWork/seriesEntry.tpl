{strip}
	<div id="listEntry{$summShortId}" class="resultsList listEntry" data-order="{$resultIndex}" data-list_entry_id="{$summShortId}">
		<div class="row">
			{if !empty($showCovers)}
				<div class="col-xs-3 col-sm-3 col-md-3 col-lg-2 text-center">
					<a href="{$summUrl}" aria-hidden="true">
						<img src="{$bookCoverUrlMedium}" class="listResultImage img-thumbnail{if $useOriginalCoverUrls} use-original-covers{/if}{* img-responsive*} {$coverStyle}" alt="{translate text='Cover Image' inAttribute=true isPublicFacing=true}">
					</a>
				</div>
			{/if}
			<div class="{if !empty($showCovers)}col-xs-9 col-sm-9 col-md-9 col-lg-10{else}col-xs-12{/if}">
				<div class="row">
					<div class="col-xs-12">
						<span class="result-index">{$resultIndex})</span>&nbsp;
						<a href="{$summUrl}" class="result-title notranslate">
							{$summTitle|removeTrailingPunctuation|escape}
							{if !empty($summSubTitle)}{if $summSubTitle|removeTrailingPunctuation}: {$summSubTitle|removeTrailingPunctuation|highlight|truncate:180:"..."}{/if}{/if}
						</a>
					</div>
				</div>

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

				{if !empty($summVolume)}
					<div class="series row">
						<div class="result-label col-xs-3">{translate text="Volume" isPublicFacing=true} </div>
						<div class="result-value col-xs-9">
							{$summVolume|format_float_with_min_decimals}
						</div>
					</div>
				{/if}

				{if !empty($summPubDate)}
					<div class="row">
						<div class="result-label col-xs-3">{translate text="Earliest Publication Date" isPublicFacing=true} </div>
						<div class="result-value col-xs-9">
							{$summPubDate|removeTrailingPunctuation|escape}
						</div>
					</div>
				{/if}

				{if !empty($listEntryNotes)}
					<div class="row">
						<div class="result-label col-md-3">{translate text="Notes" isPublicFacing=true} </div>
						<div class="user-list-entry-note result-value col-md-9">
							{$listEntryNotes}
						</div>
					</div>
				{/if}

				<div class="row">
					{include file="GroupedWork/allManifestations.tpl" isSearchResults=true}
				</div>

				{* Description Section *}
				{if !empty($summDescription)}
					<div class="row visible-xs">
						<div class="result-label col-tn-3 col-xs-3">{translate text="Description" isPublicFacing=true}</div>
						<div class="result-value col-tn-9 col-xs-9"><a id="descriptionLink{$summId|escape}" href="#" onclick="$('#descriptionValue{$summId|escape},#descriptionLink{$summId|escape}').toggleClass('hidden-xs');return false;">{translate text="Click to view" isPublicFacing=true}</a></div>
					</div>
				{/if}

				{* Description Section *}
				{if !empty($summDescription)}
					<div class="row">
						{* Hide in mobile view *}
						<div class="result-value hidden-xs col-sm-12" id="descriptionValue{$summId|escape}">
							{$summDescription|highlight|truncate_html:450:"..."}
						</div>
					</div>
				{/if}


				<div class="resultActions row">
					{include file='GroupedWork/result-tools-horizontal.tpl' seriesId=$seriesId recordUrl=$summUrl showMoreInfo=true showNotInterested=false}
				</div>
			</div>
		</div>
	</div>
{/strip}
