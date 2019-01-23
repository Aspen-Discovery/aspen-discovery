{strip}
	<div id="record{$record.source}_{$record.id|escape}" class="result row{if $record.overdue} bg-overdue{/if}">

		{* Cover Column *}
		{if $showCovers}
		{*<div class="col-xs-4">*}
		<div class="col-xs-3 col-sm-4 col-md-3">
			<div class="row">
				<div class="selectTitle col-xs-12 col-sm-1">
					{if !isset($record.canrenew) || $record.canrenew == true}
					<input type="checkbox" name="selected[{$record.userId}|{$record.recordId}|{$record.renewIndicator}]" class="titleSelect" id="selected{$record.itemid}">
					{/if}
				</div>
				<div class="{*coverColumn *}text-center col-xs-12 col-sm-10">
					{if $disableCoverArt != 1}{*TODO: should become part of $showCovers *}
						{if $record.coverUrl}
							{if $record.recordId && $record.linkUrl}
								<a href="{$record.linkUrl}" id="descriptionTrigger{$record.recordId|escape:"url"}">
									<img src="{$record.coverUrl}" class="listResultImage img-thumbnail img-responsive" alt="{translate text='Cover Image'}">
								</a>
							{else} {* Cover Image but no Record-View link *}
								<img src="{$record.coverUrl}" class="listResultImage img-thumbnail img-responsive" alt="{translate text='Cover Image'}">
							{/if}
						{/if}
					{/if}
				</div>
			</div>
		</div>
		{else}
			<div class="col-xs-1">
				{if !isset($record.canrenew) || $record.canrenew == true}
					<input type="checkbox" name="selected[{$record.userId}|{$record.recordId}|{$record.renewIndicator}]" class="titleSelect" id="selected{$record.itemid}">
				{/if}
			</div>
		{/if}

		{* Title Details Column *}
		<div class="{if $showCovers}col-xs-9 col-sm-8 col-md-9{else}col-xs-11{/if}">
			{* Title *}
			<div class="row">
				<div class="col-xs-12">
					<span class="result-index">{$resultIndex})</span>&nbsp;
					{if $record.link}
						<a href="{$record.link}" class="result-title notranslate">
							{if !$record.title|removeTrailingPunctuation}{translate text='Title not available'}{else}{$record.title|removeTrailingPunctuation|truncate:180:"..."|highlight}{/if}
						</a>
					{else}
						<span class="result-title notranslate">
							{if !$record.title|removeTrailingPunctuation}{translate text='Title not available'}{else}{$record.title|removeTrailingPunctuation|truncate:180:"..."|highlight}{/if}
						</span>
					{/if}
					{if $record.title2}
						<div class="searchResultSectionInfo">
							{$record.title2|removeTrailingPunctuation|truncate:180:"..."|highlight}
						</div>
					{/if}
				</div>
			</div>

			<div class="row">
				<div class="resultDetails col-xs-12 col-md-9">
					{if $record.volume}
						<div class="row">
							<div class="result-label col-tn-4 col-lg-3">{translate text='Volume'}</div>
							<div class="result-value col-tn-8 col-lg-9">{$record.volume|escape}</div>
						</div>
					{/if}

					{if $record.author}
						<div class="row">
							<div class="result-label col-tn-4 col-lg-3">{translate text='Author'}</div>
							<div class="result-value col-tn-8 col-lg-9">
								{if is_array($record.author)}
									{foreach from=$record.author item=author}
										<a href='{$path}/Author/Home?author="{$author|escape:"url"}"'>{$author|highlight}</a>
									{/foreach}
								{else}
									<a href='{$path}/Author/Home?author="{$record.author|escape:"url"}"'>{$record.author|highlight}</a>
								{/if}
							</div>
						</div>
					{/if}

					{if $record.publicationDate}
						<div class="row">
							<div class="result-label col-tn-4 col-lg-3">{translate text='Published'}</div>
							<div class="result-value col-tn-8 col-lg-9">{$record.publicationDate|escape}</div>
						</div>
					{/if}

					{if $showOut}
						<div class="row">
							<div class="result-label col-tn-4 col-lg-3">{translate text='Checked Out'}</div>
							<div class="result-value col-tn-8 col-lg-9">{$record.checkoutdate|date_format}</div>
						</div>
					{/if}

					<div class="row">
						<div class="result-label col-tn-4 col-lg-3">{translate text='Format'}</div>
						<div class="result-value col-tn-8 col-lg-9">{$record.format}</div>
					</div>

					{if $record.barcode}
						<div class="row">
							<div class="result-label col-tn-4 col-lg-3">{translate text='Barcode'}</div>
							<div class="result-value col-tn-8 col-lg-9">{$record.barcode}</div>
						</div>
					{/if}

					{if $showRatings && $record.groupedWorkId && $record.ratingData}
							<div class="row">
								<div class="result-label col-tn-4 col-lg-3">{translate text='Rating'}</div>
								<div class="result-value col-tn-8 col-lg-9">
									{include file="GroupedWork/title-rating.tpl" ratingClass="" id=$record.groupedWorkId ratingData=$record.ratingData showNotInterested=false}
								</div>
							</div>
					{/if}

					{if $hasLinkedUsers}
					<div class="row">
						<div class="result-label col-tn-4 col-lg-3">{translate text='Checked Out To'}</div>
						<div class="result-value col-tn-8 col-lg-9">
							{$record.user}
						</div>
					</div>
					{/if}

					<div class="row">
						<div class="result-label col-tn-4 col-lg-3">{translate text='Due'}</div>
						<div class="result-value col-tn-8 col-lg-9">
							{$record.dueDate|date_format}
							{if $record.overdue}
								<span class="overdueLabel"> OVERDUE</span>
							{elseif $record.daysUntilDue == 0}
								<span class="dueSoonLabel"> (Due today)</span>
							{elseif $record.daysUntilDue == 1}
								<span class="dueSoonLabel"> (Due tomorrow)</span>
							{elseif $record.daysUntilDue <= 7}
								<span class="dueSoonLabel"> (Due in {$record.daysUntilDue} days)</span>
							{/if}
						</div>
					</div>

					{if $record.fine}
						<div class="row">
							<div class="result-label col-tn-4 col-lg-3">{translate text='Fine'}</div>
							<div class="result-value col-tn-8 col-lg-9">
								{if $record.fine}
									<span class="overdueLabel"> {$record.fine} (up to now) </span>
								{/if}
							</div>
						</div>
					{/if}

					{if $showRenewed && $record.renewCount || $defaultSortOption == 'renewed'}{* Show times renewed when sorting by that value (even if 0)*}
						<div class="row">
							<div class="result-label col-tn-4 col-lg-3">{translate text='Renewed'}</div>
							<div class="result-value col-tn-8 col-lg-9">
								{$record.renewCount} times
								{if $record.renewMessage}{* TODO: used anymore? *}
									<div class="alert {if $record.renewResult == true}alert-success{else}alert-error{/if}">
										{$record.renewMessage|escape}
									</div>
								{/if}
							</div>
						</div>
					{/if}

					{if $showWaitList}
						<div class="row">
							<div class="result-label col-tn-4 col-lg-3">{translate text='Wait List'}</div>
							<div class="result-value col-tn-8 col-lg-9">
								{* Wait List goes here *}
								{$record.holdQueueLength}
							</div>
						</div>
					{/if}
				</div>

				{* Actions for Title *}
				{*<div class="{if $showCovers}col-xs-9 col-sm-8 col-md-4 col-lg-3{else}col-xs-11{/if}">*}
				<div class="col-xs-9 col-sm-8 col-md-4 col-lg-3">
					<div class="btn-group btn-group-vertical btn-block">
						{if !isset($record.canrenew) || $record.canrenew == true}
							{*<a href="#" onclick="$('#selected{$record.itemid}').attr('checked', 'checked');return VuFind.Account.renewSelectedTitles();" class="btn btn-sm btn-primary">Renew</a>*}
							<a href="#" onclick="return VuFind.Account.renewTitle('{$record.userId}', '{$record.recordId}', '{$record.renewIndicator}');" class="btn btn-sm btn-primary">{translate text='Renew'}</a>
						{else}
							Sorry, this title cannot be renewed
						{/if}
					</div>
				</div>
			</div>
		</div>
	</div>
{/strip}