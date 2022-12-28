{strip}
	<div class="row">
		<div class="col-xs-12">
			<h1 id="pageTitle">{$pageTitleShort}</h1>
		</div>
	</div>
    {if isset($reloadResults)}
		<div class="row">
			<div class="col-xs-12">
				<h2>{translate text="Reload Results" isAdminFacing=true}</h2>
			</div>
			<div class="col-xs-12">
				{foreach from=$reloadResults item=reloadResult}
					{if !empty($reloadResult.success)}
						<div class="alert alert-success">{$reloadResult.barcode}</div>
					{else}
						<div class="alert alert-danger">{$reloadResult.barcode}</div>
					{/if}
				{/foreach}
			</div>
		</div>
	{/if}
	<div class="row">
		<div class="col-xs-12">
			<div class="alert alert-info">{translate text="Enter the barcode(s) for the users to reset reading history for, enter each barcode on it's own line." isAdminFacing=true}</div>
		</div>
	</div>
	<form name="resetReadingHistory" method="post" enctype="multipart/form-data" class="form-horizontal">
		<fieldset>
			<input type="hidden" name="objectAction" value="processNewAdministrator">
			<div class="row form-group">
				<label for="barcodes" class="col-sm-2 control-label">{translate text='Barcode(s)' isAdminFacing=true}</label>
				<div class="col-sm-10">
					<textarea name="barcodes" id="barcodes" class="form-control"></textarea>
				</div>
			</div>

			<div class="form-group">
				<div class="controls col-sm-offset-2 col-sm-2">
					<input type="submit" name="submit" value="{translate text="Reset Reading History" inAttribute=true isAdminFacing=true}" class="btn btn-primary">
				</div>
			</div>
		</fieldset>
	</form>
{/strip}