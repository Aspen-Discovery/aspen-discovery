<div class="col-sm-12">
    <div class="row">
        <div class="col-tn-3 col-xs-4{if empty($viewingCombinedResults)} col-md-3{/if} manifestation-format">
            <a href="{$relatedManifestation->getUrl()}" onclick="return AspenDiscovery.ResultsList.toggleRelatedManifestations('{$workId|escapeCSS}_{$relatedManifestation->format|escapeCSS}');">
                {$relatedManifestation->format}
            </a>
            <br>
            <a href="#" onclick="return AspenDiscovery.ResultsList.toggleRelatedManifestations('{$workId|escapeCSS}_{$relatedManifestation->format|escapeCSS}');">
                <span class="manifestation-toggle-text label {if $relatedManifestation->getNumRelatedRecords() == 1}label-default{else}label-info{/if}" id='manifestation-toggle-text-{$workId|escapeCSS}_{$relatedManifestation->format|escapeCSS}'>{if $relatedManifestation->getNumRelatedRecords() == 1}Show&nbsp;Edition{else}Show&nbsp;Editions{/if}</span>
            </a>
        </div>
        <div class="col-tn-9 col-xs-8{if empty($viewingCombinedResults)} col-md-5 col-lg-6{/if}">
            {include file='GroupedWork/statusIndicator.tpl' statusInformation=$relatedManifestation->getStatusInformation() viewingIndividualRecord=0}

            {if $relatedManifestation->getNumRelatedRecords() == 1}
                {include file='GroupedWork/copySummary.tpl' summary=$relatedManifestation->getItemSummary() totalCopies=$relatedManifestation->getCopies() itemSummaryId=$workId recordViewUrl=$relatedManifestation->getUrl()}
            {else}
                {include file='GroupedWork/copySummary.tpl' summary=$relatedManifestation->getItemSummary() totalCopies=$relatedManifestation->getCopies() itemSummaryId=$workId}
            {/if}
        </div>
        <div class="col-tn-9 col-tn-offset-3 col-xs-8 col-xs-offset-4{if empty($viewingCombinedResults)} col-md-4 col-md-offset-0 col-lg-3{/if} manifestation-actions">
            <div class="btn-toolbar">
                <div class="btn-group btn-group-vertical btn-block">
                    {foreach from=$relatedManifestation->getActions() item=curAction}
                        {if !empty($curAction.url)}
                            <a href="{$curAction.url}" class="btn btn-sm btn-primary" onclick="{if $curAction.requireLogin}return AspenDiscovery.Account.followLinkIfLoggedIn(this, '{$curAction.url}');{/if}" {if !empty($curAction.alt)}title="{$curAction.alt}"{/if}>{$curAction.title}</a>
                        {else}
                            <a href="#" class="btn btn-sm btn-primary" onclick="{$curAction.onclick}" {if !empty($curAction.alt)}title="{$curAction.alt}"{/if}>{$curAction.title}</a>
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