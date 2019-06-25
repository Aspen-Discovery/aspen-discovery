{strip}
	{if $loggedIn}
		{if !empty($profile->_web_note)}
			<div class="row">
				<div id="web_note" class="alert alert-info text-center col-xs-12">{$profile->_web_note}</div>
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
			<div class="alert alert-warning">{translate text=offline_notice defaultText="<strong>The library system is currently offline.</strong> We are unable to retrieve information about your account at this time."}</div>
		{else}

			{if $transList}
				<form id="renewForm" action="{$path}/MyAccount/CheckedOut">
					<div id="pager" class="navbar form-inline">
						<label for="accountSort" class="control-label">{translate text='Sort by'}&nbsp;</label>
						<select name="accountSort" id="accountSort" class="form-control" onchange="AspenDiscovery.Account.changeAccountSort($(this).val());">
							{foreach from=$sortOptions item=sortDesc key=sortVal}
								<option value="{$sortVal}"{if $defaultSortOption == $sortVal} selected="selected"{/if}>{translate text=$sortDesc}</option>
							{/foreach}
						</select>

						<label for="hideCovers" class="control-label checkbox pull-right"> {translate text="Hide Covers"} <input id="hideCovers" type="checkbox" onclick="AspenDiscovery.Account.toggleShowCovers(!$(this).is(':checked'))" {if $showCovers == false}checked="checked"{/if}></label>
					</div>

					<div class="btn-group">
						{if !$hasOnlyEContentCheckOuts}
							<a href="#" onclick="AspenDiscovery.Account.renewSelectedTitles()" class="btn btn-sm btn-default">{translate text="Renew Selected Items"}</a>
							<a href="#" onclick="AspenDiscovery.Account.renewAll()" class="btn btn-sm btn-default">{translate text="Renew All"}</a>
						{/if}
						<a href="{$path}/MyAccount/CheckedOut?exportToExcel{if isset($defaultSortOption)}&accountSort={$defaultSortOption}{/if}" class="btn btn-sm btn-default" id="exportToExcelTop">{translate text="Export to Excel"}</a>
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
							{elseif $checkedOutTitle.checkoutSource == 'Rbdigital'}
								{include file="MyAccount/rbdigitalCheckedOutTitle.tpl" record=$checkedOutTitle resultIndex=$smarty.foreach.checkedOutTitleLoop.iteration}
							{else}
								<div class="row">
									{translate text="Unknown record source"} {$checkedOutTitle.checkoutSource}
								</div>
							{/if}
						{/foreach}
					</div>

					{if translate('CheckedOut_Econtent_notice')}
						<p class="alert alert-info">
							{translate text='CheckedOut_Econtent_notice' defaultText="Most eBooks and eAudiobooks cannot be renewed before they expire.  <br> However, eContent from OverDrive can be renewed within the OverDrive app starting 3 days before the due date if the title is not on hold by other patrons. You may need to download the title again after renewal.<br> For other content, if you want to renew, please wait for the title to expire and then check it out again.  You may need to download the title again after you check it out. You may be able to place a new hold on the title a few days before the title expires to help ensure continuous reading/listening."}
						</p>
					{/if}

					<div class="btn-group">
						{if !$hasOnlyEContentCheckOuts}
							<a href="#" onclick="AspenDiscovery.Account.renewSelectedTitles()" class="btn btn-sm btn-default">{translate text="Renew Selected Items"}</a>
							<a href="#" onclick="AspenDiscovery.Account.renewAll()" class="btn btn-sm btn-default">{translate text="Renew All"}</a>
						{/if}
						<a href="{$path}/MyAccount/CheckedOut?exportToExcel{if isset($defaultSortOption)}&accountSort={$defaultSortOption}{/if}" class="btn btn-sm btn-default" id="exportToExcelTop">{translate text="Export to Excel"}</a>
					</div>
				</form>
			{else}
				{translate text='You do not have any items checked out'}.
			{/if}
		{/if}
	{else}
		You must login to view this information. Click
		<a href="{$path}/MyAccount/Login">here</a>
		to login.
	{/if}
{/strip}