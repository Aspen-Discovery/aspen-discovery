<div class="col-xs-12">
{if $loggedIn}

	{if $profile->web_note}
		<div class="row">
			<div id="web_note" class="alert alert-info text-center col-xs-12">{$profile->web_note}</div>
		</div>
	{/if}

		{* Alternate Mobile MyAccount Menu *}
		{include file="MyAccount/mobilePageHeader.tpl"}

	<span class='availableHoldsNoticePlaceHolder'></span>

	<h2>{translate text='My Reading History'} {if $historyActive == true}<small><a id="readingListWhatsThis" href="#" onclick="$('#readingListDisclaimer').toggle();return false;">(What's This?)</a></small>{/if}</h2>

		{include file="MyAccount/switch-linked-user-form.tpl" label="View Reading History for" actionPath="/MyAccount/ReadingHistory"}

	<br>
		{if $offline}
		<div class="alert alert-warning"><strong>The library system is currently offline.</strong> We are unable to retrieve information about your reading history at this time.</div>
		{else}
			{strip}

		{if $masqueradeMode && !$allowReadingHistoryDisplayInMasqueradeMode}
			<div class="row">
				<div class="alert alert-warning">
					Display of the patron's reading history is disabled in Masquerade Mode.
				</div>
			</div>
		{/if}

	<div class="row">
		<div id="readingListDisclaimer" {if $historyActive == true}style="display: none"{/if} class="alert alert-info">
			{* some necessary white space in notice was previously stripped out when needed. *}
		{/strip}
			{translate text="ReadingHistoryNotice"}
		{strip}
		</div>
	</div>

	{if !$masqueradeMode || ($masqueradeMode && $allowReadingHistoryDisplayInMasqueradeMode)}
		{* Do not display Reading History in Masquerade Mode, unless the library has allowed it *}
	<form id="readingListForm" action="{$fullPath}" class="form-inline">

		{* Reading History Actions *}
		<div class="row">
			<input type="hidden" name="page" value="{$page}">
			<input type="hidden" name="patronId" value="{$selectedUser}">
			<input type="hidden" name="readingHistoryAction" id="readingHistoryAction" value="">
			<div id="readingListActionsTop" class="col-xs-6">
				<div class="btn-group btn-group-sm">
					{if $historyActive == true}
						<button class="btn btn-sm btn-info" onclick="return VuFind.Account.ReadingHistory.exportListAction()">Export To Excel</button>
						{if $transList}
							<button class="btn btn-sm btn-warning" onclick="return VuFind.Account.ReadingHistory.deletedMarkedAction()">Delete Marked</button>
						{/if}
					{else}
						<button class="btn btn-sm btn-primary" onclick="return VuFind.Account.ReadingHistory.optInAction()">Start Recording My Reading History</button>
					{/if}
				</div>
			</div>
			{if $historyActive == true}
				<div class="col-xs-6">
					<div class="btn-group btn-group-sm pull-right">
				{if $transList}
					<button class="btn btn-sm btn-danger " onclick="return VuFind.Account.ReadingHistory.deleteAllAction()">Delete All</button>
				{/if}
				<button class="btn btn-sm btn-danger" onclick="return VuFind.Account.ReadingHistory.optOutAction()">Stop Recording My Reading History</button>
				</div>
			</div>
			{/if}


			<hr>

			{if $transList}

				{* Results Page Options *}
				<div id="pager" class="col-xs-12">
					<div class="row">
						<div class="form-group col-sm-5" id="recordsPerPage">
							<label for="pagesize" class="control-label">Records Per Page&nbsp;</label>
							<select id="pagesize" class="pagesize form-control input-sm" onchange="VuFind.changePageSize()">
								<option value="10"{if $recordsPerPage == 10} selected="selected"{/if}>10</option>
								<option value="25"{if $recordsPerPage == 25} selected="selected"{/if}>25</option>
								<option value="50"{if $recordsPerPage == 50} selected="selected"{/if}>50</option>
								<option value="75"{if $recordsPerPage == 75} selected="selected"{/if}>75</option>
								<option value="100"{if $recordsPerPage == 100} selected="selected"{/if}>100</option>
							</select>
						</div>
						<div class="form-group col-sm-5" id="sortOptions">
							<label for="sortMethod" class="control-label">Sort By&nbsp;</label>
							<select class="sortMethod form-control" id="sortMethod" name="accountSort" onchange="VuFind.Account.changeAccountSort($(this).val())">
								{foreach from=$sortOptions item=sortOptionLabel key=sortOption}
									<option value="{$sortOption}" {if $sortOption == $defaultSortOption}selected="selected"{/if}>{$sortOptionLabel}</option>
								{/foreach}
							</select>
						</div>
						<div class="form-group col-sm-2" id="coverOptions">
							<label for="hideCovers" class="control-label checkbox pull-right"> Hide Covers <input id="hideCovers" type="checkbox" onclick="VuFind.Account.toggleShowCovers(!$(this).is(':checked'))" {if $showCovers == false}checked="checked"{/if}></label>
						</div>
					</div>
				</div>

				{* Header Row with Column Labels *}
				<div class="row hidden-xs">
					<div class="col-sm-1">
						<input id="selectAll" type="checkbox" onclick="VuFind.toggleCheckboxes('.titleSelect', '#selectAll');" title="Select All/Deselect All">
					</div>
					{if $showCovers}
					<div class="col-sm-2">
						{translate text='Cover'}
					</div>
					{/if}
					<div class="{if $showCovers}col-sm-7{else}col-sm-9{/if}">
						{translate text='Title'}
					</div>
					<div class="col-sm-2">
						{translate text='Checked Out'}
					</div>
				</div>

				{* Reading History Entries *}
				<div class="striped">
					{foreach from=$transList item=record name="recordLoop" key=recordKey}
						<div class="row">

							{* Cover Column *}
							{if $showCovers}
							<div class="col-tn-3">
								<div class="row">
									<div class="col-xs-12 col-sm-1">
										<input type="checkbox" name="selected[{$record.permanentId|escape:"url"}]" class="titleSelect" value="rsh{$record.itemindex}" id="rsh{$record.itemindex}">
									</div>
									<div class="col-xs-12 col-sm-10">
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
								</div>
							</div>
							{else}
								<div class="col-tn-1">
									<input type="checkbox" name="selected[{$record.permanentId|escape:"url"}]" class="titleSelect" value="rsh{$record.itemindex}" id="rsh{$record.itemindex}">
								</div>
							{/if}

							{* Title Details Column *}
							<div class="{if $showCovers}col-tn-7 col-sm-7{else}col-tn-9 col-sm-9{/if}">
								<div class="row">
									<div class="col-xs-12">
										<strong>
											{if $record.recordId && $record.linkUrl}
												<a href="{$record.linkUrl}" class="title">{if !$record.title|removeTrailingPunctuation}{translate text='Title not available'}{else}{$record.title|removeTrailingPunctuation|truncate:180:"..."|highlight}{/if}</a>
											{else}
												{if !$record.title|removeTrailingPunctuation}{translate text='Title not available'}{else}{$record.title|removeTrailingPunctuation}{/if}
											{/if}
											{if $record.title2}
												<div class="searchResultSectionInfo">
													{$record.title2|removeTrailingPunctuation|truncate:180:"..."|highlight}
												</div>
											{/if}
										</strong>
									</div>
								</div>

								{if $record.author}
									<div class="row">
										<div class="result-label col-tn-3">{translate text='Author'}</div>
										<div class="result-value col-tn-9">
											{if is_array($record.author)}
												{foreach from=$summAuthor item=author}
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
										<div class="result-label col-tn-3">{translate text='Published'}</div>
										<div class="result-value col-tn-9">
											{$record.publicationDate|escape}
										</div>
									</div>
								{/if}

								<div class="row">
									<div class="result-label col-tn-3">{translate text='Format'}</div>
									<div class="result-value col-tn-9">
										{if is_array($record.format)}
											{implode subject=$record.format glue=", "}
										{else}
											{$record.format}
										{/if}
									</div>
								</div>

								{if $showRatings == 1}
									{if $record.recordId != -1 && $record.ratingData}
										<div class="row">
											<div class="result-label col-tn-3">Rating&nbsp;</div>
											<div class="result-value col-tn-9">
												{include file="GroupedWork/title-rating.tpl" ratingClass="" id=$record.permanentId ratingData=$record.ratingData showNotInterested=false}
											</div>
										</div>
									{/if}
								{/if}
							</div>

							{* Checkout Date Column *}
							<div class="col-tn-12 {if $showCovers}col-tn-offset-3{else}col-tn-offset-1{/if} col-sm-2 col-sm-offset-0">
								{* on xs viewports, the offset lines up the date with the title details *}
								{if is_numeric($record.checkout)}
									{$record.checkout|date_format}
								{else}
									{$record.checkout|escape}
								{/if}
								{if $record.lastCheckout} to {$record.lastCheckout|escape}{/if}
								{* Do not show checkin date since historical data from initial import is not correct.
								{if $record.checkin} to {$record.checkin|date_format}{/if}
								*}
							</div>
						</div>
					{/foreach}
				</div>

				<hr>

				<div class="row">
					<div class="col-xs-12">
					<div id="readingListActionsBottom" class="btn-group btn-group-sm">
							{if $historyActive == true}
								<button class="btn btn-sm btn-info" onclick="return VuFind.Account.ReadingHistory.exportListAction()">Export To Excel</button>
								{if $transList}
									<button class="btn btn-sm btn-warning" onclick="return VuFind.Account.ReadingHistory.deletedMarkedAction()">Delete Marked</button>
								{/if}
							{else}
								<button class="btn btn-sm btn-primary" onclick="return VuFind.Account.ReadingHistory.optInAction()">Start Recording My Reading History</button>
							{/if}
					</div>
				</div>
				</div>

				{if $pageLinks.all}<div class="text-center">{$pageLinks.all}</div>{/if}
			{elseif $historyActive == true}
				{* No Items in the history, but the history is active *}
				You do not have any items in your reading list.	It may take up to 3 hours for your reading history to be updated after you start recording your history.
			{/if}
			</div>
		</form>
	{/if}

	{/strip}
			{/if}
{else}
	<div class="page">
		You must login to view this information. Click <a href="{$path}/MyAccount/Login">here</a> to login.
	</div>
{/if}
</div>
