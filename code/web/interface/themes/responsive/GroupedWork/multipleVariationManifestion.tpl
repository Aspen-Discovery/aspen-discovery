{strip}
<div class="col-sm-12">
	<div class="row">
		<div class="col-sm-12 manifestation-format">
			{translate text=$relatedManifestation->format isPublicFacing=true}
		</div>
	</div>
	{foreach from=$relatedManifestation->getVariations() item=variation}
		<div class="row {if $variation->isHideByDefault()}hiddenManifestation_{$summId}{else} displayed striped-{cycle values="odd,even"}{/if}" {if $variation->isHideByDefault()}style="display: none"{/if}>
			<div class="col-tn-4 col-xs-4{if empty($viewingCombinedResults) || !$viewingCombinedResults} col-md-3{/if} manifestation-format">
				<a class="btn btn-xs btn-primary btn-variation btn-wrap" href="{$variation->getUrl()}" onclick="return AspenDiscovery.ResultsList.toggleRelatedManifestations('{$workId|escapeCSS}_{$relatedManifestation->format|escapeCSS}');"aria-label="{translate text="View Manifestations for %1% %2 of %3" 1=$relatedManifestation->format 2=$variation->label 3=$summTitle inAttribute=true isPublicFacing=true}">
					{translate text=$variation->label isPublicFacing=true}
				</a>
				<br>
				<a href="#" onclick="return AspenDiscovery.ResultsList.toggleRelatedManifestations('{$workId|escapeCSS}_{$relatedManifestation->format|escapeCSS}_{$variation->id|escapeCSS}');">
					<span class="manifestation-toggle-text btn btn-xs btn-wrap btn-editions" id='manifestation-toggle-text-{$workId|escapeCSS}_{$variation->format|escapeCSS}'>{if $variation->getNumRelatedRecords() == 1}{translate text='Show Edition' isPublicFacing=true}{else}{translate text='Show Editions' isPublicFacing=true}{/if}</span>
				</a>
			</div>
			<div class="col-tn-5 col-xs-8{if empty($viewingCombinedResults) || !$viewingCombinedResults} col-md-5 col-lg-6{/if}">
				{include file='GroupedWork/statusIndicator.tpl' statusInformation=$variation->getStatusInformation() viewingIndividualRecord=0}

				{if $variation->getNumRelatedRecords() == 1}
					{include file='GroupedWork/copySummary.tpl' summary=$variation->getItemsDisplayedByDefault() totalCopies=$variation->getCopies() itemSummaryId="`$workId`_`$variation->label`" recordViewUrl=$variation->getUrl() format=$relatedManifestation->format}
				{else}
					{include file='GroupedWork/copySummary.tpl' summary=$variation->getItemsDisplayedByDefault() totalCopies=$variation->getCopies() itemSummaryId="`$workId`_`$variation->label`" format=$relatedManifestation->format}
				{/if}
			</div>
			<div class="col-tn-8 col-tn-offset-4 col-xs-8 col-xs-offset-4{if empty($viewingCombinedResults) || !$viewingCombinedResults} col-md-4 col-md-offset-0 col-lg-3{/if} manifestation-actions">
				<div class="btn-toolbar">
					<div class="btn-group btn-group-vertical btn-block">
						{foreach from=$variation->getActions() item=curAction}
							{if $curAction.url && strlen($curAction.url) > 0}
								<a href="{$curAction.url}" class="btn btn-sm {if empty($curAction.btnType)}btn-action{else}{$curAction.btnType}{/if} btn-wrap" {if !empty($curAction.target)}target="{$curAction.target}"{/if} onclick="{if $curAction.requireLogin}return AspenDiscovery.Account.followLinkIfLoggedIn(this, '{$curAction.url}');{/if}" {if !empty($curAction.alt)}title="{translate text=$curAction.alt inAttribute=true isPublicFacing=true}"{/if}>{if $curAction.target == "_blank"}<i class="fas fa-external-link-alt"></i> {/if}{$curAction.title}</a>
							{else}
								<a href="#" class="btn btn-sm {if empty($curAction.btnType)}btn-action{else}{$curAction.btnType}{/if} btn-wrap" {if !empty($curAction.target)}target="{$curAction.target}"{/if} onclick="{$curAction.onclick}" {if $curAction.alt}title="{translate text=$curAction.alt inAttribute=true}"{/if}>{if $curAction.target == "_blank"}<i class="fas fa-external-link-alt"></i> {/if}{$curAction.title}</a>
							{/if}
						{/foreach}
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-12" id="relatedRecordPopup_{$workId|escapeCSS}_{$relatedManifestation->format|escapeCSS}_{$variation->id|escapeCSS}" style="display:none">
				{include file="GroupedWork/relatedRecords.tpl" relatedRecords=$variation->getRelatedRecords() relatedManifestation=$relatedManifestation}
			</div>
		</div>
	{/foreach}
</div>
{/strip}