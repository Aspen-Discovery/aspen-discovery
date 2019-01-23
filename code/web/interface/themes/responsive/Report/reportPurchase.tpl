<script type="text/javascript">
{literal}
$(function() {
		$( "#dateFilterStart" ).datepicker();
	});
$(function() {
	$( "#dateFilterEnd" ).datepicker();
});
{/literal}
</script>

	<div id="main-content" class="col-md-12">
		{if $loggedIn}
			<div class="myAccountTitle">
				<h1>Reports - Purchase Tracking</h1>
			</div>
			<div class="myAccountTitle">
				<form method="get" action="" id="reportForm" class="search">

					<div id="filterContainer">
						<div id="filterLeftColumn">
							<div id="startDate">
								Start Date:
								<input id="dateFilterStart" name="dateFilterStart" value="{$selectedDateStart}" />
							</div>
							<div id="roles">
								Stores: <br/>
								<select id="storesFilter[]" name="storesFilter[]" multiple="multiple" size="5" class="multiSelectFilter">
										{section name=resultsStoresFilterRow loop=$resultsStoresFilter}
												<option value="{$resultsStoresFilter[resultsStoresFilterRow]}" {if $resultsStoresFilter[resultsStoresFilterRow]|in_array:$selectedStoresFilter}selected='selected'{/if}>{$resultsStoresFilter[resultsStoresFilterRow]}</option>
										{/section}
								</select>
							</div>
						</div>
						<div id="filterRightColumn">
							<div id="endDate">
								End Date:
								<input id="dateFilterEnd" name="dateFilterEnd" value="{$selectedDateEnd}" />
							</div>
							<div class="filterPlaceholder">

							</div>
						</div>
						<div class="divClear"></div>
						<input type="submit" id="filterSubmit" value="Go">
					</div>
					{if $chartPath}
					<div id="chart">
						<img src="{$chartPath}" />
						</div>
					{/if}

					<div id="reportSorting">
						{if $pageLinks.all}
							{translate text="Showing"}
							<b>{$recordStart}</b> - <b>{$recordEnd}</b>
							{translate text='of'} <b>{$recordCount}</b>
							{if $searchType == 'basic'}{translate text='for search'}: <b>'{$lookfor|escape:"html"}'</b>,{/if}
						{/if}

						<select name="reportSort" id="reportSort" onchange="this.form.submit();">
							{foreach from=$sortList item=sortListItem key=keyName}
								<option value="{$sortListItem.column}" {if $sortListItem.selected} selected="selected"{/if} >Sort By {$sortListItem.displayName}</option>
							{/foreach}
						</select>

						<b>Results Per Page: </b>
						<select name="itemsPerPage" id="itemsPerPage" onchange="this.form.submit();">
							{foreach from=$itemsPerPageList item=itemsPerPageItem key=keyName}
								<option value="{$itemsPerPageItem.amount}" {if $itemsPerPageItem.selected} selected="selected"{/if} >{$itemsPerPageItem.amount}</option>
							{/foreach}
						</select>
					</div>

				<table border="0" width="100%" class="datatable">
				 <tr>
					<th align="center">Store</th>
					<th align="center">Purchases</th>
				 </tr>
				{section name=resultsPurchasesRow loop=$resultsPurchases}
						<tr {if $smarty.section.nr.iteration is odd} bgcolor="#efefef"{/if}>
								<td>
										{$resultsPurchases[resultsPurchasesRow].Store}</td>
								<td>{$resultsPurchases[resultsPurchasesRow].Purchases}
								</td>
						</tr>
				{sectionelse}
				<tr><td align="center" colspan="4"><br /><b>No Purchases </b> <br /> </td></tr>
				{/section}
				</table>

							{if $pageLinks.all}<div class="pagination" id="pagination-bottom">Page: {$pageLinks.all}</div>{/if}



				<div class="exportButton">
				<input type="submit" id="exportToExcel" name="exportToExcel" value="Export to Excel">
				</div>
				</form>
			</div>


			{else}
				You must login to view this information. Click <a href="{$path}/MyAccount/Login">here</a> to login.
			{/if}
	</div>
