{strip}
	<div class="col-sm-12">
		{foreach from=$recordDriver->getRecordVariations() item=record}
			<div class="row related-manifestation grouped" >
				{* Display Format Button (does nothing) *}
				<div class="col-tn-4 col-xs-4 col-md-3 manifestation-format">
					<a class="btn btn-primary btn-wrap">
						{translate text=$record->variationFormat isPublicFacing=true}
					</a>
				</div>
				{* Display Item Status and Info *}
				<div class="col-tn-8 col-xs-8 col-md-5 col-lg-6">
					{include file='GroupedWork/statusIndicator.tpl' statusInformation=$record->getStatusInformation() viewingIndividualRecord=0}
					{if !$record->isEContent()}
						{include file='GroupedWork/copySummary.tpl' summary=$record->getItemsDisplayedByDefault() totalCopies=$record->getCopies() itemSummaryId=$workId recordViewUrl=$record->getUrl() format=$record->format}
					{/if}
				</div>
				{* Display Hold/Action Button *}
				<div class="col-tn-8 col-tn-offset-4 col-xs-8 col-xs-offset-4 col-md-4 col-md-offset-0 col-lg-3 manifestation-actions">
					<div class="btn-toolbar">
						<div class="btn-group btn-group-vertical btn-block">
							{if $record->isHoldable() || $record->isEContent()}
							{* actions *}
							{foreach from=$record->getActions($record->variationId) item=curAction}
								<a href="{$curAction.url}" {if !empty($curAction.target)}target="{$curAction.target}"{/if} {if !empty($curAction.onclick)}onclick="{$curAction.onclick}"{/if} class="btn btn-sm {if empty($curAction.btnType)}btn-action{else}{$curAction.btnType}{/if} btn-wrap">{if !empty($curAction.target) && $curAction.target == "_blank"}<i class="fas fa-external-link-alt"></i> {/if}{$curAction.title}</a>
							{/foreach}
							{/if}
						</div>
					</div>
				</div>
			</div>
		{/foreach}
	</div>
{/strip}
