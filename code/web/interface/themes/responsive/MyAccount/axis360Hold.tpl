{strip}
	<div class="result row axis360Hold_{$record->recordId}_{$record->userId}">
		<div class="selectTitle col-xs-12 col-sm-1">
			<input type="checkbox" name="selected[{$record->userId}|{$record->sourceId}|{$record->cancelId}]" class="titleSelect" id="selected{$record->cancelId}">
		</div>
		{* Cover column *}
		{if $showCovers}
		<div class="col-xs-3 col-sm-2">
			{*<div class="row">*}
				<div class="{*col-xs-10 *}text-center">
					{if $record->getCoverUrl()}
						{if $record->recordId && $record->getLinkUrl()}
							<a href="{$record->getLinkUrl()}" id="descriptionTrigger{$record->recordId|escape:"url"}" aria-hidden="true">
								<img src="{$record->getCoverUrl()}" class="listResultImage img-thumbnail img-responsive" alt="{translate text='Cover Image' inAttribute=true isPublicFacing=true}">
							</a>
						{else} {* Cover Image but no Record-View link *}
							<img src="{$record->getCoverUrl()}" class="listResultImage img-thumbnail img-responsive" alt="{translate text='Cover Image' inAttribute=true isPublicFacing=true}" aria-hidden="true">
						{/if}
					{/if}
				</div>
			{*</div>*}
		</div>

		{/if}
		{* Details Column*}
		<div class="{if $showCovers}col-xs-8 col-sm-9{else}col-xs-11{/if}">
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
							<div class="result-label col-tn-4">{translate text='Author'}</div>
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
						<div class="result-label col-tn-4">{translate text='Source'}</div>
						<div class="col-tn-8 result-value">
							{translate text="Axis 360"}
						</div>
					</div>

					{if $record->getFormats()}
						<div class="row">
							<div class="result-label col-tn-4">{translate text='Format'}</div>
							<div class="col-tn-8 result-value">
								{implode subject=$record->getFormats() glue=", "}
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

					{if $section == 'available'}
					{* Available Hold *}
						<div class="row">
							<div class="result-label col-tn-4">{translate text='Expires'}</div>
							<div class="col-tn-8 result-value">
								<strong>{$record->expirationDateexpire|date_format:"%b %d, %Y at %l:%M %p"}</strong>
							</div>
						</div>
					{else}
						{* Unavailable hold *}
						<div class="row">
							{if $record->frozen}
								<div class="result-label col-tn-4">{translate text='Status'}</div>
								<div class="col-tn-8 result-value">
									<span class="frozenHold label label-warning">{$record->status|translate}</span>
								</div>
							{else}
								<div class="result-label col-tn-4">{translate text='Position'}</div>
								<div class="col-tn-8 result-value">
									{translate text="%1% out of %2%" 1=$record->position 2=$record->holdQueueLength}
								</div>
							{/if}
						</div>
					{/if}
				</div>

				{* Actions for Title *}
				<div class="col-xs-9 col-sm-8 col-md-4 col-lg-3">
					<div class="btn-group btn-group-vertical btn-block">
						{if $section == 'available'}
							<button onclick="return AspenDiscovery.Axis360.doCheckOut('{$record->userId}', '{$record->recordId}');" class="btn btn-sm btn-action">{translate text="Checkout"}</button>
						{/if}
						<button onclick="return AspenDiscovery.Axis360.cancelHold('{$record->userId}', '{$record->recordId}');" class="btn btn-sm btn-warning">{translate text="Cancel Hold"}</button>
						{if $record->canFreeze}
							{if $record->frozen}
								<button onclick="return AspenDiscovery.Axis360.thawHold('{$record->userId}', '{$record->recordId}', this);" class="btn btn-sm btn-default">{translate text="Thaw Hold"}</button>
							{elseif $record->canFreeze}
								<button onclick="return AspenDiscovery.Axis360.freezeHold('{$record->userId}', '{$record->recordId}');" class="btn btn-sm btn-default">{translate text="Freeze Hold"}</button>
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