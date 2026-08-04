{strip}
    {* These are all of the grouping records for the given MARC record*}
    {foreach from=$recordDriver->getRecordVariations() item=record}
		<div class="row variationInfo"><div class="col-xs-12">
				<div class="row">
					<div class="col-tn-8">
                        {* Display Format Button (does nothing) *}
						<div class="row">
                            {include file='GroupedWork/statusIndicator.tpl' statusInformation=$record->getStatusInformation() viewingIndividualRecord=0}
                            {translate text=$record->variationFormat isPublicFacing=true inAttribute=true}
						</div>
					</div>
                    {* Display Hold/Action Button *}
					<div class="col-tn-4" style="padding-right: 0">
						<div class="btn-group btn-group-vertical btn-group-md btn-block">
                            {if $record->isHoldable() || $record->isEContent()}
                                {* actions *}
                                {foreach from=$record->getActions($record->variationId) item=curAction}
									<a href="{$curAction.url}" {if !empty($curAction.target)}target="{$curAction.target}"{/if} {if !empty($curAction.onclick)}onclick="{$curAction.onclick}"{/if} {if !empty($curAction['data-needs-refresh'])}data-needs-refresh="{$curAction['data-needs-refresh']}"{/if} {if !empty($curAction['data-record-id'])}data-record-id="{$curAction['data-record-id']}"{/if} {if !empty($curAction['data-record-source'])}data-record-source="{$curAction['data-record-source']}"{/if} class="btn btn-sm {if empty($curAction.btnType)}btn-action{else}{$curAction.btnType}{/if} btn-wrap">{if !empty($curAction.target) && $curAction.target == "_blank"}<i class="fas fa-external-link-alt" role="presentation"></i> {/if}{$curAction.title}</a>
                                {/foreach}
                            {/if}
						</div>
					</div>
				</div>
                {* Display Item Status and Info *}
				<div class="row horizDisplayShelfLocations">
                    {if $record->showCopySummary()}
                        {include file='GroupedWork/copySummaryHoriz.tpl' summary=$record->getItemsDisplayedByDefault($record->variationId) totalCopies=$record->getCopies() itemSummaryId=$workId recordViewUrl=$record->getUrl() format=$record->variationFormat isEContent=$record->isEContent()}
                    {/if}
				</div>

			</div></div>
    {/foreach}
{/strip}