{strip}
	{foreach from=$relatedRecords item=relatedRecord key=index}
		<div class="relatedRecord row striped-{cycle values="odd,even"} {if !empty($promptAlternateEdition) && $index===0} danger{/if}" style="padding:1px">
			{if !empty($showEditionCovers) && $showEditionCovers == 1}
				<div class="col-tn-2 col-md-2 col-lg-2">
					<img src="{$relatedRecord->getBookcoverUrl('small')}" class="img-thumbnail {$coverStyle}" alt="{translate text='Book Cover' inAttribute=true isPublicFacing=true}">
				</div>
			{/if}

			<div class="col-tn-12 {if !empty($showEditionCovers) && $showEditionCovers == 1}col-md-2 col-lg-4{else}col-md-4 col-lg-4{/if}">
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
			<div class="{if !empty($showEditionCovers) && $showEditionCovers == 1}col-tn-6 col-md-4 col-lg-3{else}col-tn-3 col-md-4 col-lg-4{/if}">
				{include file='GroupedWork/statusIndicator.tpl' statusInformation=$relatedRecord->getStatusInformation() viewingIndividualRecord=1}
				{if $relatedRecord->showCopySummary()}
					{include file='GroupedWork/copySummary.tpl' summary=$relatedRecord->getItemSummary() totalCopies=$relatedRecord->getCopies() itemSummaryId=$relatedRecord->id recordViewUrl=$relatedRecord->getUrl() isEContent=$relatedRecord->isEContent()}
				{/if}
			</div>
			<div class="{if !empty($showEditionCovers) && $showEditionCovers == 1}col-tn-6 col-md-4 col-lg-3{else}col-tn-3 col-md-4 col-lg-4{/if}">
				<div class="btn-group btn-group-vertical btn-group-md btn-block">
					<a href="{$relatedRecord->getUrl()}" class="btn btn-sm btn-info">{translate text="More Info" isPublicFacing=true}</a>
					{foreach from=$relatedRecord->getActions($variationId) item=curAction}
						<a href="{if !empty($curAction.url)}{$curAction.url}{else}#{/if}" {if !empty($curAction.onclick)}onclick="{$curAction.onclick}"{/if} class="btn btn-sm {if empty($curAction.btnType)}btn-action{else}{$curAction.btnType}{/if} btn-wrap" {if !empty($curAction.target)}target="{$curAction.target}"{/if} {if !empty($curAction.id)}id="relatedRecord{$curAction.id}"{/if} {if !empty($curAction.alt)}title="{$curAction.alt}"{/if}>{$curAction.title}</a>
					{/foreach}
				</div>
			</div>
		</div>
	{/foreach}
{/strip}
