{strip}
	<div class="row">
		<div class="col-xs-12">
			<h1 id="pageTitle">{$pageTitleShort}</h1>
		</div>
	</div>
	{if isset($duplicateUsers)}
		<div class="row">
			<div class="col-xs-12">
				<h2>{translate text="Barcodes with Duplicates" isAdminFacing=true}</h2>
			</div>
		</div>
		<div class="row">
			<div class="col-xs-12">
				{translate text="Barcodes" isAdminFacing=true}
			</div>
		</div>
		{foreach from=$duplicateUsers item=barcodeInfo}
			<div class="row">
				<div class="col-xs-12">
					{$barcodeInfo.cat_username}
				</div>
			</div>
		{/foreach}
	{/if}
{/strip}