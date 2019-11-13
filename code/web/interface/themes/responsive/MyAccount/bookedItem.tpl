{strip}
	<div class="result row">
		<div class="col-xs-12 col-sm-3">
			<div class="row">
				<div class="selectTitle col-xs-2">
					{if $record.cancelValue}
						<input type="checkbox" name="cancelId[{$record.userId}][{$record.cancelName}]" value="{$record.cancelValue}" id="selected{$record.cancelValue}" class="titleSelect">
						&nbsp;
					{/if}
				</div>
				<div class="col-xs-9 text-center">
					{if $record.id}
					<a href="{$record.linkUrl}">
						{/if}
						<img src="{$record.coverUrl}" class="listResultImage img-thumbnail img-responsive" alt="{translate text='Cover Image' inAttribute=true}">
						{if $record.id}
					</a>
					{/if}
				</div>
			</div>
		</div>

		<div class="col-xs-12 col-sm-9">
			<div class="row">
				<div class="col-xs-12">
					<span class="result-index">{$resultIndex})</span>&nbsp;
					{if $record.id}
					<a href="{$record.linkUrl}" class="result-title notranslate">
						{/if}
						{if !$record.title|removeTrailingPunctuation}{translate text='Title not available'}{else}{$record.title|removeTrailingPunctuation|truncate:180:"..."|highlight}{/if}
						{if $record.id}
					</a>
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

					{if $record.author}
						<div class="row">
							<div class="result-label col-xs-3">{translate text='Author'}</div>
							<div class="col-xs-9 result-value">
								{if is_array($record.author)}
									{foreach from=$record.author item=author}
										<a href='/Author/Home?author="{$author|escape:"url"}"'>{$author|highlight}</a>
									{/foreach}
								{else}
									<a href='/Author/Home?author="{$record.author|escape:"url"}"'>{$record.author|highlight}</a>
								{/if}
							</div>
						</div>
					{/if}

					{if $record.format}
						<div class="row">
							<div class="result-label col-xs-3">{translate text='Format'}</div>
							<div class="col-xs-9 result-value">
								{implode subject=$record.format glue=", "}
							</div>
						</div>
					{/if}

					{if $record.user}
						<div class="row">
							<div class="result-label col-xs-3">{translate text='Scheduled For'}</div>
							<div class="col-xs-9 result-value">
								{$record.user}
							</div>
						</div>
					{/if}

					{if $record.startDateTime == $record.endDateTime}
						{* Items Booked for a day will have the same start & end. (time is usually 4) *}
						<div class="row">
							<div class="result-label col-xs-3">{translate text='Scheduled Date'}</div>
							<div class="col-xs-9 result-value">
								{$record.startDateTime|date_format:"%b %d, %Y"} (All Day)
							</div>
						</div>
					{else}

						{* Otherwise display full datetime for start & end *}
						{if $record.startDateTime}
							<div class="row">
								<div class="result-label col-xs-3">{translate text='Starting at'}</div>
								<div class="col-xs-9 result-value">
									{*{$record.startDateTime|date_format:"%b %d, %Y at %l:%M %p"}*}
									{$record.startDateTime|date_format:"%b %d, %Y"}
								</div>
							</div>
						{/if}

						{if $record.endDateTime}
							<div class="row">
								<div class="result-label col-xs-3">{translate text='Ending at'}</div>
								<div class="col-xs-9 result-value">
									{*{$record.endDateTime|date_format:"%b %d, %Y at %l:%M %p"}*}
									{$record.endDateTime|date_format:"%b %d, %Y"}
								</div>
							</div>
						{/if}
					{/if}
					{if $record.status}
						<div class="row">
							<div class="result-label col-xs-3">{translate text='Status'}</div>
							<div class="col-xs-9 result-value">{$record.status}</div>
						</div>
					{/if}

				</div>

				<div class="col-xs-12 col-md-3">
					<div class="btn-group btn-group-vertical btn-block">
						{if $record.cancelValue}
							<button onclick="return AspenDiscovery.Account.cancelBooking('{$record.userId}', '{$record.cancelValue}')" class="btn btn-sm btn-warning">Cancel Item</button>
						{/if}
					</div>
				</div>

			</div>
		</div>
	</div>
{/strip}