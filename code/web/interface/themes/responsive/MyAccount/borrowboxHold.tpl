{strip}
	<div class="result row borrowboxHold_{$record->sourceId}_{$record->userId}" id="borrowboxHold_{$record->sourceId}">
		<div class="selectTitle col-xs-12 col-sm-1">
			&nbsp;
		</div>
		{if !empty($showCovers)}
			<div class="col-xs-3 col-sm-2">
				<div class="text-center">
					{if $record->getCoverUrl()}
						{if $record->sourceId && $record->getLinkUrl()}
							<a href="{$record->getLinkUrl()}" id="descriptionTrigger{$record->sourceId|escape:"url"}" aria-hidden="true">
								<img src="{$record->getCoverUrl()}" class="listResultImage img-thumbnail{if $useOriginalCoverUrls} use-original-covers{/if} img-responsive {$coverStyle}" alt="{translate text='Cover Image' inAttribute=true isPublicFacing=true}">
							</a>
						{else}
							<img src="{$record->getCoverUrl()}" class="listResultImage img-thumbnail{if $useOriginalCoverUrls} use-original-covers{/if} img-responsive {$coverStyle}" alt="{translate text='Cover Image' inAttribute=true isPublicFacing=true}" aria-hidden="true">
						{/if}
					{/if}
				</div>
			</div>
		{/if}
		<div class="{if !empty($showCovers)}col-xs-8 col-sm-9{else}col-xs-11{/if}">
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
					{if $record->getAuthor()}
						<div class="row">
							<div class="result-label col-tn-4">{translate text='Author' isPublicFacing=true}</div>
							<div class="col-tn-8 result-value">
								<a href='/Author/Home?author="{$record->getAuthor()|escape:"url"}"'>{$record->getAuthor()|highlight}</a>
							</div>
						</div>
					{/if}

					<div class="row">
						<div class="result-label col-tn-4">{translate text='Source' isPublicFacing=true}</div>
						<div class="col-tn-8 result-value">
							{translate text="BorrowBox" isPublicFacing=true}
						</div>
					</div>

					{if $record->getFormats()}
						<div class="row">
							<div class="result-label col-tn-4">{translate text='Format' isPublicFacing=true}</div>
							<div class="col-tn-8 result-value">
								{implode subject=$record->getFormats() glue=", " translate=true isPublicFacing=true}
							</div>
						</div>
					{/if}

					{if !empty($hasLinkedUsers)}
						<div class="row">
							<div class="result-label col-tn-4">{translate text='On Hold For' isPublicFacing=true}</div>
							<div class="col-tn-8 result-value">
								{$record->getUserName()|escape}
							</div>
						</div>
					{/if}

					{if $record->collectionName}
						<div class="row">
							<div class="result-label col-tn-4">{translate text='Collection' isPublicFacing=true}</div>
							<div class="col-tn-8 result-value">
								{$record->collectionName}
							</div>
						</div>
					{/if}

					{if $record->createDate}
						<div class="row">
							<div class="result-label col-tn-4">{translate text='Date Placed' isPublicFacing=true}</div>
							<div class="col-tn-8 result-value">
								{$record->createDate|format_date_locale}
							</div>
						</div>
					{/if}
				</div>

				<div class="col-xs-9 col-sm-8 col-md-4 col-lg-3">
					<div class="btn-group btn-group-vertical btn-block">
						{if $record->cancelable}
							<button onclick="return AspenDiscovery.BorrowBox.cancelHold('{$record->userId}', '{$record->sourceId}');" class="btn btn-sm btn-warning btn-wrap">{translate text="Cancel Hold" isPublicFacing=true}</button>
						{/if}
					</div>
					{if !empty($showWhileYouWait)}
						<div class="btn-group btn-group-vertical btn-block">
							{if !empty($record->getGroupedWorkId())}
								<button onclick="return AspenDiscovery.GroupedWork.getWhileYouWait('{$record->getGroupedWorkId()}', '{$record->getPrimaryFormat()}');" class="btn btn-sm btn-default btn-wrap">{translate text="While You Wait" isPublicFacing=true}</button>
							{/if}
						</div>
					{/if}
				</div>
			</div>
		</div>
	</div>
{/strip}
