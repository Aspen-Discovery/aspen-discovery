{strip}
	<div class="row">
		<div class="col-xs-12">
			<h1 id="pageTitle">{$pageTitleShort}</h1>
		</div>
	</div>
	{if isset($mappingResults)}
		<div class="row">
			<div class="col-xs-12">
				<div class="alert {if $mappingResults.success}alert-success{else}alert-danger{/if}">
					{$mappingResults.message}
				</div>
			</div>
		</div>
	{else}
		<div class="row">
			<div class="col-xs-12">
				<div class="alert alert-info">{translate text="This tool can be used to map biblio numbers after a migration.  A CSV File must be provided where the first column is the original number and the second is the new number." isAdminFacing=true}</div>
			</div>
		</div>
		<form enctype="multipart/form-data" id='mapBiblioNumbersForm' method="post" role="form" aria-label="{translate text="Mapping Form" isAdminFacing=true inAttribute=true}">
			<div class="form-group">
				<div class="input-group">
					<label class="input-group-btn">
						<span class="btn btn-primary">
							{translate text="Select Mapping File" isAdminFacing=true}&hellip; <input type="file" style="display: none;" name="mappingFile" id="mappingFile">
						</span>
					</label>
					<input type="text" class="form-control" id="selected-file-label" readonly>
				</div>
			</div>
			<small id="mappingFileHelp" class="form-text text-muted">{translate text="A CSV file should be uploaded with the first column the old id and the second column the new id." isAdminFacing=true}</small>
			</div>
			<div class="form-group">
				<button type="submit" name="submit" value="mapBiblios" class="btn btn-primary">{translate text="Map Biblio Numbers" isAdminFacing=true}</button>
			</div>
		</form>
	{/if}
	<script type="application/javascript">
		{literal}
		$("#mapBiblioNumbersForm").validate();
		$(document).on('change', ':file', function() {
			var input = $(this);
			var label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
			$("#selected-file-label").val(label);
		});
		{/literal}
	</script>
{/strip}