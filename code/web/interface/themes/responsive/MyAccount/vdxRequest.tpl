{strip}
{* Overall hold *}
<div class="result row" id="vdxHold_{$record->sourceId|escapeCSS}_{$record->cancelId|escapeCSS}">
	<div class="selectTitle col-xs-12 col-sm-1">
		<input type="checkbox" name="selected[{$record->userId}|{$record->sourceId}|{$record->cancelId}]" class="titleSelect" id="selected{$record->cancelId}">
	</div>
	{* Cover column *}
	{if !empty($showCovers)}
		<div class="{if $section == 'available'}col-xs-4 col-sm-3{else}col-xs-3 col-sm-2{/if}">
			<div class="{*col-xs-10 *}text-center">
				{if !empty($record->getCoverUrl())}
					{if !empty($record->getLinkUrl())}
						<a href="{$record->getLinkUrl()}" id="descriptionTrigger{$record->recordId|escape:"url"}" aria-hidden="true">
							<img src="{$record->getCoverUrl()}" class="listResultImage img-thumbnail img-responsive {$coverStyle}" alt="{translate text='Cover Image' inAttribute=true isPublicFacing=true}">
						</a>
					{else} {* Cover Image but no Record-View link *}
						<img src="{$record->getCoverUrl()}" class="listResultImage img-thumbnail img-responsive {$coverStyle}" alt="{translate text='Cover Image' inAttribute=true isPublicFacing=true}" aria-hidden="true">
					{/if}
				{/if}

			</div>
		</div>
	{/if}

	{* Details Column*}
	<div class="{if !empty($showCovers)}col-xs-8 col-sm-9{else}{if $section != 'available'}col-xs-11{else}col-xs-12{/if}{/if}">
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
			</div>
		</div>

		{* 2 column row to show information and then actions*}
		<div class="row">
			{* Information column author, format, etc *}
			<div class="resultDetails col-xs-12 col-md-8 col-lg-9">
				{if !empty($record->getAuthor())}
					<div class="row">
						<div class="result-label col-tn-4">{translate text='Author' isPublicFacing=true}</div>
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

				{if !empty($hasLinkedUsers)}
				<div class="row">
					<div class="result-label col-tn-4">{translate text='On Hold For' isPublicFacing=true}</div>
					<div class="col-tn-8 result-value">
						{$record->getUserName()|escape}
					</div>
				</div>
				{/if}

				<div class="row">
					<div class="result-label col-tn-4">{translate text='Pickup Location' isPublicFacing=true}</div>
					<div class="col-tn-8 result-value">
						{$record->pickupLocationName|escape}
					</div>
				</div>

				{if !empty($showPlacedColumn) && $record->createDate}
					<div class="row">
						<div class="result-label col-tn-4">{translate text='Date Placed' isPublicFacing=true}</div>
						<div class="col-tn-8 result-value">
							{$record->createDate|date_format:"%b %d, %Y"}
						</div>
					</div>
				{/if}

				{* Unavailable hold *}
				<div class="row">
					<div class="result-label col-tn-4">{translate text='Status' isPublicFacing=true}</div>
					<div class="col-tn-8 result-value">
						{if $record->frozen}
							<span class="frozenHold label label-warning">
						{/if}
						{translate text=$record->status isPublicFacing=true}
					</div>
				</div>
			</div>

			{* Actions for Title *}
			<div class="col-xs-9 col-sm-8 col-md-4 col-lg-3">
				<div class="btn-group btn-group-vertical btn-block">
					{if $record->cancelable}
						{* First step in cancelling a hold is now fetching confirmation message, with better labeled buttons. *}
						<button onclick="return AspenDiscovery.Account.cancelVdxRequest('{$record->userId}', '{$record->sourceId}', '{$record->cancelId}');" class="btn btn-sm btn-warning">{translate text="Cancel Request" isPublicFacing=true}</button>
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
