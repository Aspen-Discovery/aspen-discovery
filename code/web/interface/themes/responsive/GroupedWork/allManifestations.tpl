{if $formatDisplayStyle == 1}
	{* Short Mobile Entry for Formats when there aren't hidden formats *}
	<div class="visible-xs">
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
	<div class="{if empty($hasHiddenFormats) && count($relatedManifestations) != 1}hidden-xs {/if}col-xs-12 formatDisplayVertical" id="relatedManifestationsValue{$summId|escape}">
		{* Hide Formats section on mobile view, unless there is a single format or a format has been selected by the user *}
		{* relatedManifestationsValue ID is used by the Formats button *}
		{include file="GroupedWork/relatedManifestations.tpl" id=$summId workId=$summId}
	</div>
{else}
	<div class="col-xs-12 formatDisplayHorizontal" id="relatedManfiestations{$summId|escape}" style="margin-top: 3px;margin-bottom: 5px;">
		<div class="horizontalSliders"><div class="row horizontalFormatSelector">
			<div class="col-xs-12">
				<div class="slider-container" id="slider-{$summId|escape}">
					<div class="slider-button slider-button-prev" id="slider-prev-{$summId|escape}"></div>
					<div class="slider-wrapper">
                        {assign var=firstFormat value=""}
                        {foreach from=$relatedManifestations item=$manifestation name=manifestations}
                            {if $smarty.foreach.manifestations.index ==0}
                                {assign var=firstFormat value=$manifestation->format}
                            {/if}
							<div class="slider-slide horizontal-format-button{if $smarty.foreach.manifestations.index == 0} active{/if}"
							     data-workId="{$summId|escape}" data-format="{$manifestation->format}" data-cleanedWorkId="{$summId|regex_replace:"/-/" : ""}">
									<div class="horizontal-format-button-format">{$manifestation->format}</div>
                                    {include file='GroupedWork/statusIndicator.tpl' statusInformation=$manifestation->getStatusInformation() viewingIndividualRecord=0 applyColors=false}
							</div>
                        {/foreach}
					</div>
					<div class="slider-button slider-button-next" id="slider-next-{$summId|escape}"></div>
				</div>
				<script>
					$(document).ready(function(){ldelim}
						AspenDiscovery.GroupedWork.initializeHorizontalFormatSwipers('{$summId}');
						AspenDiscovery.GroupedWork.showManifestation('{$summId|escape}', '{$firstFormat}', '{$summId|regex_replace:"/-/" : ""}');
					{rdelim});
					AspenDiscovery.GroupedWork.groupedWorks['{$summId|regex_replace:"/-/" : ""}'] = {ldelim}
					{foreach $relatedManifestations as $manifestation}
						'{$manifestation->format}': '{$manifestation->getHorizontalFormatDisplayInfo()}',
					{/foreach}
					{rdelim};
				</script>

			</div>
		</div>
		<div class="row variationsInfo">
			<div class="col-xs-12">
				<div class="slider-container variations" id="variationsInfo_{$summId|escape}" style="display: none;">
					<div class="slider-button slider-button-prev" id="slider-prev-{$summId|escape}"></div>
					<div class="slider-wrapper" id="slider-variations-{$summId|escape}">

					</div>
					<div class="slider-button slider-button-next" id="slider-next-{$summId|escape}"></div>
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
