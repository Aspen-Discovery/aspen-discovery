{strip}
<div id="openArchivesResult{$resultIndex|escape}" class="resultsList">
	<div class="row">
		{if $showCovers}
			<div class="coversColumn col-xs-3 col-sm-3 col-md-3 col-lg-2 text-center" aria-hidden="true" role="presentation">
				{if $disableCoverArt != 1}
					<a href="{$eventUrl}" class="alignleft listResultImage" onclick="AspenDiscovery.Events.trackUsage('{$id}')" target="_blank" aria-hidden="true">
						<img src="{$bookCoverUrl}" class="listResultImage img-thumbnail" alt="{translate text='Cover Image' inAttribute=true isPublicFacing=true}">
					</a>
				{/if}
			</div>
		{/if}

		<div class="{if !$showCovers}col-xs-12{else}col-xs-9 col-sm-9 col-md-9 col-lg-10{/if}">{* May turn out to be more than one situation to consider here *}
			{* Title Row *}

			<div class="row">
				<div class="col-xs-12">
					<span class="result-index">{$resultIndex})</span>&nbsp;
					<a href="{$eventUrl}" class="result-title notranslate" onclick="AspenDiscovery.Events.trackUsage('{$id}')" target="_blank">
						{if !$title|removeTrailingPunctuation} {translate text='Title not available' isPublicFacing=true}{else}{$title|removeTrailingPunctuation|highlight|truncate:180:"..."}{/if}
					</a>
					{if isset($summScore)}
						&nbsp;(<a href="#" onclick="return AspenDiscovery.showElementInPopup('Score Explanation', '#scoreExplanationValue{$summId|escape}');">{$summScore}</a>)
					{/if}
				</div>
			</div>

			<div class="row">
				<div class="result-label col-tn-3">{translate text="Date" isPublicFacing=true} </div>
				<div class="result-value col-tn-8 notranslate">
					{$start_date|date_format:"%a %b %e, %Y from %l:%M%p"} to {$end_date|date_format:"%l:%M%p"}
                    {if $isCancelled}
						&nbsp;<span class="label label-danger">{translate text="Cancelled" isPublicFacing=true}</span>
                    {/if}
				</div>
			</div>

			{* Description Section *}
			{if $description}
				<div class="row visible-xs">
					<div class="result-label col-tn-3 col-xs-3">{translate text="Description" isPublicFacing=true}</div>
					<div class="result-value col-tn-9 col-xs-9"><a id="descriptionLink{$resultIndex|escape}" href="#" onclick="$('#descriptionValue{$resultIndex|escape},#descriptionLink{$resultIndex|escape}').toggleClass('hidden-xs');return false;">{translate text="Click to view" isPublicFacing=true}</a></div>
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
                    {include file='Events/result-tools-horizontal.tpl' recordUrl=$eventUrl showMoreInfo=true}
                    {* TODO: id & shortId shouldn't be needed to be specified here, otherwise need to note when used.
						summTitle only used by cart div, which is disabled as of now. 12-28-2015 plb *}
				</div>
			</div>
		</div>
	</div>
</div>
{/strip}