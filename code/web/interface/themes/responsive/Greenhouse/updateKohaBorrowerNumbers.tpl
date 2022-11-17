{strip}
	<div class="row">
		<div class="col-xs-12">
			<h1 id="pageTitle">{$pageTitleShort}</h1>
		</div>
	</div>
	{if !empty($setupErrors)}
		<div class="row">
			<div class="col-xs-12">
				{foreach from=$setupErrors item=setupError}
					<div class="alert alert-danger">
						{$setupError}
					</div>
				{/foreach}
			</div>
		</div>
	{else}
		{if isset($results)}
			<div class="row">
				<div class="col-xs-12">
					<div class="alert {if $results.success}alert-success{else}alert-danger{/if}">
						{$results.message}
						{foreach from=$results.errors item=error}
							<div>{$error}</div>
						{/foreach}
					</div>
				</div>
			</div>
		{else}
			<div class="row">
				<div class="col-xs-12">
					<div class="alert alert-info">{translate text="This tool can be used to update borrower numbers based on barcode number after a migration for Koha installs." isAdminFacing=true}</div>
				</div>
			</div>
			<form enctype="multipart/form-data" id='mapBiblioNumbersForm' method="post" role="form" aria-label="{translate text="Update Borrower Numbers Form" isAdminFacing=true inAttribute=true}">
				<div class="form-group">
					<button type="submit" name="submit" value="mapBiblios" class="btn btn-primary">{translate text="Update Borrower Numbers" isAdminFacing=true}</button>
				</div>
			</form>
		{/if}
	{/if}
{/strip}