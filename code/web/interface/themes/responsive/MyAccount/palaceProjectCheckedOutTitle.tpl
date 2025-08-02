{strip}
	<div class="result row palace_project_checkout_{$record->recordId|escapeCSS}_{$record->userId}">
		{* Cover Column *}
		{if !empty($showCovers)}
			{*<div class="col-xs-4">*}
			<div class="col-xs-3 col-sm-4 col-md-3 checkedOut-covers-column">
				<div class="row">
					<div class="{*coverColumn *}text-center col-xs-12 col-sm-10">
						{if $disableCoverArt != 1}{*TODO: should become part of $showCovers *}
							{if $record->getCoverUrl()}
								{if $record->recordId && $record->getLinkUrl()}
									<a href="{$record->getLinkUrl()}" id="descriptionTrigger{$record->recordId|escapeCSS}" aria-hidden="true">
										<img src="{$record->getCoverUrl()}" class="listResultImage img-thumbnail img-responsive {$coverStyle}" alt="{translate text='Cover Image' inAttribute=true isPublicFacing=true}">
									</a>
								{else} {* Cover Image but no Record-View link *}
									<img src="{$record->getCoverUrl()}" class="listResultImage img-thumbnail img-responsive {$coverStyle}" alt="{translate text='Cover Image' inAttribute=true isPublicFacing=true}" aria-hidden="true">
								{/if}
							{/if}
						{/if}
					</div>
				</div>
			</div>
		{else}
			<div class="col-xs-1"></div>
		{/if}
		<div class="{if !empty($showCovers)}col-xs-9 col-sm-8 col-md-9{else}col-xs-11{/if}">
			{* Title *}
			<div class="row">
				<div class="col-xs-12">
					<span class="result-index">{$resultIndex})</span>&nbsp;
					{if $record->getLinkUrl()}
						<a href="{$record->getLinkUrl()}" class="result-title notranslate">
							{if !$record->getTitle()|removeTrailingPunctuation}{translate text='Title not available' isPublicFacing=true}{else}{$record->getTitle()|removeTrailingPunctuation|truncate:180:"..."|highlight}{/if}
						</a>
					{else}
						<span class="result-title notranslate">
							{if !$record->getTitle()|removeTrailingPunctuation}{translate text='Title not available' isPublicFacing=true}{else}{$record->getTitle()|removeTrailingPunctuation|truncate:180:"..."|highlight}{/if}
						</span>
					{/if}
				</div>
			</div>
			<div class="row">
				<div class="resultDetails col-xs-12 col-md-8 col-lg-9">
					{if strlen($record->getAuthor()) > 0}
						<div class="row">
							<div class="result-label col-sm-12 col-md-5">{translate text='Author' isPublicFacing=true}</div>
							<div class="col-sm-12 col-md-7 result-value">{$record->getAuthor()}</div>
						</div>
					{/if}

					{if !empty($record->checkoutDate)}
						<div class="row">
							<div class="result-label col-sm-12 col-md-5">{translate text="checked_out_user_account" defaultText="Checked Out" isPublicFacing=true}</div>
							<div class="col-sm-12 col-md-7 result-value">{$record->checkoutDate|date_format}</div>
						</div>
					{/if}


					<div class="row">
						<div class="result-label col-sm-12 col-md-5">{translate text='Format' isPublicFacing=true}</div>
						<div class="col-sm-12 col-md-7 result-value">{implode subject=$record->getFormats() translate=true isPublicFacing=true} - {translate text="Palace Project" isPublicFacing=true}</div>
					</div>

					{if !empty($showRatings) && $record->getGroupedWorkId() && $record->getRatingData()}
						<div class="row">
							<div class="result-label col-sm-12 col-md-5">{translate text='Rating' isPublicFacing=true}&nbsp;</div>
							<div class="col-sm-12 col-md-7 result-value">
								{include file="GroupedWork/title-rating.tpl" id=$record->getGroupedWorkId() summId=$record->getGroupedWorkId() ratingData=$record->getRatingData() showNotInterested=false}
							</div>
						</div>
					{/if}

					{if !empty($hasLinkedUsers)}
						<div class="row">
							<div class="result-label col-sm-12 col-md-5">{translate text='Checked Out To' isPublicFacing=true}</div>
							<div class="col-sm-12 col-md-7 result-value">
								{$record->getUserName()|escape}
							</div>
						</div>
					{/if}

					{if !empty($record->dueDate)}
						<div class="row">
							<div class="result-label col-sm-12 col-md-5">{translate text='Expires' isPublicFacing=true}</div>
							<div class="col-sm-12 col-md-7 result-value">{$record->dueDate|date_format}</div>
						</div>
					{/if}
				</div>

				{* Actions for Title *}
				<div class="col-sm-12 col-md-4 col-lg-3">
					<div class="btn-group btn-group-vertical btn-block">
						<a onclick="AspenDiscovery.PalaceProject.showUsageInstructions();" target="_blank" class="btn btn-sm btn-action btn-wrap">{translate text='Access In Palace Project' isPublicFacing=true}</a>
						{if $record->canReturnEarly}
							<a href="#" onclick="return AspenDiscovery.PalaceProject.returnCheckout('{$record->userId}', '{$record->recordId}', '{$record->recordId|escapeCSS}');" class="btn btn-sm btn-warning">{translate text='Return Now' isPublicFacing=true}</a>
						{/if}
					</div>
					{if !empty($showWhileYouWait)}
						<div class="btn-group btn-group-vertical btn-block">
							{if !empty($record->getGroupedWorkId())}
								<button onclick="return AspenDiscovery.GroupedWork.getYouMightAlsoLike('{$record->getGroupedWorkId()}', '{$record->getPrimaryFormat()}');" class="btn btn-sm btn-default btn-wrap">{translate text="You Might Also Like" isPublicFacing=true}</button>
							{/if}
						</div>
					{/if}
				</div>
			</div>
		</div>
	</div>
{/strip}
