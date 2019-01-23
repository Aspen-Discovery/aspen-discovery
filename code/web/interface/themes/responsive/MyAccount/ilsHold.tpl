{strip}
	{* Overall hold *}
	<div class="result row">
		{* Cover column *}
		{if $showCovers}
		<div class="col-xs-4 col-sm-3">
			{*<div class="row">*}
				{*
				<div class="selectTitle col-xs-2">
					{if $record.cancelable}
						{if $section == 'available'}
							<input type="checkbox" name="availableholdselected[]" value="{$record.cancelId}" id="selected{$record.cancelId|escape:"url"}" class="titleSelect{$sectionKey} titleSelect"/>&nbsp;
						{else}
							<input type="checkbox" name="waitingholdselected[]" value="{$record.cancelId}" id="selected{$record.cancelId|escape:"url"}" class="titleSelect{$sectionKey} titleSelect"/>&nbsp;
						{/if}
					{/if}
				</div>
				*}
				<div class="{*col-xs-10 *}text-center">
					{if $record.coverUrl}
						{if $record.recordId && $record.linkUrl}
							<a href="{$record.linkUrl}" id="descriptionTrigger{$record.recordId|escape:"url"}">
								<img src="{$record.coverUrl}" class="listResultImage img-thumbnail img-responsive" alt="{translate text='Cover Image'}">
							</a>
						{else} {* Cover Image but no Record-View link *}
							<img src="{$record.coverUrl}" class="listResultImage img-thumbnail img-responsive" alt="{translate text='Cover Image'}">
						{/if}
					{/if}

				</div>
			{*</div>*}
		</div>

		{/if}
		{* Details Column*}
			<div class="{if $showCovers}col-xs-8 col-sm-9{else}col-xs-12{/if}">
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

			{* 2 column row to show information and then actions*}
			<div class="row">
				{* Information column author, format, etc *}
				<div class="resultDetails col-xs-12 col-md-8 col-lg-9">
					{if $record.volume}
						<div class="row">
							<div class="result-label col-tn-3">{translate text='Volume'}</div>
							<div class="col-tn-9 result-value">
								{$record.volume}
							</div>
						</div>
					{/if}

					{if $record.author}
						<div class="row">
							<div class="result-label col-tn-3">{translate text='Author'}</div>
							<div class="col-tn-9 result-value">
								{if is_array($record.author)}
									{foreach from=$record.author item=author}
										<a href='{$path}/Author/Home?"author={$author|escape:"url"}"'>{$author|highlight}</a>
									{/foreach}
								{else}
									<a href='{$path}/Author/Home?author="{$record.author|escape:"url"}"'>{$record.author|highlight}</a>
								{/if}
							</div>
						</div>
					{/if}

					{if $record.format}
						<div class="row">
							<div class="result-label col-tn-3">{translate text='Format'}</div>
							<div class="col-tn-9 result-value">
								{implode subject=$record.format glue=", "}
							</div>
						</div>
					{/if}

					{if $hasLinkedUsers}
					<div class="row">
						<div class="result-label col-tn-3">{translate text='On Hold For'}</div>
						<div class="col-tn-9 result-value">
							{$record.user}
						</div>
					</div>
					{/if}

					<div class="row">
						<div class="result-label col-tn-3">{translate text='Pickup'}</div>
						<div class="col-tn-9 result-value">
							{$record.location}
						</div>
					</div>

					{if $showPlacedColumn && $record.create}
						<div class="row">
							<div class="result-label col-tn-3">{translate text='Date Placed'}</div>
							<div class="col-tn-9 result-value">
								{$record.create|date_format:"%b %d, %Y"}
							</div>
						</div>
					{/if}

					{if $section == 'available'}
						{* Available Hold *}
						<div class="row">
							<div class="result-label col-tn-3">{translate text='Available'}</div>
							<div class="col-tn-9 result-value">
								{if $record.availableTime}
									{$record.availableTime|date_format:"%b %d, %Y at %l:%M %p"}
								{else}
									{if strcasecmp($record.status, 'Hold Being Shelved') === 0}
										<strong>{$record.status}</strong>
									{else}
										Now
									{/if}
								{/if}
							</div>
						</div>

						{if $record.expire}
							<div class="row">
								<div class="result-label col-tn-3">{translate text='Pick-Up By'}</div>
								<div class="col-tn-9 result-value">
									<strong>{$record.expire|date_format:"%b %d, %Y"}</strong>
								</div>
							</div>
						{/if}
					{else}
						{* Unavailable hold *}
						<div class="row">
							<div class="result-label col-tn-3">{translate text='Status'}</div>
							<div class="col-tn-9 result-value">
								{if $record.frozen}
									<span class="frozenHold">
								{/if}
								{$record.status}
								{if $record.frozen && $showDateWhenSuspending && $record.reactivate} until {$record.reactivate|date_format:"%b %d, %Y"}</span>{/if}
								{* No references to freezeMessage in php code found. plb 8-3-2016
								{if strlen($record.freezeMessage) > 0}
									<div class='{if $record.freezeResult == true}freezePassed{else}freezeFailed{/if}'>
										{$record.freezeMessage|escape}
									</div>
								{/if}*}
							</div>
						</div>

						{if $showPosition && $record.position}
							<div class="row">
								<div class="result-label col-tn-3">{translate text='Position'}</div>
								<div class="col-tn-9 result-value">
									{$record.position}
								</div>
							</div>
						{/if}

						{if $record.automaticCancellation && $showHoldCancelDate}
							<div class="row">
								<div class="result-label col-tn-3">{translate text='Cancels on'}</div>
								<div class="col-tn-9 result-value">
									{$record.automaticCancellation|date_format:"%b %d, %Y"}
								</div>
							</div>
						{/if}
					{/if}
				</div>

				{* Actions for Title *}
				<div class="col-xs-9 col-sm-8 col-md-4 col-lg-3">
					<div class="btn-group btn-group-vertical btn-block">
						{if $section == 'available'}
							{if $record.cancelable}
								{* First step in cancelling a hold is now fetching confirmation message, with better labeled buttons. *}
								<button onclick="return VuFind.Account.confirmCancelHold('{$record.userId}', '{$record.id}', '{$record.cancelId}');" class="btn btn-sm btn-warning">{translate text="Cancel Hold"}</button>
							{/if}
						{else}
							{if $record.cancelable}
								{* First step in cancelling a hold is now fetching confirmation message, with better labeled buttons. *}
								<button onclick="return VuFind.Account.confirmCancelHold('{$record.userId}', '{$record.id}', '{$record.cancelId}');" class="btn btn-sm btn-warning">{translate text="Cancel Hold"}</button>
							{/if}
							{if $record.allowFreezeHolds}
								{if $record.frozen}
									<button onclick="return VuFind.Account.thawHold('{$record.userId}', '{$record.id}', '{$record.cancelId}', this);" class="btn btn-sm btn-default">{translate text="Thaw Hold"}</button>
								{elseif $record.freezeable}
									<button onclick="return VuFind.Account.freezeHold('{$record.userId}', '{$record.id}', '{$record.cancelId}', {if $suspendRequiresReactivationDate}true{else}false{/if}, this);" class="btn btn-sm btn-default">{translate text="Freeze Hold"}</button>
								{/if}
							{/if}
							{if $record.locationUpdateable}
								<button onclick="return VuFind.Account.changeHoldPickupLocation('{$record.userId}', '{$record.id}', '{$record.cancelId}');" class="btn btn-sm btn-default">Change Pickup Loc.</button>
							{/if}
						{/if}
					</div>
				</div>
			</div>
		</div>
	</div>
{/strip}