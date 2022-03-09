{strip}
<div class="col-sm-12">
	<div class="row">
		<div class="col-tn-4 col-xs-4{if empty($viewingCombinedResults)} col-md-3{/if} manifestation-format">
			<a class="btn btn-xs btn-primary btn-wrap" href="{$relatedManifestation->getUrl()}" {if $relatedManifestation->getNumRelatedRecords() > 1}onclick="return AspenDiscovery.ResultsList.toggleRelatedManifestations('{$workId|escapeCSS}_{$relatedManifestation->format|escapeCSS}');" aria-label="View Manifestations for {translate text=$relatedManifestation->format inAttribute=true isPublicFacing=true} of {$summTitle}"{else} aria-label="View {$summTitle} ({translate text=$relatedManifestation->format inAttribute=true})"{/if}>
				{translate text=$relatedManifestation->format isPublicFacing=true}
			</a>
			<br>
			<a href="#" onclick="return AspenDiscovery.ResultsList.toggleRelatedManifestations('{$workId|escapeCSS}_{$relatedManifestation->format|escapeCSS}');" aria-label="View Editions for {translate text=$relatedManifestation->format inAttribute=true} of {$summTitle}">
				<span class="manifestation-toggle-text btn btn-xs btn-wrap btn-editions" id='manifestation-toggle-text-{$workId|escapeCSS}_{$relatedManifestation->format|escapeCSS}'>{if $relatedManifestation->getNumRelatedRecords() == 1}{translate text='Show Edition' isPublicFacing=true}{else}{translate text='Show Editions' isPublicFacing=true}{/if}</span>
			</a>
		</div>
		<div class="col-tn-8 col-xs-8{if empty($viewingCombinedResults)} col-md-5 col-lg-6{/if}">
			{include file='GroupedWork/statusIndicator.tpl' statusInformation=$relatedManifestation->getStatusInformation() viewingIndividualRecord=0}
			{if !$relatedManifestation->isEContent()}
				{if $relatedManifestation->getNumRelatedRecords() == 1}
					{include file='GroupedWork/copySummary.tpl' summary=$relatedManifestation->getItemsDisplayedByDefault() totalCopies=$relatedManifestation->getCopies() itemSummaryId=$workId recordViewUrl=$relatedManifestation->getUrl() format=$relatedManifestation->format}
				{else}
					{include file='GroupedWork/copySummary.tpl' summary=$relatedManifestation->getItemsDisplayedByDefault() totalCopies=$relatedManifestation->getCopies() itemSummaryId=$workId format=$relatedManifestation->format}
				{/if}
			{/if}
		</div>
		<div class="col-tn-8 col-tn-offset-4 col-xs-8 col-xs-offset-4{if empty($viewingCombinedResults)} col-md-4 col-md-offset-0 col-lg-3{/if} manifestation-actions">
			<div class="btn-toolbar">
				<div class="btn-group btn-group-vertical btn-block">
					{foreach from=$relatedManifestation->getActions() item=curAction}
						{if !empty($curAction.url)}
							<a href="{$curAction.url}" class="btn btn-sm {if empty($curAction.btnType)}btn-action{else}{$curAction.btnType}{/if} btn-wrap" {if !empty($curAction.target)}target="{$curAction.target}"{/if} onclick="{if $curAction.requireLogin}return AspenDiscovery.Account.followLinkIfLoggedIn(this, '{$curAction.url}');{/if}" {if !empty($curAction.alt)}title="{translate text=$curAction.alt inAttribute=true isPublicFacing=true}"{/if}>{if $curAction.target == "_blank"}<i class="fas fa-external-link-alt"></i> {/if}{$curAction.title}</a>
						{else}
							<a href="#" class="btn btn-sm {if empty($curAction.btnType)}btn-action{else}{$curAction.btnType}{/if} btn-wrap" {if !empty($curAction.target)}target="{$curAction.target}"{/if} onclick="{$curAction.onclick}" {if !empty($curAction.alt)}title="{translate text=$curAction.alt inAttribute=true isPublicFacing=true}"{/if}>{if $curAction.target == "_blank"}<i class="fas fa-external-link-alt"></i> {/if}{$curAction.title}</a>
						{/if}
					{/foreach}
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-sm-12" id="relatedRecordPopup_{$workId|escapeCSS}_{$relatedManifestation->format|escapeCSS}" style="display:none">
			{include file="GroupedWork/relatedRecords.tpl" relatedRecords=$relatedManifestation->getRelatedRecords() relatedManifestation=$relatedManifestation}
		</div>
	</div>
</div>
{/strip}