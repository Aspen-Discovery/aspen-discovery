{strip}
	<div class="row">
		<div class="col-xs-12">
			<h1 id="pageTitle">{$pageTitleShort}</h1>
		</div>
	</div>
    {if isset($results)}
		<div class="row">
			<div class="col-xs-12">
				<div class="alert {if !empty($results.success)}alert-success{else}alert-danger{/if}">
                    {$results.message}
				</div>
			</div>
		</div>
    {else}
		<div class="row">
			<div class="col-xs-12">
				<div class="alert alert-info">{translate text="This tool can be used to preload preferred pickup locations during migration.  A CSV File must be provided where the second column is the barcode, the third is the preferred pickup location, and the fourth is the home location." isAdminFacing=true}</div>
			</div>
		</div>
		<form enctype="multipart/form-data" id='loadPreferredPickupLocationForm' method="post" role="form" aria-label="{translate text="Preferred Pickup Location Form" isAdminFacing=true inAttribute=true}">
			<div class="form-group">
				<div class="input-group">
					<label class="input-group-btn">
						<span class="btn btn-primary">
							{translate text="Select Preferred Pickup Location File" isAdminFacing=true}&hellip; <input type="file" style="display: none;" name="preferredPickupLocationFile" id="preferredPickupLocationFile">
						</span>
					</label>
					<input type="text" class="form-control" id="selected-file-label" readonly>
				</div>
			</div>
			<small id="preferredPickupLocationFileHelp" class="form-text text-muted">{translate text="A CSV file should be uploaded where the second column is the barcode, the third is the preferred pickup location, and the fourth is the home location." isAdminFacing=true}</small>
			</div>
			<div class="form-group">
				<button type="submit" name="submit" value="mapBiblios" class="btn btn-primary">{translate text="Load Preferred Pickup Locations" isAdminFacing=true}</button>
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