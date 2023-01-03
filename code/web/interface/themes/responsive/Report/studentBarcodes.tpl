{strip}
<script src="/js/jsBarcode/JsBarcode.all.min.js"></script>
<script>
function makeBarcodes() {ldelim}
var patron = [];
{foreach from=$reportData item=dataRow name=barcodeData}
	patron[{$smarty.foreach.barcodeData.index}] = [];
	{foreach from=$dataRow item=fieldValue name=barcodeFieldData}
		patron[{$smarty.foreach.barcodeData.index}][{$smarty.foreach.barcodeFieldData.index}] = '{$fieldValue|replace:'\'':'\\\''}';
	{/foreach}
{/foreach}
{literal}
	var printdiv = document.getElementById("printish");
	var i=0;
	for (i=0;i<patron.length;i++) {
		if ((i+1) % 30 == 1) {
			var pagediv = document.createElement("div");
			pagediv.setAttribute("id", "page"+i/30);
			pagediv.setAttribute("class", "page");
			printdiv.appendChild(pagediv);
		}
		var labeldiv = document.createElement("div");
		labeldiv.setAttribute("id", "label"+i);
		labeldiv.setAttribute("class", "avery5160");
		pagediv.appendChild(labeldiv);
		if (!patron[i][7]) {
		} else {
			var namediv = document.createElement("div");
			namediv.setAttribute("id", "name"+i);
			namediv.setAttribute("class", "name");
			labeldiv.appendChild(namediv);
			var nametext = document.createTextNode(patron[i][8].toUpperCase() + ', ' + patron[i][9] + ' ' + patron[i][10] + ' ' + patron[i][11])
			namediv.appendChild(nametext);
			var barcodeWidth = 12 / (patron[i][7].length + 2);
			if (barcodeWidth > 1.5) { barcodeWidth = 1.5 ; }
			var svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
			svg.setAttribute("id", "svg"+i);
			labeldiv.appendChild(svg);
			JsBarcode("#svg"+i, patron[i][7], {
				format: "code39",
				displayValue: true,
				font: "akagi-pro, Helvetica, Arial, sans-serif",
				fontSize: 14,
				textAlign: "left",
				textMargin: 2,
				textPosition: "top",
				height: 40,
				margin: 0,
				width: barcodeWidth
			});
		}
	}
}
document.addEventListener("DOMContentLoaded", function(event) {
	makeBarcodes();
});
{/literal}
</script>
{literal}
<style>
.avery5160 {
	/* Avery 5160 labels */
	width: 2.625in !important; 
	height: 1in !important; 
	margin: 0in .125in 0in 0in !important;
	padding: .425in 0 0 .475in !important;
	float: left;
	display: inline-block;
	text-align: left;
	overflow: hidden;
	outline: 1px dotted;  /*outline doesn't occupy space like border does */
}
.name {
	height: 20px;
	overflow: hidden;
	text-align: left;
}

@media print {
	.avery5160 {
		outline: 0px; 
	}
	#footer-container, #header-wrapper,#horizontal-menu-bar-wrapper,#side-bar,#system-message-header,.breadcrumbs {
		display: none;
	}
	.container
	, #main-content-with-sidebar
	, #main-content
	, #printish {
		clear: both !important;
		left: 0px !important;
		margin: 0 !important;;
		padding: 0 !important;
		height: 10.625in !important;
		width: 8.25in !important;
	}
	.page {
		page-break-after: always !important;
	}
	#reportFrontMatter {
		display: none;
	}
}

@page {
	size: 8.5in 11in !important;
/*	margin: .375in .125in .375in !important; */
	margin: .375in .125in 0in !important;
}
</style>
{/literal}

	<div id="main-content" class="col-md-12">
	<div id="reportFrontMatter">
		{if !empty($loggedIn)}
			<h1>School Barcodes Report</h1>
		{if isset($errors)}
			{foreach from=$errors item=error}
				<div class="error">{$error}</div>
			{/foreach}
		{/if}
			<form class="form form-inline">
				{if !empty($selectedLocation)}
					{html_options name=location options=$locationLookupList selected=$selectedLocation class="form-control input-sm" onchange="this.form.submit()"}
				{else}
					{html_options name=location options=$locationLookupList class="form-control input-sm" onchange="this.form.submit()"}
				{/if}
				{if !empty($selectedHomeroom)}
					{html_options name=homeroom options=$homeroomLookupList selected=$selectedHomeroom class="form-control input-sm" onchange="this.form.submit()"}
				{else}
					{html_options name=homeroom options=$homeroomLookupList class="form-control input-sm" onchange="this.form.submit()"}
				{/if}
				<input type="button" name="printSlips" value="Print Labels" class="btn btn-sm btn-primary" onclick="{literal} var x = document.querySelectorAll('.avery5160'); var i; for (i = 0; i < x.length; i++) { x[i].style.pageBreakBefore = 'auto'; } window.print(); {/literal}" />
				&nbsp;
			</form>
			{if !empty($reportData)}
				<br/>
				<p>
					Homeroom has a total of <strong>{$reportData|@count}</strong> patron barcodes.
				</p>
				</div>
				<div id="printish"></div>
			{/if}
		{else}
			</div>
			You must login to view this information. Click <a href="{$path}/MyAccount/Login">here</a> to login.
		{/if}
	</div>
{/strip}
