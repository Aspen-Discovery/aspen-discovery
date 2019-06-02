{strip}
<div class="col-sm-12">
    <div class="row">
        <div class="col-sm-12 manifestation-format">
            {$relatedManifestation->format}
        </div>
    </div>
    {foreach from=$relatedManifestation->getVariations() item=variation}
        <div class="row {if $variation->isHideByDefault()}hiddenManifestation_{$summId}{/if}" {if $variation->isHideByDefault()}style="display: none"{/if}>
            <div class="col-tn-3 col-xs-4{if !$viewingCombinedResults} col-md-3{/if} manifestation-format">
                &nbsp;&nbsp;&nbsp;
                <a href="{$variation->getUrl()}" onclick="return AspenDiscovery.ResultsList.toggleRelatedManifestations('{$id|escapeCSS}_{$relatedManifestation->format|escapeCSS}_{$variation->id|escapeCSS}');">
                    {$variation->label}
                </a>
                <br>&nbsp;&nbsp;&nbsp;
                <a href="#" onclick="return AspenDiscovery.ResultsList.toggleRelatedManifestations('{$id|escapeCSS}_{$relatedManifestation->format|escapeCSS}_{$variation->id|escapeCSS}');">
                    <span class="manifestation-toggle-text label {if $variation->getNumRelatedRecords() == 1}label-default{else}label-info{/if}" id='manifestation-toggle-text-{$id|escapeCSS}_{$variation->format|escapeCSS}'>{if $variation->getNumRelatedRecords() == 1}Show&nbsp;Edition{else}Show&nbsp;Editions{/if}</span>
                </a>
            </div>
            <div class="col-tn-9 col-xs-8{if !$viewingCombinedResults} col-md-5 col-lg-6{/if}">
                {include file='GroupedWork/statusIndicator.tpl' statusInformation=$variation->getStatusInformation() viewingIndividualRecord=0}

                {if $variation->getNumRelatedRecords() == 1}
                    {include file='GroupedWork/copySummary.tpl' summary=$variation->getItemSummary() totalCopies=$variation->getCopies() itemSummaryId="`$id`_`$variation->label`" recordViewUrl=$variation->getUrl()}
                {else}
                    {include file='GroupedWork/copySummary.tpl' summary=$variation->getItemSummary() totalCopies=$variation->getCopies() itemSummaryId="`$id`_`$variation->label`"}
                {/if}
            </div>
            <div class="col-tn-9 col-tn-offset-3 col-xs-8 col-xs-offset-4{if !$viewingCombinedResults} col-md-4 col-md-offset-0 col-lg-3{/if} manifestation-actions">
                <div class="btn-toolbar">
                    <div class="btn-group btn-group-vertical btn-block">
                        {foreach from=$variation->getActions() item=curAction}
                            {if $curAction.url && strlen($curAction.url) > 0}
                                <a href="{$curAction.url}" class="btn btn-sm btn-primary" onclick="{if $curAction.requireLogin}return AspenDiscovery.Account.followLinkIfLoggedIn(this, '{$curAction.url}');{/if}" {if $curAction.alt}title="{$curAction.alt}"{/if}>{$curAction.title}</a>
                            {else}
                                <a href="#" class="btn btn-sm btn-primary" onclick="{$curAction.onclick}" {if $curAction.alt}title="{$curAction.alt}"{/if}>{$curAction.title}</a>
                            {/if}
                        {/foreach}
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12" id="relatedRecordPopup_{$id|escapeCSS}_{$relatedManifestation->format|escapeCSS}_{$variation->id|escapeCSS}" style="display:none">
                {include file="GroupedWork/relatedRecords.tpl" relatedRecords=$variation->getRelatedRecords() relatedManifestation=$relatedManifestation}
            </div>
        </div>
    {/foreach}
</div>
{/strip}