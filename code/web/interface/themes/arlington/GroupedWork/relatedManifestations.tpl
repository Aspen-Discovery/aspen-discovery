{strip}
	<div class="related-manifestations">
		<div class="row related-manifestations-header">
			<div class="col-xs-12 result-label related-manifestations-label">
				{translate text="Choose a Format"}
			</div>
		</div>
		{assign var=hasHiddenFormats value=false}
		{foreach from=$relatedManifestations item=relatedManifestation}
			{if $relatedManifestation.hideByDefault}
				{assign var=hasHiddenFormats value=true}
			{/if}
			<div class="row related-manifestation {if $relatedManifestation.hideByDefault}hiddenManifestation_{$summId}{/if}" {if $relatedManifestation.hideByDefault}style="display: none"{/if}>
				<div class="col-sm-12">
				  <div class="row">
						<div class="col-tn-3 col-xs-4 col-md-3 manifestation-format">
							{if $relatedManifestation.numRelatedRecords == 1}
								<a class="btn btn-xs btn-primary" href="{$relatedManifestation.url}">{$relatedManifestation.format}</a>
								{*<a href="#" class="btn btn-xs btn-primary" onclick="return VuFind.ResultsList.toggleRelatedManifestations('{$id|escapeCSS}_{$relatedManifestation.format|escapeCSS}');">*}
									{*{$relatedManifestation.format}*}
								{*</a>*}
								<a href="#" onclick="return VuFind.ResultsList.toggleRelatedManifestations('{$id|escapeCSS}_{$relatedManifestation.format|escapeCSS}');">
									<span class="manifestation-toggle-text label label-default" id='manifestation-toggle-text-{$id|escapeCSS}_{$relatedManifestation.format|escapeCSS}'>Show&nbsp;Edition</span>
								</a>
							{else}
								<a href="#" class="btn btn-xs btn-primary" onclick="return VuFind.ResultsList.toggleRelatedManifestations('{$id|escapeCSS}_{$relatedManifestation.format|escapeCSS}');">
									{$relatedManifestation.format}
								</a>
								<a href="#" onclick="return VuFind.ResultsList.toggleRelatedManifestations('{$id|escapeCSS}_{$relatedManifestation.format|escapeCSS}');">
									<span class="manifestation-toggle-text label label-info" id='manifestation-toggle-text-{$id|escapeCSS}_{$relatedManifestation.format|escapeCSS}'>Show&nbsp;Editions</span>
								</a>
							{/if}
						</div>
						<div class="col-tn-9 col-xs-8 col-md-5 col-lg-6">
							{include file='GroupedWork/statusIndicator.tpl' statusInformation=$relatedManifestation viewingIndividualRecord=0}

							{if $relatedManifestation.numRelatedRecords == 1}
								{include file='GroupedWork/copySummary.tpl' summary=$relatedManifestation.itemSummary totalCopies=$relatedManifestation.copies itemSummaryId=$id format=$relatedManifestation.format recordViewUrl=$relatedManifestation.url}
							{else}
								{include file='GroupedWork/copySummary.tpl' summary=$relatedManifestation.itemSummary totalCopies=$relatedManifestation.copies itemSummaryId=$id format=$relatedManifestation.format}
							{/if}

						</div>
						<div class="col-tn-9 col-tn-offset-3 col-xs-8 col-xs-offset-4 col-md-4 col-md-offset-0 col-lg-3 manifestation-actions">
							<div class="btn-toolbar">
								<div class="btn-group btn-group-vertical btn-block">
									{foreach from=$relatedManifestation.actions item=curAction}
										{if $curAction.url && strlen($curAction.url) > 0}
											<a href="{$curAction.url}" class="btn btn-xs btn-info" onclick="{if $curAction.requireLogin}return VuFind.Account.followLinkIfLoggedIn(this, '{$curAction.url}');{/if}" {if $curAction.alt}title="{$curAction.alt}"{/if}>{$curAction.title}</a>
										{else}
											<a href="#" class="btn btn-xs btn-info" onclick="{$curAction.onclick}" {if $curAction.alt}title="{$curAction.alt}"{/if}>{$curAction.title}</a>
										{/if}
									{/foreach}
								</div>
							</div>
						</div>
				  </div>
					<div class="row">
						<div class="col-sm-12 hidden" id="relatedRecordPopup_{$id|escapeCSS}_{$relatedManifestation.format|escapeCSS}">
							{include file="GroupedWork/relatedRecords.tpl" relatedRecords=$relatedManifestation.relatedRecords relatedManifestation=$relatedManifestation}
						</div>
					</div>
				</div>
			</div>
		{foreachelse}
			<div class="row related-manifestation">
				<div class="col-sm-12">
					Arlington Public Library does not own this item.
				</div>
			</div>
		{/foreach}
		{if $hasHiddenFormats}
			<div class="row related-manifestation" id="formatToggle_{$summId}">
				<div class="col-sm-12">
					<a href="#" onclick="$('.hiddenManifestation_{$summId}').show();$('#formatToggle_{$summId}').hide();return false;">View all Formats</a>
				</div>
			</div>
		{/if}
	</div>
{/strip}