{strip}
	{* Overall hold *}
	<div class="result row ilsHold_{$record->sourceId|escapeCSS}_{$record->cancelId|escapeCSS}">
		{if $section != 'available'}
		<div class="selectTitle col-xs-12 col-sm-1">
			<input type="checkbox" name="selected[{$record->userId}|{$record->sourceId}|{$record->cancelId}]" class="titleSelect" id="selected{$record->cancelId}">
		</div>
		{/if}
		{* Cover column *}
		{if $showCovers}
			<div class="{if $section == 'available'}col-xs-4 col-sm-3{else}col-xs-3 col-sm-2{/if}">
				<div class="{*col-xs-10 *}text-center">
					{if !empty($record->getCoverUrl())}
						{if !empty($record->getLinkUrl())}
							<a href="{$record->getLinkUrl()}" id="descriptionTrigger{$record->recordId|escape:"url"}" aria-hidden="true">
								<img src="{$record->getCoverUrl()}" class="listResultImage img-thumbnail img-responsive" alt="{translate text='Cover Image' inAttribute=true isPublicFacing=true}">
							</a>
						{else} {* Cover Image but no Record-View link *}
							<img src="{$record->getCoverUrl()}" class="listResultImage img-thumbnail img-responsive" alt="{translate text='Cover Image' inAttribute=true isPublicFacing=true}" aria-hidden="true">
						{/if}
					{/if}

				</div>
			</div>
		{/if}

		{* Details Column*}
		<div class="{if $showCovers}col-xs-8 col-sm-9{else}{if $section != 'available'}col-xs-11{else}col-xs-12{/if}{/if}">
			{* Title *}
			<div class="row">
				<div class="col-xs-12">
					<span class="result-index">{$resultIndex})</span>&nbsp;
					{if $record->getLinkUrl()}
						<a href="{$record->getLinkUrl()}" class="result-title notranslate">
							{if !$record->getTitle()|removeTrailingPunctuation}{translate text='Title not available'}{else}{$record->getTitle()|removeTrailingPunctuation|truncate:180:"..."|highlight}{/if}
						</a>
					{else}
						<span class="result-title notranslate">
							{if !$record->getTitle()|removeTrailingPunctuation}{translate text='Title not available'}{else}{$record->getTitle()|removeTrailingPunctuation|truncate:180:"..."|highlight}{/if}
						</span>
					{/if}
					{if !empty($record->title2)}
						<div class="searchResultSectionInfo">
							{$record->title2|removeTrailingPunctuation|truncate:180:"..."|highlight}
						</div>
					{/if}
				</div>
			</div>

			{* 2 column row to show information and then actions*}
			<div class="row">
				{* Information column author, format, etc *}
				<div class="resultDetails col-xs-12 col-md-8 col-lg-9">
					{if !empty($record->volume)}
						<div class="row">
							<div class="result-label col-tn-4">{translate text='Volume'}</div>
							<div class="col-tn-8 result-value">
								{$record->volume}
							</div>
						</div>
					{/if}

					{if !empty($record->getAuthor())}
						<div class="row">
							<div class="result-label col-tn-4">{translate text='Author'}</div>
							<div class="col-tn-8 result-value">
								{if is_array($record->getAuthor())}
									{foreach from=$record->getAuthor() item=author}
										<a href='/Author/Home?"author={$author|escape:"url"}"'>{$author|highlight}</a>
									{/foreach}
								{else}
									<a href='/Author/Home?author="{$record->getAuthor()|escape:"url"}"'>{$record->getAuthor()|highlight}</a>
								{/if}
							</div>
						</div>
					{/if}

					{if !empty($record->callNumber)}
						<div class="row">
							<div class="result-label col-tn-4">{translate text='Call Number'}</div>
							<div class="col-tn-8 result-value">
								{$record->callNumber}
							</div>
						</div>
					{/if}

					{if !empty($record->getFormats())}
						<div class="row">
							<div class="result-label col-tn-4">{translate text='Format'}</div>
							<div class="col-tn-8 result-value">
								{implode subject=$record->getFormats() glue=", " translate=true}
							</div>
						</div>
					{/if}

					{if $hasLinkedUsers}
					<div class="row">
						<div class="result-label col-tn-4">{translate text='On Hold For'}</div>
						<div class="col-tn-8 result-value">
							{$record->getUserName()}
						</div>
					</div>
					{/if}

					<div class="row">
						<div class="result-label col-tn-4">{translate text='Pickup Location'}</div>
						<div class="col-tn-8 result-value">
							{$record->pickupLocationName}
						</div>
					</div>

					{if $showPlacedColumn && $record->createDate}
						<div class="row">
							<div class="result-label col-tn-4">{translate text='Date Placed'}</div>
							<div class="col-tn-8 result-value">
								{$record->createDate|date_format:"%b %d, %Y"}
							</div>
						</div>
					{/if}

					{if $section == 'available'}
						{* Available Hold *}
						<div class="row">
							<div class="result-label col-tn-4">{translate text='Available'}</div>
							<div class="col-tn-8 result-value">
								{if $record->availableDate}
									{$record->availableDate|date_format:"%b %d, %Y at %l:%M %p"}
								{else}
									{if strcasecmp($record->status, 'Hold Being Shelved') === 0}
										<strong>{$record->status|translate}</strong>
									{else}
										{translate text=Now}
									{/if}
								{/if}
							</div>
						</div>

						{if $record->expirationDate}
							<div class="row">
								<div class="result-label col-tn-4">{translate text='Pickup By'}</div>
								<div class="col-tn-8 result-value">
									<strong>{$record->expirationDate|date_format:"%b %d, %Y"}</strong>
								</div>
							</div>
						{/if}
					{else}
						{* Unavailable hold *}
						<div class="row">
							<div class="result-label col-tn-4">{translate text='Status'}</div>
							<div class="col-tn-8 result-value">
								{if $record->frozen}
									<span class="frozenHold label label-warning">
								{/if}
								{$record->status|translate}
								{if $record->frozen && $showDateWhenSuspending && !empty($record->reactivateDate)} until {$record->reactivateDate|date_format:"%b %d, %Y"}</span>{/if}
							</div>
						</div>

						{if $showPosition && $record->position}
							<div class="row">
								<div class="result-label col-tn-4">{translate text='Position'}</div>
								<div class="col-tn-8 result-value">
									{if $record->holdQueueLength}
										{translate text="%1% of %2%" 1=$record->position 2=$record->holdQueueLength}
									{else}
                                        {$record->position}
									{/if}
								</div>
							</div>
						{/if}

						{if !empty($record->automaticCancellationDate) && $showHoldCancelDate}
							<div class="row">
								<div class="result-label col-tn-4">{translate text='Cancels on'}</div>
								<div class="col-tn-8 result-value">
									{$record->automaticCancellationDate|date_format:"%b %d, %Y"}
								</div>
							</div>
						{/if}
					{/if}
				</div>

				{* Actions for Title *}
				<div class="col-xs-9 col-sm-8 col-md-4 col-lg-3">
					<div class="btn-group btn-group-vertical btn-block">
						{if $section == 'available'}
							{if $record->cancelable}
								{* First step in cancelling a hold is now fetching confirmation message, with better labeled buttons. *}
								<button onclick="return AspenDiscovery.Account.confirmCancelHold('{$record->userId}', '{$record->sourceId}', '{$record->cancelId}');" class="btn btn-sm btn-warning">{translate text="Cancel Hold"}</button>
							{/if}
						{else}
							{if $record->cancelable}
								{* First step in cancelling a hold is now fetching confirmation message, with better labeled buttons. *}
								<button onclick="return AspenDiscovery.Account.confirmCancelHold('{$record->userId}', '{$record->sourceId}', '{$record->cancelId}');" class="btn btn-sm btn-warning">{translate text="Cancel Hold"}</button>
							{/if}
							{if $record->canFreeze}
								{if $record->frozen}
									<button onclick="return AspenDiscovery.Account.thawHold('{$record->userId}', '{$record->sourceId}', '{$record->cancelId}', this);" class="btn btn-sm btn-default">{translate text="Thaw Hold"}</button>
								{else}
									<button onclick="return AspenDiscovery.Account.freezeHold('{$record->userId}', '{$record->sourceId}', '{$record->cancelId}', {if $suspendRequiresReactivationDate}true{else}false{/if}, this);" class="btn btn-sm btn-default">{translate text="Freeze Hold"}</button>
								{/if}
							{/if}
							{if $record->locationUpdateable && $numPickupBranches > 1}
								<button onclick="return AspenDiscovery.Account.changeHoldPickupLocation('{$record->userId}', '{$record->sourceId}', '{$record->cancelId}', '{$record->pickupLocationId}');" class="btn btn-sm btn-default btn-wrap"">{translate text="Change Pickup Loc."}</button>
							{/if}
						{/if}
					</div>
					{if $showWhileYouWait}
						<div class="btn-group btn-group-vertical btn-block">
							{if !empty($record->getGroupedWorkId())}
								<button onclick="return AspenDiscovery.GroupedWork.getWhileYouWait('{$record->getGroupedWorkId()}');" class="btn btn-sm btn-default btn-wrap">{translate text="While You Wait"}</button>
							{/if}
						</div>
					{/if}
				</div>
			</div>
		</div>
	</div>
{/strip}