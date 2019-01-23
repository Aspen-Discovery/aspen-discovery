{strip}
	<div id="main-content" class="col-md-12">
		{if $loggedIn}
			<h1>School Overdue Report</h1>
			{foreach from=$errors item=error}
				<div class="error">{$error}</div>
			{/foreach}
			<form class="form form-inline">
{* TO DO: SORT REPORT LABELS *}
{* TO DO: ADD SCHOOL NAMES TO  REPORT LABELS *}
				<select name="selectedReport" id="selectedReport" class="form-control input-sm">
					{foreach from=$availableReports item=curReport key=reportLocation}
						<option value="{$reportLocation}" {if $curReport==$selectedReport}selected="selected"{/if}>{$curReport|regex_replace:"/^../":""} created {$reportDateTime}</option>
					{/foreach}
				</select>
				&nbsp;

				<select name="showOverdueOnly" id="showOverdueOnly" class="form-control input-sm">
					<option value="overdue" {if $showOverdueOnly}selected="selected"{/if}>Overdue Items</option>
					<option value="checkedOut" {if !$showOverdueOnly}selected="selected"{/if}>All Checked Out</option>
				</select>
				&nbsp;
				<input type="submit" name="showData" value="Show Data" class="btn btn-sm btn-primary"/>
				&nbsp;
				<input type="button" name="printSlips" value="Print Slips" class="btn btn-sm btn-primary" onclick="{literal} var x = document.querySelectorAll('.overdueSlip'); var i; for (i = 0; i < x.length; i++) { x[i].style.pageBreakBefore = 'auto'; } window.print(); {/literal}" />
				&nbsp;
				<input type="button" name="printPages" value="Print Pages" class="btn btn-sm btn-primary" onclick="{literal} var x = document.querySelectorAll('.overdueSlip'); var i; for (i = 0; i < x.length; i++) { x[i].style.pageBreakBefore = 'always'; } window.print(); {/literal}" />
				&nbsp;
			</form>
			{if $reportData}
				<br/>
				<p>
					There are a total of <strong>{$reportData|@count}</strong> overdue items.
				</p>
{literal}
<style type="text/css">
	div.overdueSlip {
		border-top: 1px dashed #ccc !important;
		display: table !important;
		margin: 1em;
		padding-top: 1em;
		page-break-inside: avoid;
		width: 7in;
		margin-left: 0;
		margin-right: auto;
	}
	div.overdueSlip div.patronHeader {
		display: table !important;		
		width: 7in;
		margin-left: auto;
		margin-right: auto;
	}
	div.overdueSlip div.patronHeader div {
		display: table-cell !important;
		font-weight: bold;
	}
	div.P_TYPE {
		width: 1in !important;
	}
	div.HOME_ROOM {
		width: 2in !important;
	}
	div.PATRON_NAME {
		width: 3in !important;
	}
	div.P_BARCODE {
		text-align: right;
		width: 1in !important;
	}
	div.overdueSlip div.overdueRecordTable {
		display: table-row !important;		
		width: 7in;
		margin-left: auto;
		margin-right: auto;
	}
	div.overdueSlip div.overdueRecordTable div.overdueRecordTableMessage {
		width: 7in;
		margin-left: auto;
		margin-right: auto;
		text-align: left;
	}
	div.overdueSlip div.overdueRecordTable div.overdueRecord {
		width: 7in !important;
		margin-left: auto !important;
		margin-right: auto !important;
	}
	div.overdueSlip div.overdueRecordTable div.overdueRecord div {
		display: table-cell !important;
	}
	div.SYSTEM {
		width: .5in !important;
	}
	div.ITEM_ID {
		width: 1.25in !important;
	}
	div.CALL_NUMBER {
		width: 1.25in !important;;
	}
	div.TITLE {
		width: 2.5in !important;
	}
	div.DUE_DATE {
		width: .75in !important;
	}
	div.PRICE {
		text-align: right;
		width: .75in !important;
	}
</style>
{/literal}

{assign var=previousPatron value=0}
{foreach from=$reportData item=dataRow name=overdueData}
	{if $dataRow[6] != $previousPatron}
		{if $smarty.foreach.overdueData.index > 0}</div></div>{/if}
		<div class="overdueSlip">
			<div class="patronHeader">
				<div class="P_TYPE">{$dataRow[3]|replace:' student':''}</div>
				<div class="HOME_ROOM">{$dataRow[4]|lower|capitalize:true}</div>
				<div class="PATRON_NAME">{$dataRow[5]|upper}</div>
				<div class="P_BARCODE">{$dataRow[6]}</div>
			</div>
			<div class="overdueRecordTable">
				<div class="overdueRecordTableMessage">
					The items below are
					{if $showOverdueOnly}&nbsp;overdue{/if}
					{if !$showOverdueOnly}&nbsp;checked out{/if}
					. &nbsp; 
					Please return them to your library. This notice was created {$reportDateTime}<br>
					Check your account online at https://school.library.nashville.org/
				</div>
				<div class="overdueRecord">
                                        <div class="SYSTEM">SYSTEM</div>
                                        <div class="ITEM_ID">BARCODE</div>
                                        <div class="CALL_NUMBER">CALL NUMBER</div>
                                        <div class="TITLE">TITLE</div>
                                        <div class="DUE_DATE">DUE DATE</div>
                                        <div class="PRICE">PRICE</div>
				</div>
		{assign var=previousPatron value=$dataRow[6]}
	{/if}
			<div class="overdueRecord">
				<div class="SYSTEM">{$dataRow[7]|replace:"1":"NPL"|replace:"2":"MNPS"}</div>
                                <div class="ITEM_ID">{$dataRow[13]}</div>
				<div class="CALL_NUMBER">{$dataRow[8]}</div>
				<div class="TITLE">{$dataRow[9]|regex_replace:"/ *\/ *$/":""}</div>
				<div class="DUE_DATE">{$dataRow[10]}</div>
				<div class="PRICE">{$dataRow[11]|regex_replace:"/^ *0\.00$/":"10.00"}</div>
			</div>	
{/foreach}
			{/if}

		{else}
			You must login to view this information. Click <a href="{$path}/MyAccount/Login">here</a> to login.
		{/if}
	</div>
{/strip}
