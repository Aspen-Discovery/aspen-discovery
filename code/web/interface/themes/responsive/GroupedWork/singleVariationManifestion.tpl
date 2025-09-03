{strip}
<div class="col-xs-12">
	<div class="row">
		{if $printInterface === false || ($printInterface === true && $printEntryFormats === true)}
		<div class="col-tn-4 col-xs-4{if empty($viewingCombinedResults)} col-md-3{/if} manifestation-format">
			<a class="btn btn-xs btn-primary btn-wrap" href="{$relatedManifestation->getUrl()}" {if $relatedManifestation->getNumRelatedRecords() > 1}onclick="return AspenDiscovery.ResultsList.showRelatedManifestations('{$workId|escapeCSS}','{$relatedManifestation->format|escapeCSS}','{$relatedManifestation->getFirstVariation()->databaseId|escapeCSS}');" aria-label="View Manifestations for {translate text=$relatedManifestation->format inAttribute=true isPublicFacing=true}"{else} aria-label="View {translate text=$relatedManifestation->format inAttribute=true}"{/if}>
				{translate text=$relatedManifestation->format isPublicFacing=true}
			</a>
			<br>
			<a href="#" onclick="return AspenDiscovery.ResultsList.showRelatedManifestations('{$workId|escapeCSS}','{$relatedManifestation->format|escapeCSS}', '{$relatedManifestation->getFirstVariation()->databaseId|escapeCSS}');" aria-label="View Editions for {translate text=$relatedManifestation->format inAttribute=true}">
				<span class="manifestation-toggle-text btn btn-xs btn-wrap btn-editions" id='manifestation-toggle-text-{$workId|escapeCSS}_{$relatedManifestation->format|escapeCSS}_{$relatedManifestation->getFirstVariation()->databaseId|escapeCSS}'><i class='fas fa-spinner fa-spin hidden' role='status' aria-hidden='true'></i>&nbsp;{if $relatedManifestation->getNumRelatedRecords() == 1}{translate text='Show Edition' isPublicFacing=true}{else}{translate text='Show Editions' isPublicFacing=true}{/if}</span>
			</a>
		</div>
		{/if}
		<div class="col-tn-8 col-xs-8{if empty($viewingCombinedResults)} col-md-5 col-lg-6{/if}">
			{if $printInterface === false}
			{include file='GroupedWork/statusIndicator.tpl' statusInformation=$relatedManifestation->getStatusInformation() viewingIndividualRecord=0}
			{/if}
            {if $printInterface === false || ($printInterface === true && $printEntryHoldings === true)}
			{if $relatedManifestation->showCopySummary()}
				{if $relatedManifestation->getNumRelatedRecords() == 1}
					{include file='GroupedWork/copySummary.tpl' summary=$relatedManifestation->getItemsDisplayedByDefault() totalCopies=$relatedManifestation->getCopies() itemSummaryId=$workId recordViewUrl=$relatedManifestation->getUrl() format=$relatedManifestation->format isEContent=$relatedManifestation->isEContent()}
				{else}
					{include file='GroupedWork/copySummary.tpl' summary=$relatedManifestation->getItemsDisplayedByDefault() totalCopies=$relatedManifestation->getCopies() itemSummaryId=$workId format=$relatedManifestation->format isEContent=$relatedManifestation->isEContent()}
				{/if}
			{/if}
			{/if}
		</div>
		{if $printInterface === false}
		<div class="col-tn-8 col-tn-offset-4 col-xs-8 col-xs-offset-4{if empty($viewingCombinedResults)} col-md-4 col-md-offset-0 col-lg-3{/if} manifestation-actions">
			<div class="btn-toolbar">
				<div class="btn-group btn-group-vertical btn-block">
					{foreach from=$relatedManifestation->getActions() item=curAction}
						{if $relatedManifestation->showActionButton()}
							{if !empty($curAction.url)}
								<a href="{$curAction.url}" class="btn btn-sm {if empty($curAction.btnType)}btn-action{else}{$curAction.btnType}{/if} btn-wrap" {if !empty($curAction.target)}target="{$curAction.target}"{/if} id="actionButton" onclick="{if !empty($curAction.requireLogin)}return AspenDiscovery.Account.followLinkIfLoggedIn(this, '{$curAction.url}');{/if}" {if !empty($curAction.alt)}title="{translate text=$curAction.alt inAttribute=true isPublicFacing=true}"{/if}>{if !empty($curAction.target) && $curAction.target == "_blank"}<i class="fas fa-external-link-alt" role="presentation"></i> {/if}{$curAction.title}</a>
							{else}
								<a href="#" class="btn btn-sm {if empty($curAction.btnType)}btn-action{else}{$curAction.btnType}{/if} btn-wrap" {if !empty($curAction.id)}id="{$curAction.id}"{/if}{if !empty($curAction.target)}target="{$curAction.target}"{/if} {if !empty($curAction.id)}id="{$curAction.id}"{/if} onclick="{$curAction.onclick}" {if !empty($curAction.alt)}title="{translate text=$curAction.alt inAttribute=true isPublicFacing=true}"{/if}>{if !empty($curAction.target) && $curAction.target == "_blank"}<i class="fas fa-external-link-alt" role="presentation"></i> {/if}{$curAction.title}</a>
							{/if}
						{/if}
					{/foreach}
				</div>
			</div>
		</div>
		{/if}
	</div>
	<div class="row">
		<div class="col-xs-12" id="relatedRecordPopup_{$workId|escapeCSS}_{$relatedManifestation->format|escapeCSS}_{$relatedManifestation->getFirstVariation()->databaseId}" style="display:none">
		</div>
	</div>
</div>
{/strip}