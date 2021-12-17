{strip}
	{if !$masqueradeMode || ($masqueradeMode && $allowReadingHistoryDisplayInMasqueradeMode)}
		{* Do not display Reading History in Masquerade Mode, unless the library has allowed it *}
		{* Reading History Actions *}
		<div class="row">
			<form id="readingListForm">
			<input type="hidden" name="page" value="{$page}">
			<input type="hidden" name="patronId" id="patronId" value="{$selectedUser}">
			<input type="hidden" name="readingHistoryAction" id="readingHistoryAction" value="">
			<div id="readingListActionsTop" class="col-xs-6">
				<div class="btn-group btn-group-sm">
					{if $historyActive == true}
						<button class="btn btn-sm btn-info" onclick="return AspenDiscovery.Account.ReadingHistory.exportListAction()">{translate text="Export To Excel" isPublicFacing=true}</button>
					{else}
						<button class="btn btn-sm btn-primary" onclick="return AspenDiscovery.Account.ReadingHistory.optInAction()">{translate text="Start Recording My Reading History" isPublicFacing=true}</button>
					{/if}
				</div>
			</div>
			{if $historyActive == true}
				<div class="col-xs-6">
					<div class="btn-group btn-group-sm pull-right">
						{if $transList}
							<button class="btn btn-sm btn-danger " onclick="return AspenDiscovery.Account.ReadingHistory.deleteAllAction()">{translate text="Delete All" isPublicFacing=true}</button>
						{/if}
						<button class="btn btn-sm btn-danger" onclick="return AspenDiscovery.Account.ReadingHistory.optOutAction()">{translate text="Stop Recording My Reading History" isPublicFacing=true}</button>
					</div>
				</div>
			{/if}
			</form>

			<hr>

			{if $transList || !empty($readingHistoryFilter)}
				{* Results Page Options *}
				<div class="col-xs-12">
					<div class="row">
						<div class="form-group col-sm-3" id="sortOptions">
							<select aria-label="{translate text="Sort By" inAttribute=true isPublicFacing=true}" class="sortMethod form-control" id="sortMethod" name="accountSort" onchange="return AspenDiscovery.Account.loadReadingHistory($('#patronId').val(),$('#sortMethod option:selected').val(), 1,undefined, $('#readingHistoryFilter').val())">
								{foreach from=$sortOptions item=sortOptionLabel key=sortOption}
									<option value="{$sortOption}" {if $sortOption == $defaultSortOption}selected="selected"{/if}>{translate text="Sort By %1%" 1=$sortOptionLabel isPublicFacing=true}</option>
								{/foreach}
							</select>
						</div>
						<div class="form-group col-sm-7">
							<form class="form-inline" name="readingHistoryFilterForm" onsubmit="return AspenDiscovery.Account.loadReadingHistory($('#patronId').val(),$('#sortMethod option:selected').val(), 1,undefined, $('#readingHistoryFilter').val());">
								<div class="input-group">
									<input aria-label="{translate text="Filter Reading History" inAttribute=true isPublicFacing=true}" type="text" class="form-control" name="readingHistoryFilter" id="readingHistoryFilter" value="{$readingHistoryFilter}"/>
									<span class="input-group-btn">
										<button type="submit" class="btn btn-default" onclick="return AspenDiscovery.Account.loadReadingHistory($('#patronId').val(),$('#sortMethod option:selected').val(), 1,undefined, $('#readingHistoryFilter').val())">{translate text="Filter" isPublicFacing=true}</button>
										{if !empty($readingHistoryFilter)}
											<button type="submit" class="btn btn-default" onclick="return AspenDiscovery.Account.loadReadingHistory($('#patronId').val(),$('#sortMethod option:selected').val(), 1,undefined, '')">{translate text="Clear" isPublicFacing=true}</button>
										{/if}
									</span>
								</div>
							</form>
						</div>

						<div class="form-group col-sm-2" id="coverOptions">
							<label for="hideCovers" class="control-label checkbox pull-right"> {translate text='Hide Covers' isPublicFacing=true} <input id="hideCovers" type="checkbox" onclick="AspenDiscovery.Account.loadReadingHistory($('#patronId').val(),$('#sortMethod option:selected').val(), {$curPage},!$(this).is(':checked'), $('#readingHistoryFilter').val())" {if $showCovers == false}checked="checked"{/if}></label>
						</div>
					</div>
				</div>

				<a id="topOfList"></a>
				{if $transList}
					{* Reading History Entries *}
					<div class="striped">
						{foreach from=$transList item=record name="recordLoop" key=recordKey}
							{include file="MyAccount/readingHistoryEntry.tpl" record=$record}
						{/foreach}
					</div>
					{if $pageLinks.all}
						<div class="text-center">{$pageLinks.all}</div>
					{/if}
				{else}
					<div class="row">
						<div class="col-xs-12">
							{* No Items in the history because everything was filtered out *}
							<div class="alert alert-warning">{translate text="No items in your reading history match the specified filter, please search again." isPublcFacing=true}</div>
						</div>
					</div>
				{/if}
			{elseif $historyActive == true}
				{* No Items in the history, but the history is active *}
				{translate text="You do not have any items in your reading list." isPublicFacing=true}
			{/if}
		</div>
	{/if}
{/strip}
<script type="text/javascript">
	AspenDiscovery.Ratings.initializeRaters();
</script>
