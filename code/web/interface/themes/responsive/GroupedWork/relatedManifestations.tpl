{strip}
	<div class="related-manifestations">
		{assign var=hasHiddenFormats value=false}
		{foreach from=$relatedManifestations item=relatedManifestation}
			{if $relatedManifestation->hasHiddenFormats() || (isset($activeFormat) && $relatedManifestation->format != $activeFormat)}
				{assign var=hasHiddenFormats value=true}
			{/if}
			{* Display the manifestation (the format being displayed) *}
			<div class="row related-manifestation grouped {if $relatedManifestation->isHideByDefault() || (isset($activeFormat) && $relatedManifestation->format != $activeFormat)}hiddenManifestation_{$workId}{/if}" {if $relatedManifestation->isHideByDefault() || (isset($activeFormat) && $relatedManifestation->format != $activeFormat)}style="display: none"{/if}>
				{* Display information about the format *}
				{if $relatedManifestation->getNumVariations() == 1}
					{include file="GroupedWork/singleVariationManifestion.tpl" workId=$workId}
				{else}
					{include file="GroupedWork/multipleVariationManifestion.tpl" workId=$workId summTitle=$recordDriver->getTitle()}
				{/if}
			</div>
		{foreachelse}
			<div class="row related-manifestation">
				<div class="col-xs-12">
					{translate text="The library does not own any copies of this title." isPublicFacing=true}
				</div>
			</div>
		{/foreach}
		{if !empty($hasHiddenFormats)}
			<div class="row related-manifestation" id="formatToggle_{$workId}">
				<div class="col-xs-12">
					<a href="#" onclick="$('.hiddenManifestation_{$workId}').show();$('#formatToggle_{$workId}').hide();return false;" class="showHidden">{translate text="View all Formats" isPublicFacing=true}</a>
				</div>
			</div>
		{/if}
	</div>
{/strip}
