{strip}
<div class="col-sm-12">
	<div class="row">
		<div class="col-sm-12 manifestation-format">
			{$relatedManifestation->format|translate}
		</div>
	</div>
	{foreach from=$relatedManifestation->getVariations() item=variation}
		<div class="row {if $variation->isHideByDefault()}hiddenManifestation_{$summId}{/if}" {if $variation->isHideByDefault()}style="display: none"{/if}>
			<div class="col-tn-4 col-xs-4{if !$viewingCombinedResults} col-md-3{/if} manifestation-format">
				&nbsp;&nbsp;&nbsp;
				<a class="btn btn-xs btn-primary btn-variation btn-wrap" href="{$variation->getUrl()}" onclick="return AspenDiscovery.ResultsList.toggleRelatedManifestations('{$workId|escapeCSS}_{$relatedManifestation->format|escapeCSS}_{$variation->id|escapeCSS}');"aria-label="View Manifestations for {translate text=$relatedManifestation->format inAttribute=true} {$variation->label} of {$summTitle}">
					{$variation->label}
				</a>
				<br>&nbsp;&nbsp;&nbsp;
				<a href="#" onclick="return AspenDiscovery.ResultsList.toggleRelatedManifestations('{$workId|escapeCSS}_{$relatedManifestation->format|escapeCSS}_{$variation->id|escapeCSS}');">
					<span class="manifestation-toggle-text btn btn-xs btn-editions" id='manifestation-toggle-text-{$workId|escapeCSS}_{$variation->format|escapeCSS}'>{if $variation->getNumRelatedRecords() == 1}{translate text='Show Edition'}{else}{translate text='Show Editions'}{/if}</span>
				</a>
			</div>
			<div class="col-tn-5 col-xs-8{if !$viewingCombinedResults} col-md-5 col-lg-6{/if}">
				{include file='GroupedWork/statusIndicator.tpl' statusInformation=$variation->getStatusInformation() viewingIndividualRecord=0}

				{if $variation->getNumRelatedRecords() == 1}
					{include file='GroupedWork/copySummary.tpl' summary=$variation->getItemSummary() totalCopies=$variation->getCopies() itemSummaryId="`$workId`_`$variation->label`" recordViewUrl=$variation->getUrl() format=$relatedManifestation->format}
				{else}
					{include file='GroupedWork/copySummary.tpl' summary=$variation->getItemSummary() totalCopies=$variation->getCopies() itemSummaryId="`$workId`_`$variation->label`" format=$relatedManifestation->format}
				{/if}
			</div>
			<div class="col-tn-8 col-tn-offset-4 col-xs-8 col-xs-offset-4{if !$viewingCombinedResults} col-md-4 col-md-offset-0 col-lg-3{/if} manifestation-actions">
				<div class="btn-toolbar">
					<div class="btn-group btn-group-vertical btn-block">
						{foreach from=$variation->getActions() item=curAction}
							{if $curAction.url && strlen($curAction.url) > 0}
								<a href="{$curAction.url}" class="btn btn-sm {if empty($curAction.btnType)}btn-action{else}{$curAction.btnType}{/if} btn-wrap" onclick="{if $curAction.requireLogin}return AspenDiscovery.Account.followLinkIfLoggedIn(this, '{$curAction.url}');{/if}" {if $curAction.alt}title="{translate text=$curAction.alt inAttribute=true}"{/if}>{$curAction.title|translate}</a>
							{else}
								<a href="#" class="btn btn-sm {if empty($curAction.btnType)}btn-action{else}{$curAction.btnType}{/if} btn-wrap" onclick="{$curAction.onclick}" {if $curAction.alt}title="{translate text=$curAction.alt inAttribute=true}"{/if}>{$curAction.title|translate}</a>
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