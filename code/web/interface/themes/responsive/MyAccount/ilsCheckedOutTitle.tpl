{strip}
	<div id="record{$record->source}_{$record->sourceId|escape}" class="result row{if $record->isOverdue()} bg-overdue{/if}">

		{* Cover Column *}
		{if $showCovers}
			{*<div class="col-xs-4">*}
			<div class="col-xs-3 col-sm-4 col-md-3">
				<div class="row">
					<div class="selectTitle col-xs-12 col-sm-1">
						{if !isset($record->canRenew) || $record->canRenew == true}
						<input type="checkbox" name="selected[{$record->userId}|{$record->recordId}|{$record->renewIndicator}]" class="titleSelect" id="selected{$record->itemId}">
						{/if}
					</div>
					<div class="{*coverColumn *}text-center col-xs-12 col-sm-10">
						{if $disableCoverArt != 1}{*TODO: should become part of $showCovers *}
							{if $record->getCoverUrl()}
								{if $record->recordId && !empty($record->getLinkUrl())}
									<a href="{$record->getLinkUrl()}" id="descriptionTrigger{$record->recordId|escape:"url"}" aria-hidden="true">
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
			<div class="col-xs-1">
				{if !isset($record->canRenew) || $record->canRenew == true}
					<input type="checkbox" name="selected[{$record->userId}|{$record->recordId}|{$record->renewIndicator}]" class="titleSelect" id="selected{$record->itemId}">
				{/if}
			</div>
		{/if}

		{* Title Details Column *}
		<div class="{if $showCovers}col-xs-9 col-sm-8 col-md-9{else}col-xs-11{/if}">
			{* Title *}
			<div class="row">
				<div class="col-xs-12">
					<span class="result-index">{$resultIndex})</span>&nbsp;
					{if $record->getLinkUrl()}
						<a href="{$record->getLinkUrl()}" class="result-title notranslate">
							{if !$record->getTitle()|removeTrailingPunctuation} {translate text='Title not available' isPublicFacing=true}{else}{$record->getTitle()|removeTrailingPunctuation|truncate:180:"..."|highlight}{/if}
						</a>
					{else}
						<span class="result-title notranslate">
							{if !$record->getTitle()|removeTrailingPunctuation} {translate text='Title not available' isPublicFacing=true}{else}{$record->getTitle()|removeTrailingPunctuation|truncate:180:"..."|highlight}{/if}
						</span>
					{/if}
					{if !empty($record->title2)}
						<div class="searchResultSectionInfo">
							{$record->title2|removeTrailingPunctuation|truncate:180:"..."|highlight}
						</div>
					{/if}
				</div>
			</div>

			<div class="row">
				<div class="resultDetails col-xs-12 col-md-8 col-lg-9">
					{if !empty($record->volume)}
						<div class="row">
							<div class="result-label col-sm-12 col-md-5">{translate text='Volume' isPublicFacing=true}</div>
							<div class="col-sm-12 col-md-7 result-value">{$record->volume|escape}</div>
						</div>
					{/if}

					{if $record->getAuthor()}
						<div class="row">
							<div class="result-label col-sm-12 col-md-5">{translate text='Author' isPublicFacing=true}</div>
							<div class="col-sm-12 col-md-7 result-value">
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

                    {if !empty($record->callNumber)}
						<div class="row">
							<div class="result-label col-sm-12 col-md-5">{translate text='Call Number' isPublicFacing=true}</div>
							<div class="col-sm-12 col-md-7 result-value">
                                {$record->callNumber}
							</div>
						</div>
                    {/if}

					{if $showOut}
						<div class="row">
							<div class="result-label col-sm-12 col-md-5">{translate text='Checked Out' isPublicFacing=true}</div>
							<div class="col-sm-12 col-md-7 result-value">{$record->checkoutDate|date_format}</div>
						</div>
					{/if}

					<div class="row">
						<div class="result-label col-sm-12 col-md-5">{translate text='Format' isPublicFacing=true}</div>
						<div class="col-sm-12 col-md-7 result-value">{implode subject=$record->getFormats()}</div>
					</div>

					{if $displayItemBarcode == 1}
						{if !empty($record->barcode)}
							<div class="row">
								<div class="result-label col-sm-12 col-md-5">{translate text='Barcode' isPublicFacing=true}</div>
								<div class="col-sm-12 col-md-7 result-value">
									{$record->barcode}
								</div>
							</div>
						{/if}
					{/if}

					{if $showRatings && $record->getGroupedWorkId() && $record->getRatingData()}
						<div class="row">
							<div class="result-label col-sm-12 col-md-5">{translate text='Rating' isPublicFacing=true}</div>
							<div class="col-sm-12 col-md-7 result-value">
								{include file="GroupedWork/title-rating.tpl" id=$record->getGroupedWorkId() summId=$record->getGroupedWorkId() ratingData=$record->getRatingData() showNotInterested=false}
							</div>
						</div>
					{/if}

					{if $hasLinkedUsers}
						<div class="row">
							<div class="result-label col-sm-12 col-md-5">{translate text='Checked Out To' isPublicFacing=true}</div>
							<div class="col-sm-12 col-md-7 result-value">
								{$record->getUserName()}
							</div>
						</div>
					{/if}

					{if !empty($record->returnClaim)}
						<div class="row">
							<div class="result-value col-tn-8 col-lg-9 col-tn-offset-4 col-lg-offset-3 return_claim">
								{$record->returnClaim}
							</div>
						</div>
					{else}
						<div class="row">
							<div class="result-label col-sm-12 col-md-5">{translate text='Due' isPublicFacing=true}</div>
							<div class="col-sm-12 col-md-7 result-value">
								{$record->dueDate|date_format}
								{if $record->isOverdue()}
									&nbsp;<span class="label label-danger">{translate text="OVERDUE" isPublicFacing=true}</span>
								{elseif $record->getDaysUntilDue() == 0}
									&nbsp;<span class="label label-warning">({translate text="Due today" isPublicFacing=true})</span>
								{elseif $record->getDaysUntilDue() == 1}
									&nbsp;<span class="label label-warning">({translate text="Due tomorrow" isPublicFacing=true})</span>
								{elseif $record->getDaysUntilDue() <= 7}
									&nbsp;<span class="label label-warning">({translate text="Due in %1% days" 1=$record->getDaysUntilDue() isPublicFacing=true})</span>
								{/if}
							</div>
						</div>
					{/if}

					{if !empty($record->fine)}
						<div class="row">
							<div class="result-label col-sm-12 col-md-5">{translate text='Fine' isPublicFacing=true}</div>
							<div class="col-sm-12 col-md-7 result-value">
								{if $record->fine}
									<span class="overdueLabel"> {translate text="%1% (up to now)" 1=$record->fine isPublicFacing=true} </span>
								{/if}
							</div>
						</div>
					{/if}

					{if empty($record->returnClaim) && ($showRenewed && $record->renewCount || $defaultSortOption == 'renewed')}{* Show times renewed when sorting by that value (even if 0)*}
						<div class="row">
							<div class="result-label col-sm-12 col-md-5">{translate text='Renewed' isPublicFacing=true}</div>
							<div class="col-sm-12 col-md-7 result-value">
								{if empty($record->maxRenewals)}
									{translate text="%1% times" 1=$record->renewCount isPublicFacing=true}
								{else}
                                    {translate text="%1% of %2% times" 1=$record->renewCount 2=$record->maxRenewals isPublicFacing=true}
								{/if}
							</div>
						</div>
					{/if}

					{if empty($record->returnClaim) && ($showRenewalsRemaining)}{* Show times renewed when sorting by that value (even if 0)*}
						<div class="row">
							<div class="result-label col-sm-12 col-md-5">{translate text='Renewals Remaining' isPublicFacing=true}</div>
							<div class="col-sm-12 col-md-7 result-value">
								{if empty($record->maxRenewals)}
									0
								{else}
									{$record->maxRenewals}
								{/if}
							</div>
						</div>
					{/if}

					{if $showWaitList}
						<div class="row">
							<div class="result-label col-sm-12 col-md-5">{translate text='Wait List' isPublicFacing=true}</div>
							<div class="col-sm-12 col-md-7 result-value">
								{* Wait List goes here *}
								{$record->holdQueueLength}
							</div>
						</div>
					{/if}
				</div>

				{* Actions for Title *}
				{*<div class="{if $showCovers}col-xs-9 col-sm-8 col-md-4 col-lg-3{else}col-xs-11{/if}">*}
				<div class="col-sm-12 col-md-4 col-lg-3">
					<div class="btn-group btn-group-vertical btn-block">
						{if empty($record->returnClaim)}
							{if !isset($record->canRenew) || $record->canRenew == true}
								<a href="#" onclick="return AspenDiscovery.Account.renewTitle('{$record->userId}', '{$record->recordId}', '{$record->renewIndicator}');" class="btn btn-sm btn-primary">{translate text='Renew' isPublicFacing=true}</a>
							{elseif isset($record->autoRenew) && $record->autoRenew == true}
								{if !empty($record->autoRenewError)}
									{$record->autoRenewError}
								{else}
									{translate text='If eligible, this item will renew on<br/>%1%' 1=$record->getFormattedRenewalDate() isPublicFacing=true}
								{/if}
							{else}
								{if !empty($record->renewError)}
									{$record->renewError}
								{else}
									{translate text="Sorry, this title cannot be renewed" isPublicFacing=true}
								{/if}
							{/if}
						{/if}
					</div>
					{if $showWhileYouWait}
						<div class="btn-group btn-group-vertical btn-block">
							{if !empty($record->getGroupedWorkId())}
								<button onclick="return AspenDiscovery.GroupedWork.getYouMightAlsoLike('{$record->getGroupedWorkId()}');" class="btn btn-sm btn-default btn-wrap">{translate text="You Might Also Like" isPublicFacing=true}</button>
							{/if}
						</div>
					{/if}
				</div>
			</div>
		</div>
	</div>
{/strip}