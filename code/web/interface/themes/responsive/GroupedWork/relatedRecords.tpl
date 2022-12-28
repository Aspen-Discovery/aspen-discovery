{strip}
    {foreach from=$relatedRecords item=relatedRecord key=index}
		<div class="row striped-{cycle values="odd,even"} {if !empty($promptAlternateEdition) && $index===0} danger{/if}" style="padding:8px">
			<div class="col-tn-12 col-md-4 col-lg-6">
                {if !empty($showRelatedRecordLabels)}
                    {if !empty($relatedRecord->publicationDate) || !empty($relatedRecord->publisher)}
						<div class="row"><div class="result-label col-lg-5 col-tn-12">{translate text="Published" isPublicFacing=true}</div><div class="result-value col-lg-7 col-tn-12"><a href="{$relatedRecord->getUrl()}">{$relatedRecord->publicationDate} {$relatedRecord->publisher}</a></div></div>
                    {/if}
                    {if !empty($relatedRecord->getEContentSource())}
						<div class="row"><div class="result-label col-lg-5 col-tn-12">{translate text="Source" isPublicFacing=true}</div><div class="result-value col-lg-7 col-tn-12"> <a href="{$relatedRecord->getUrl()}">{translate text=$relatedRecord->getEContentSource() isPublicFacing=true}</a></div></div>
                    {/if}
                    {if !empty($relatedRecord->edition)}
						<div class="row"><div class="result-label col-lg-5 col-tn-12">{translate text="Edition" isPublicFacing=true}</div><div class="result-value col-lg-7 col-tn-12"> {$relatedRecord->edition}</div></div>
                    {/if}
                    {if !empty($relatedRecord->physical)}
						<div class="row"><div class="result-label col-lg-5 col-tn-12">{translate text="Physical Description" isPublicFacing=true}</div><div class="result-value col-lg-7 col-tn-12"> <a href="{$relatedRecord->getUrl()}">{$relatedRecord->physical} {if $relatedRecord->closedCaptioned}<i class="fas fa-closed-captioning"></i> {/if}</a></div></div>
                    {/if}
                    {if !empty($relatedRecord->languageNote)}
						<div class="row"><div class="result-label col-lg-5 col-tn-12">{translate text="Language" isPublicFacing=true}</div><div class="result-value col-lg-7 col-tn-12"> <a href="{$relatedRecord->getUrl()}">{$relatedRecord->physical}</a></div></div>
                    {/if}
                {else}
                    {if !empty($relatedRecord->publicationDate) || !empty($relatedRecord->publisher)}
						<div style="margin-bottom: 3px"><strong>{$relatedRecord->publicationDate}</strong> {$relatedRecord->publisher}</div>
                    {/if}
                    {if !empty($relatedRecord->edition)}
						<div style="margin-bottom: 3px">{$relatedRecord->edition}</div>
                    {/if}
                    {if !empty($relatedRecord->getEContentSource())}
						<div style="margin-bottom: 3px">{translate text=$relatedRecord->getEContentSource() isPublicFacing=true}</div>
                    {/if}
                    {if !empty($relatedRecord->physical)}
						<div style="margin-bottom: 3px">{$relatedRecord->physical} {if $relatedRecord->closedCaptioned}<i class="fas fa-closed-captioning"></i> {/if}</div>
                    {/if}
                    {if !empty($relatedRecord->languageNote)}
						<div style="margin-bottom: 3px">{$relatedRecord->languageNote}</div>
                    {/if}
                {/if}
			</div>
			<div class="col-tn-8 col-md-4 col-lg-4">
                {include file='GroupedWork/statusIndicator.tpl' statusInformation=$relatedRecord->getStatusInformation() viewingIndividualRecord=1}
                {if !$relatedRecord->isEContent()}
                    {include file='GroupedWork/copySummary.tpl' summary=$relatedRecord->getItemSummary() totalCopies=$relatedRecord->getCopies() itemSummaryId=$relatedRecord->id recordViewUrl=$relatedRecord->getUrl()}
                {/if}
			</div>
			<div class="col-tn-4 col-md-4 col-lg-2">
				<div class="btn-group btn-group-vertical btn-group-sm text-right">
					<a href="{$relatedRecord->getUrl()}" class="btn btn-sm btn-info">{translate text="More Info" isPublicFacing=true}</a>
                    {foreach from=$relatedRecord->getActions() item=curAction}
						<a href="{if !empty($curAction.url)}{$curAction.url}{else}#{/if}" {if !empty($curAction.onclick)}onclick="{$curAction.onclick}"{/if} class="btn btn-sm {if empty($curAction.btnType)}btn-action{else}{$curAction.btnType}{/if} btn-wrap" {if !empty($curAction.target)}target="{$curAction.target}"{/if} {if !empty($curAction.alt)}title="{$curAction.alt}"{/if}>{$curAction.title}</a>
                    {/foreach}
				</div>
			</div>
		</div>
    {/foreach}
{/strip}