{strip}
	<div class="result row palace_projectHold_{$record->recordId|escapeCSS}_{$record->userId}">
		<div class="selectTitle col-xs-12 col-sm-1">
			<input type="checkbox" name="selected[{$record->userId}|{$record->sourceId|escapeCSS}|{$record->cancelId|escapeCSS}]" class="titleSelect" id="selected{$record->cancelId|escapeCSS}">
		</div>
		{* Cover column *}
		{if !empty($showCovers)}
		<div class="col-xs-3 col-sm-2">
			<div class="text-center">
				{if $record->getCoverUrl()}
					{if $record->recordId && $record->getLinkUrl()}
						<a href="{$record->getLinkUrl()}" id="descriptionTrigger{$record->recordId||escapeCSS}" aria-hidden="true">
							<img src="{$record->getCoverUrl()}" class="listResultImage img-thumbnail img-responsive {$coverStyle}" alt="{translate text='Cover Image' inAttribute=true isPublicFacing=true}">
						</a>
					{else}  Cover Image but no Record-View link
						<img src="{$record->getCoverUrl()}" class="listResultImage img-thumbnail img-responsive {$coverStyle}" alt="{translate text='Cover Image' inAttribute=true isPublicFacing=true}" aria-hidden="true">
					{/if}
				{/if}
			</div>
		</div>

		{/if}
		{* Details Column*}
		<div class="{if !empty($showCovers)}col-xs-8 col-sm-9{else}col-xs-11{/if}">
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
					{if $record->getSubtitle()}
						<div class="searchResultSectionInfo">
							{$record->getSubtitle()|removeTrailingPunctuation}
						</div>
					{/if}
				</div>
			</div>

			<div class="row">
				<div class="resultDetails col-xs-12 col-md-8 col-lg-9">
					{if $record->getAuthor()}
						<div class="row">
							<div class="result-label col-tn-4">{translate text='Author' isPublicFacing=true}</div>
							<div class="col-tn-8 result-value">
								{if is_array($record->getAuthor())}
									{foreach from=$record->getAuthor() item=author}
										<a href='/Author/Home?author="{$author|escape:"url"}"'>{$author|highlight}</a>
									{/foreach}
								{else}
									<a href='/Author/Home?author="{$record->getAuthor()|escape:"url"}"'>{$record->getAuthor()|highlight}</a>
								{/if}
							</div>
						</div>
					{/if}

					<div class="row">
						<div class="result-label col-tn-4">{translate text='Source' isPublicFacing=true}</div>
						<div class="col-tn-8 result-value">
							{translate text="Palace Project" isPublicFacing=true}
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

					{if $record->createDate}
						<div class="row">
							<div class="result-label col-tn-4">{translate text='Date Placed' isPublicFacing=true}</div>
							<div class="col-tn-8 result-value">
								{$record->createDate|date_format:"%b %d, %Y"}
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

					{if $section == 'available'}
						<div class="row">
							<div class="result-label col-tn-4">{translate text='Expires' isPublicFacing=true}</div>
							<div class="col-tn-8 result-value">
								<strong>{$record->expirationDate|date_format:"%b %d, %Y at %l:%M %p"}</strong>
							</div>
						</div>
					{/if}
				</div>

				{* Actions for Title *}
				<div class="col-xs-9 col-sm-8 col-md-4 col-lg-3">
					<div class="btn-group btn-group-vertical btn-block">
						{if $section == 'available'}
							<button onclick="return AspenDiscovery.PalaceProject.doCheckOut('{$record->userId}', '{$record->recordId}', '{$record->recordId|escapeCSS}');" class="btn btn-sm btn-action">{translate text="Checkout" isPublicFacing=true}</button>
						{/if}
						<button onclick="return AspenDiscovery.PalaceProject.cancelHold('{$record->userId}', '{$record->recordId}', '{$record->recordId|escapeCSS}');" class="btn btn-sm btn-warning">{translate text="Cancel Hold" isPublicFacing=true}</button>
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
