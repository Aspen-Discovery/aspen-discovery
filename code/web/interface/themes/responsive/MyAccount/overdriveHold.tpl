{strip}
	<div class="result row" id="overDriveHold_{$record.overDriveId}">
		{* Cover column *}
		{if $showCovers}
		<div class="col-xs-4 col-sm-3">
			{*<div class="row">*}
				<div class="{*col-xs-10 *}text-center">
					{if $record.coverUrl}
						{if $record.recordId && $record.linkUrl}
							<a href="{$record.linkUrl}" id="descriptionTrigger{$record.recordId|escape:"url"}" aria-hidden="true">
								<img src="{$record.coverUrl}" class="listResultImage img-thumbnail img-responsive" alt="{translate text='Cover Image' inAttribute=true}">
							</a>
						{else} {* Cover Image but no Record-View link *}
							<img src="{$record.coverUrl}" class="listResultImage img-thumbnail img-responsive" alt="{translate text='Cover Image' inAttribute=true}" aria-hidden="true">
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
					{if $record.linkUrl}
					<a href="{$record.linkUrl}" class="result-title notranslate">
						{*{if !$record.title}{translate text='Title not available'}{else}{$record.title|removeTrailingPunctuation}{/if}*}
						{if !$record.title|removeTrailingPunctuation}{translate text='Title not available'}{else}{$record.title|removeTrailingPunctuation|truncate:180:"..."|highlight}{/if}
					</a>
					{else}
						<span class="result-title notranslate">
							{if !$record.title|removeTrailingPunctuation}{translate text='Title not available'}{else}{$record.title|removeTrailingPunctuation|truncate:180:"..."|highlight}{/if}
						</span>
					{/if}
					{if $record.subTitle}
						<div class="searchResultSectionInfo">
							{$record.subTitle|removeTrailingPunctuation}
						</div>
					{/if}
				</div>
			</div>

			<div class="row">
				<div class="resultDetails col-xs-12 col-md-8 col-lg-9">
					{if $record.author}
						<div class="row">
							<div class="result-label col-tn-4">{translate text='Author'}</div>
							<div class="col-tn-8 result-value">
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

					<div class="row">
						<div class="result-label col-tn-4">{translate text='Source'}</div>
						<div class="col-tn-8 result-value">
							OverDrive
						</div>
					</div>

					{if $record.format}
						<div class="row">
							<div class="result-label col-tn-4">{translate text='Format'}</div>
							<div class="col-tn-8 result-value">
								{implode subject=$record.format glue=", "}
							</div>
						</div>
					{/if}

					{if $hasLinkedUsers}
					<div class="row">
						<div class="result-label col-tn-4">{translate text='On Hold For'}</div>
						<div class="col-tn-8 result-value">
							{$record.user}
						</div>
					</div>
					{/if}

					{if $record.create}
						<div class="row">
							<div class="result-label col-tn-4">{translate text='Date Placed'}</div>
							<div class="col-tn-8 result-value">
								{$record.create|date_format:"%b %d, %Y"}
							</div>
						</div>
					{/if}

					{if $section == 'available'}
					{* Available Hold *}
						<div class="row">
							<div class="result-label col-tn-4">{translate text='Expires'}</div>
							<div class="col-tn-8 result-value">
								<strong>{$record.expire|date_format:"%b %d, %Y at %l:%M %p"}</strong>
							</div>
						</div>
					{else}
						{* Unavailable hold *}
						<div class="row">
							{if $record.frozen}
								<div class="result-label col-tn-4">{translate text='Status'}</div>
								<div class="col-tn-8 result-value">
									<span class="frozenHold label label-warning">{$record.status|translate}</span>
								</div>
							{else}
								<div class="result-label col-tn-4">{translate text='Position'}</div>
								<div class="col-tn-8 result-value">
									{translate text="%1% out of %2%" 1=$record.holdQueuePosition 2=$record.holdQueueLength}
								</div>
							{/if}
						</div>
					{/if}
				</div>

				{* Actions for Title *}
				<div class="col-xs-9 col-sm-8 col-md-4 col-lg-3">
					<div class="btn-group btn-group-vertical btn-block">
						{if $section == 'available'}
							<button onclick="return AspenDiscovery.OverDrive.doOverDriveCheckout('{$record.userId}', '{$record.overDriveId}');" class="btn btn-sm btn-action">{translate text="Checkout"}</button>
						{/if}
						<button onclick="return AspenDiscovery.OverDrive.cancelOverDriveHold('{$record.userId}', '{$record.overDriveId}');" class="btn btn-sm btn-warning">{translate text="Cancel Hold"}</button>
						{if $record.allowFreezeHolds}
							{if $record.frozen}
								<button onclick="return AspenDiscovery.OverDrive.thawHold('{$record.userId}', '{$record.overDriveId}', this);" class="btn btn-sm btn-default">{translate text="Thaw Hold"}</button>
							{elseif $record.canFreeze}
								<button onclick="return AspenDiscovery.OverDrive.freezeHold('{$record.userId}', '{$record.overDriveId}');" class="btn btn-sm btn-default">{translate text="Freeze Hold"}</button>
							{/if}
						{/if}
					</div>
					{if !empty($record.previewActions)}
						<div class="btn-group btn-group-vertical btn-block">
							{foreach from=$record.previewActions item=curAction}
								{if $curAction.url && strlen($curAction.url) > 0}
									<a href="{$curAction.url}" class="btn btn-sm {if empty($curAction.btnType)}btn-action{else}{$curAction.btnType}{/if} btn-wrap" onclick="{if $curAction.requireLogin}return AspenDiscovery.Account.followLinkIfLoggedIn(this, '{$curAction.url}');{/if}" {if $curAction.alt}title="{translate text=$curAction.alt inAttribute=true}"{/if}>{$curAction.title|translate}</a>
								{else}
									<a href="#" class="btn btn-sm {if empty($curAction.btnType)}btn-action{else}{$curAction.btnType}{/if} btn-wrap" onclick="{$curAction.onclick}" {if $curAction.alt}title="{translate text=$curAction.alt inAttribute=true}"{/if}>{$curAction.title|translate}</a>
								{/if}
							{/foreach}
						</div>
					{/if}
					{if $showWhileYouWait}
						<div class="btn-group btn-group-vertical btn-block">
							{if !empty($record.groupedWorkId)}
								<button onclick="return AspenDiscovery.GroupedWork.getWhileYouWait('{$record.groupedWorkId}');" class="btn btn-sm btn-default btn-wrap">{translate text="While You Wait"}</button>
							{/if}
						</div>
					{/if}
				</div>
			</div>
		</div>
	</div>
{/strip}