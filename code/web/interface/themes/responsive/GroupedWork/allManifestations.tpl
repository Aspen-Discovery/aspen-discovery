{if $formatDisplayStyle == 1}
	{* Short Mobile Entry adapts based on manifestation count *}
	<div class="visible-xs">
		{* Determine if there were hidden Formats for this entry *}
		{assign var=hasHiddenFormats value=false}
		{foreach from=$relatedManifestations item=relatedManifestation}
			{if $relatedManifestation->hasHiddenFormats()}
				{assign var=hasHiddenFormats value=true}
			{/if}
		{/foreach}


		{assign var=hideInMobile value=$hideManifestationsInMobileView|default:1}
		{if empty($hasHiddenFormats) && $hideInMobile && count($relatedManifestations) > 1}
			<div class="hidethisdiv{$summId|escape} result-label col-sm-4 col-xs-12">
				{translate text="Formats" isPublicFacing=true}
			</div>
			<div class="hidethisdiv{$summId|escape} result-value col-sm-8 col-xs-12">
				<a onclick="$('#relatedManifestationsValue{$summId|escape},.hidethisdiv{$summId|escape}').toggleClass('hidden-xs');return false;" role="button">
					{implode subject=$relatedManifestations|@array_keys glue=", "}
				</a>
			</div>
		{/if}
	</div>

	{* Formats Section *}
	<div class="{if empty($hasHiddenFormats) && $hideInMobile && count($relatedManifestations) > 1}hidden-xs {/if}col-xs-12 formatDisplayVertical" id="relatedManifestationsValue{$summId|escape}">
		{* Hide Formats section on mobile view, unless there is a single format or a format has been selected by the user *}
		{* relatedManifestationsValue ID is used by the Formats button *}
		{include file="GroupedWork/relatedManifestations.tpl" id=$summId workId=$summId}
	</div>
{else}
	{if count($relatedManifestations) == 0}
		<div class="col-xs-12 formatDisplayHorizontal" id="relatedManfiestations{$summId|escape}" style="margin-top: 1.5em;margin-bottom: 1em;">
			<div class="row related-manifestation">
				<div class="col-xs-12">
					<span class="noCopiesOwnedMessage">{translate text="The library does not own any copies of this title." isPublicFacing=true}</span>
				</div>
			</div>
		</div>
	{else}
		<div class="col-xs-12 formatDisplayHorizontal" id="relatedManfiestations{$summId|escape}" style="margin-top: 3px;margin-bottom: 5px;">
			<div class="horizontalSliders"><div class="row horizontalFormatSelector">
				<div class="col-xs-12">
					<div class="slider-container" role="region" id="slider-{$summId|escape}">
						<button type="button" class="slider-button slider-button-prev btn btn-editions" id="slider-prev-{$summId|escape}" aria-label="{translate text="Previous Format" isPublicFacing=true inAttribute=true}"><i class="fas fa-chevron-left"></i></button>
						<div class="slider-wrapper" role="listbox" aria-activedescendant="slide-{$summId|escape}-0">
	                        {assign var=firstFormat value=""}
	                        {foreach from=$relatedManifestations item=$manifestation name=manifestations}
	                            {if $smarty.foreach.manifestations.index ==0}
	                                {assign var=firstFormat value=$manifestation->format}
	                            {/if}
								<div role="option" tabindex="0" class="slider-slide horizontal-format-button{if $smarty.foreach.manifestations.index == 0} active{/if}"
								     data-workId="{$summId|escape}" data-format="{$manifestation->format}" data-cleanedWorkId="{$summId|regex_replace:"/-/" : ""}" aria-selected="{if $smarty.foreach.manifestations.index == 0}true{else}false{/if}">
										<div class="horizontal-format-button-format">{$manifestation->format}</div>
	                                    {include file='GroupedWork/statusIndicator.tpl' statusInformation=$manifestation->getStatusInformation() viewingIndividualRecord=0 applyColors=false hideCopiesLine=true}
								</div>
	                        {/foreach}
						</div>
						<button type="button" class="slider-button slider-button-next btn btn-editions" id="slider-next-{$summId|escape}" aria-label="{translate text="Next Format" isPublicFacing=true inAttribute=true}"><i class="fas fa-chevron-right"></i></button>
					</div>
					<script>
						$(document).ready(function(){ldelim}
							AspenDiscovery.GroupedWork.initializeHorizontalFormatSwipers('{$summId}');
							AspenDiscovery.GroupedWork.showManifestation('{$summId|escape}', '{$firstFormat}', '{$summId|regex_replace:"/-/" : ""}');
						{rdelim});
						AspenDiscovery.GroupedWork.groupedWorks['{$summId|regex_replace:"/-/" : ""}'] = {ldelim}
						{foreach $relatedManifestations as $manifestation}
							'{$manifestation->format|regex_replace:"/'/" : "\'"}': '{$manifestation->getHorizontalFormatDisplayInfo()|regex_replace:"/'/" : "\'"}',
						{/foreach}
						{rdelim};
					</script>

				</div>
			</div>
			<div class="row variationsInfo">
				<div class="col-xs-12">
					<div role="region" class="slider-container variations" id="variationsInfo_{$summId|escape}" style="display: none;">
						<button type="button" class="slider-button slider-button-prev btn btn-editions" id="slider-prev-{$summId|escape}" aria-label="{translate text="Previous Edition" isPublicFacing=true inAttribute=true}"><i class="fas fa-chevron-left"></i></button>
						<div role="listbox" class="slider-wrapper" id="slider-variations-{$summId|escape}" aria-activedescendant="slide-{$summId|escape}-0">

						</div>
						<button type="button" class="slider-button slider-button-next btn btn-editions" id="slider-next-{$summId|escape}" aria-label="{translate text="Next Edition" isPublicFacing=true inAttribute=true}"><i class="fas fa-chevron-right"></i></button>
					</div>
				</div>
			</div>
			</div>
			<div class="row variationInfo">
				<div class="col-xs-12">
					<div id="variationInfo_{$summId|escape}">
					</div>
				</div>
			</div>
		</div>
	{/if}
{/if}
