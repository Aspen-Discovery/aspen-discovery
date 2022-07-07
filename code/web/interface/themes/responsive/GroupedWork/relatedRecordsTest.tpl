{strip}
    {foreach from=$relatedRecords item=relatedRecord key=index}
		<div class="row striped-{cycle values="odd,even"} {if !empty($promptAlternateEdition) && $index===0} danger{/if}">
			<div class="col-tn-12 col-md-4 col-lg-6" style="white-space: pre-wrap; white-space: -moz-pre-wrap; white-space: -o-pre-wrap">
                {if true}
                    {if !empty($relatedRecord->publicationDate) || !empty($relatedRecord->publisher)}
						<div><div class="result-label col-md-4 col-xs-12">{translate text="Published" isPublicFacing=true}</div><div class="result-value col-md-8 col-xs-12"><a href="{$relatedRecord->getUrl()}">{$relatedRecord->publicationDate} {$relatedRecord->publisher}</a></div></div>
                    {/if}
                    {if !empty($relatedRecord->getEContentSource())}
						<div><div class="result-label col-md-4 col-xs-12">{translate text="Source" isPublicFacing=true}</div><div class="result-value col-md-8 col-xs-12"> <a href="{$relatedRecord->getUrl()}">{translate text=$relatedRecord->getEContentSource() isPublicFacing=true}</a></div></div>
                    {/if}
                    {if !empty($relatedRecord->edition)}
						<div><div class="result-label col-md-4 col-xs-12">{translate text="Edition" isPublicFacing=true}</div><div class="result-value col-md-8 col-xs-12"> {$relatedRecord->edition}</div></div>
                    {/if}
                    {if !empty($relatedRecord->physical)}
						<div><div class="result-label col-md-4 col-xs-12">{translate text="Physical Description" isPublicFacing=true}</div><div class="result-value col-md-8 col-xs-12"> <a href="{$relatedRecord->getUrl()}">{$relatedRecord->physical} {if $relatedRecord->closedCaptioned}<i class="fas fa-closed-captioning"></i> {/if}</a></div></div>
                    {/if}
                    {if !empty($relatedRecord->languageNote)}
						<div><div class="result-label col-md-4 col-xs-12">{translate text="Language" isPublicFacing=true}</div><div class="result-value col-md-8 col-xs-12"> <a href="{$relatedRecord->getUrl()}">{$relatedRecord->physical}</a></div></div>
                    {/if}
                {else}
                    {if !empty($relatedRecord->edition)}
						<div style="margin-bottom: 3px"><small>{$relatedRecord->edition}</small></div>
                    {/if}
                    {if !empty($relatedRecord->publicationDate) || !empty($relatedRecord->publisher)}
						<div style="margin-bottom: 3px"><small><strong>{$relatedRecord->publicationDate}</strong> {$relatedRecord->publisher}</small></div>
                    {/if}
                    {if !empty($relatedRecord->getEContentSource())}
						<div style="margin-bottom: 3px"><small>{translate text=$relatedRecord->getEContentSource() isPublicFacing=true}</small></div>
                    {/if}
                    {if !empty($relatedRecord->physical)}
						<div style="margin-bottom: 3px"><small>{$relatedRecord->physical} {if $relatedRecord->closedCaptioned}<i class="fas fa-closed-captioning"></i> {/if}</small></div>
                    {/if}
                    {if !empty($relatedRecord->languageNote)}
						<div style="margin-bottom: 3px"><small>{$relatedRecord->languageNote}</small></div>
                    {/if}
                {/if}
			</div>
			<div class="col-tn-8 col-md-4 col-lg-3">
                {include file='GroupedWork/statusIndicator.tpl' statusInformation=$relatedRecord->getStatusInformation() viewingIndividualRecord=1}
                {if !$relatedRecord->isEContent()}
                    {include file='GroupedWork/copySummary.tpl' summary=$relatedRecord->getItemSummary() totalCopies=$relatedRecord->getCopies() itemSummaryId=$relatedRecord->id recordViewUrl=$relatedRecord->getUrl()}
                {/if}
			</div>
			<div class="col-tn-4 col-md-4 col-lg-3t">
				<div class="btn-group btn-group-vertical btn-group-sm text-right">
					<a href="{$relatedRecord->getUrl()}" class="btn btn-sm btn-info">{translate text="More Info" isPublicFacing=true}</a>
                    {foreach from=$relatedRecord->getActions() item=curAction}
						<a href="{if !empty($curAction.url)}{$curAction.url}{else}#{/if}" {if $curAction.onclick}onclick="{$curAction.onclick}"{/if} class="btn btn-sm {if empty($curAction.btnType)}btn-action{else}{$curAction.btnType}{/if} btn-wrap" {if !empty($curAction.target)}target="{$curAction.target}"{/if} {if !empty($curAction.alt)}title="{$curAction.alt}"{/if}>{$curAction.title}</a>
                    {/foreach}
				</div>
			</div>
		</div>
    {/foreach}
{/strip}