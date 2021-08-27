{strip}
	<div class="result row" id="cloudLibraryHold_{$record->sourceId}">
		<div class="selectTitle col-xs-12 col-sm-1">
			<input type="checkbox" name="selected[{$record->userId}|{$record->sourceId}|{$record->cancelId}]" class="titleSelect" id="selected{$record->cancelId}">
		</div>
		{* Cover column *}
		{if $showCovers}
		<div class="col-xs-3 col-sm-2">
			{*<div class="row">*}
				<div class="{*col-xs-10 *}text-center">
					{if $record->getCoverUrl()}
						{if $record->sourceId && $record->getLinkUrl()}
							<a href="{$record->getLinkUrl()}" id="descriptionTrigger{$record->sourceId|escape:"url"}" aria-hidden="true">
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
				</div>
			</div>

			<div class="row">
				<div class="resultDetails col-xs-12 col-md-8 col-lg-9">
					{if $record->getAuthor()}
						<div class="row">
							<div class="result-label col-tn-4">{translate text='Author'}</div>
							<div class="col-tn-8 result-value">
								<a href='/Author/Home?author="{$record->getAuthor()|escape:"url"}"'>{$record->getAuthor()|highlight}</a>
							</div>
						</div>
					{/if}

					<div class="row">
						<div class="result-label col-tn-4">{translate text='Source'}</div>
						<div class="col-tn-8 result-value">
							{translate text="CloudLibrary"}
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

					{if !empty($record->position)}
						<div class="row">
							<div class="result-label col-tn-4">{translate text='Position'}</div>
							<div class="col-tn-8 result-value">
								{$record->position}
							</div>
						</div>
					{/if}
				</div>

				{* Actions for Title *}
				<div class="col-xs-9 col-sm-8 col-md-4 col-lg-3">
					<div class="btn-group btn-group-vertical btn-block">
						{if $section == 'available'}
							<button onclick="return AspenDiscovery.CloudLibrary.checkOutTitle('{$record->userId}', '{$record->sourceId}');" class="btn btn-sm btn-action">{translate text="Checkout"}</button>
						{/if}
						<button onclick="return AspenDiscovery.CloudLibrary.cancelHold('{$record->userId}', '{$record->sourceId}');" class="btn btn-sm btn-warning">Cancel Hold</button>
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