{strip}
	{if $loggedIn}
		{if $profile->web_note}
			<div class="row">
				<div id="web_note" class="alert alert-info text-center col-xs-12">{$profile->web_note}</div>
			</div>
		{/if}

		{* Alternate Mobile MyAccount Menu *}
		{include file="MyAccount/mobilePageHeader.tpl"}

		<span class='availableHoldsNoticePlaceHolder'></span>

		<h2>{translate text='Checked Out Titles'}</h2>

		{if $libraryHoursMessage}
			<div class="libraryHours alert alert-success">{$libraryHoursMessage}</div>
		{/if}

		{if $offline}
			<div class="alert alert-warning"><strong>The library system is currently offline.</strong> We are unable to retrieve information about your {translate text='Checked Out Titles'|lower} at this time.</div>
		{else}

			{if $transList}
				<form id="renewForm" action="{$path}/MyAccount/RenewMultiple">
					<div id="pager" class="navbar form-inline">
						<label for="accountSort" class="control-label">{translate text='Sort by'}:&nbsp;</label>
						<select name="accountSort" id="accountSort" class="form-control" onchange="VuFind.Account.changeAccountSort($(this).val());">
							{foreach from=$sortOptions item=sortDesc key=sortVal}
								<option value="{$sortVal}"{if $defaultSortOption == $sortVal} selected="selected"{/if}>{translate text=$sortDesc}</option>
							{/foreach}
						</select>

						<label for="hideCovers" class="control-label checkbox pull-right"> Hide Covers <input id="hideCovers" type="checkbox" onclick="VuFind.Account.toggleShowCovers(!$(this).is(':checked'))" {if $showCovers == false}checked="checked"{/if}></label>
					</div>

					<div class="btn-group">
						{if !$hasOnlyEContentCheckOuts}
							<a href="#" onclick="VuFind.Account.renewSelectedTitles()" class="btn btn-sm btn-default">Renew Selected Items</a>
							<a href="#" onclick="VuFind.Account.renewAll()" class="btn btn-sm btn-default">Renew All</a>
						{/if}
						<a href="{$path}/MyAccount/CheckedOut?exportToExcel{if isset($defaultSortOption)}&accountSort={$defaultSortOption}{/if}" class="btn btn-sm btn-default" id="exportToExcelTop">Export to Excel</a>
					</div>

					<br>

					<div class="striped">
						{foreach from=$transList item=checkedOutTitle name=checkedOutTitleLoop key=checkedOutKey}
							{if $checkedOutTitle.checkoutSource == 'ILS'}
								{include file="MyAccount/ilsCheckedOutTitle.tpl" record=$checkedOutTitle resultIndex=$smarty.foreach.checkedOutTitleLoop.iteration}
							{elseif $checkedOutTitle.checkoutSource == 'OverDrive'}
								{include file="MyAccount/overdriveCheckedOutTitle.tpl" record=$checkedOutTitle resultIndex=$smarty.foreach.checkedOutTitleLoop.iteration}
							{elseif $checkedOutTitle.checkoutSource == 'Hoopla'}
								{include file="MyAccount/hooplaCheckedOutTitle.tpl" record=$checkedOutTitle resultIndex=$smarty.foreach.checkedOutTitleLoop.iteration}
							{else}
								<div class="row">
									Unknown record source {$checkedOutTitle.checkoutSource}
								</div>
							{/if}
						{/foreach}
					</div>

					{if translate('CheckedOut_Econtent_notice')}
						<p class="alert alert-info">
							{translate text='CheckedOut_Econtent_notice'}
						</p>
					{/if}

					<div class="btn-group">
						{if !$hasOnlyEContentCheckOuts}
							<a href="#" onclick="VuFind.Account.renewSelectedTitles()" class="btn btn-sm btn-default">Renew Selected Items</a>
							<a href="#" onclick="VuFind.Account.renewAll()" class="btn btn-sm btn-default">Renew All</a>
						{/if}
						<a href="{$path}/MyAccount/CheckedOut?exportToExcel{if isset($defaultSortOption)}&accountSort={$defaultSortOption}{/if}" class="btn btn-sm btn-default" id="exportToExcelTop">Export to Excel</a>
					</div>
				</form>

			{else}
				{translate text='You do not have any items checked out'}.
			{/if}
		{/if}
	{else}
		You must login to view this information. Click <a href="{$path}/MyAccount/Login">here</a> to login.
	{/if}
{/strip}