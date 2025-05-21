{strip}<div class="row result" id="readingHistoryEntry{$record.permanentId}">
	{* Cover Column *}
	{if !empty($showCovers)}
		<div class="col-xs-3 col-sm-4 col-md-2 text-center">
			{if !empty($record.coverUrl)}
				{if !empty($record.recordId) && $record.linkUrl}
					<a href="{$record.linkUrl}" id="descriptionTrigger{$record.recordId|escape:"url"}" aria-hidden="true">
						<img src="{$record.coverUrl}" class="listResultImage img-thumbnail img-responsive {$coverStyle}" alt="{translate text='Cover Image' inAttribute=true isPublicFacing=true}">
					</a>
				{else} {* Cover Image but no Record-View link *}
					<img src="{$record.coverUrl}" class="listResultImage img-thumbnail img-responsive {$coverStyle}" alt="{translate text='Cover Image' inAttribute=true isPublicFacing=true}" aria-hidden="true">
				{/if}
			{/if}
		</div>
	{/if}

	{* Title Details Column *}
	<div class="{if !empty($showCovers)}col-xs-9 col-sm-8 col-md-10{else}col-tn-12{/if}">
		<div class="row">
			<div class="col-xs-12 result-title notranslate">
				{$record.index})&nbsp;
				{if !empty($record.linkUrl)}
					<a href="{$record.linkUrl}" class="title">{if !$record.title|removeTrailingPunctuation} {translate text='Title not available' isPublicFacing=true}{else}{$record.title|removeTrailingPunctuation|truncate:180:"..."|highlight}{/if}</a>
				{else}
					{if !$record.title|removeTrailingPunctuation} {translate text='Title not available' isPublicFacing=true}{else}{$record.title|removeTrailingPunctuation}{/if}
				{/if}
				{if !empty($record.title2)}
					<div class="searchResultSectionInfo">
						{$record.title2|removeTrailingPunctuation|truncate:180:"..."|highlight}
					</div>
				{/if}
			</div>
		</div>

		<div class="row">
			<div class="col-xs-12 col-md-9">

				{if !empty($record.author)}
					<div class="row">
						<div class="result-label col-tn-3"> {translate text='Author' isPublicFacing=true}</div>
						<div class="result-value col-tn-9">
							{if is_array($record.author)}
								{foreach from=$summAuthor item=author}
									<a href='/Author/Home?author="{$author|escape:"url"}"'>{$author|highlight}</a>
								{/foreach}
							{else}
								<a href='/Author/Home?author="{$record.author|escape:"url"}"'>{$record.author|highlight}</a>
							{/if}
						</div>
					</div>
				{/if}

				{if !empty($record.format)}
					<div class="row">
						<div class="result-label col-tn-3">{translate text='Format' isPublicFacing=true}</div>
						<div class="result-value col-tn-9">
							{if is_array($record.format)}
								{implode subject=$record.format glue=", " translate=true isPublicFacing=true}
							{else}
								{if !empty($record.format)}
									{translate text=$record.format isPublicFacing=true}
								{/if}
							{/if}
						</div>
					</div>
				{/if}

				<div class="row">
					<div class="result-label col-tn-3">{translate text='Last Used' isPublicFacing=true}</div>
					<div class="result-value col-tn-9">
						{if !empty($record.checkedOut)}
							{translate text="In Use" isPublicFacing=true}
						{else}
							{if is_numeric($record.checkout)}
								{$record.checkout|date_format:"%b %Y"}
							{else}
								{$record.checkout|escape}
							{/if}
						{/if}
					</div>
				</div>

				{if !empty($showRatings)}
					{if !empty($record.existsInCatalog) && !empty($record.ratingData)}
						<div class="row">
							<div class="result-label col-tn-3">{translate text="Rating" isPublicFacing=true}</div>
							<div class="result-value col-tn-9">
								{include file="GroupedWork/title-rating.tpl" id=$record.permanentId summId=$record.permanentId ratingData=$record.ratingData showNotInterested=false}
							</div>
						</div>
					{/if}
				{/if}
			</div>

			<div class="col-xs-12 col-md-3">
				<div class="btn-group btn-group-vertical btn-block">
					{if empty($record.permanentId)}
						<a href="#" onclick="return AspenDiscovery.Account.ReadingHistory.deleteEntryByTitleAuthor('{$selectedUser}', '{$record.title}', '{$record.author}');" class="btn btn-sm btn-primary">{translate text='Delete' isPublicFacing=true}</a>
					{else}
						<a href="#" onclick="return AspenDiscovery.Account.ReadingHistory.deleteEntry('{$selectedUser}', '{$record.permanentId}');" class="btn btn-sm btn-primary">{translate text='Delete' isPublicFacing=true}</a>
					{/if}
				</div>
				{if !empty($showWhileYouWait)}
					{if !$record.isIll}
						<div class="btn-group btn-group-vertical btn-block">
							{if !empty($record.existsInCatalog)}
								<button onclick="return AspenDiscovery.GroupedWork.getYouMightAlsoLike('{$record.permanentId}');" class="btn btn-sm btn-default btn-wrap">{translate text="You Might Also Like" isPublicFacing=true}</button>
							{/if}
						</div>
					{/if}
				{/if}
			</div>
		</div>

		{if !empty($record.existsInCatalog)}
			<div class="row">
				<div class="col-xs-12">
					{include file='GroupedWork/result-tools-horizontal.tpl' recordDriver=$record.recordDriver summTitle=$record.title ratingData=$record.ratingData recordUrl=$record.linkUrl showMoreInfo=true showNotInterested=false}
				</div>
			</div>
		{/if}
	</div>

</div>
{/strip}