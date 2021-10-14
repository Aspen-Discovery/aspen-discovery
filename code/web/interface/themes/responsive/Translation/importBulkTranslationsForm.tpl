{strip}
<div id="main-content" class="col-md-12">
	<h1>{translate text="Import Translations" isAdminFacing=true}</h1>

	{if !empty($error)}
		<div class="alert-warning alert">
			{translate text=$error isAdminFacing=true}
		</div>
	{/if}

	<form class="form" method="post" enctype="multipart/form-data">
		<div class="form-group">
			<div class="input-group">
				<label class="input-group-btn">
					<span class="btn btn-primary">
						{translate text="Select a file to import with %1% translations" 1=$userLang->displayName isAdminFacing=true}&hellip; <input type="file" style="display: none;" name="importFile" id="importFile">
					</span>
				</label>
				<input type="text" class="form-control" id="importFile-label" readonly>
			</div>
		</div>

		<div class="form-group">
			<button type="submit" name="submit" class="btn btn-primary">{translate text="Import" isAdminFacing=true}</button>
		</div>
	</form>
	<script type="application/javascript">
		{literal}
		$(document).on('change', ':file', function() {
			var input = $(this);
			var label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
			$("#importFile-label").val(label);
		});
		{/literal}
	</script>
</div>
{/strip}