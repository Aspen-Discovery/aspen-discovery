{strip}
	<div id="main-content" class="col-md-12">
		<h1>Upload MARC Record</h1>
		{if !empty($error)}
			<div class="alert alert-warning">
				{$error}
			</div>
		{elseif !empty($message)}
			<div class="alert alert-info">
                {$message}
			</div>
		{/if}
		<form enctype="multipart/form-data" name="uploadMarc" method="post">
			<input type="hidden" name="id" value="{$id}"/>
			<div class="form-group">
				<div class="input-group">
					<label class="input-group-btn">
					<span class="btn btn-primary">
						{translate text="Select MARC File"}&hellip; <input type="file" style="display: none;" name="marcFile" id="marcFile">
					</span>
					</label>
					<input type="text" class="form-control" id="selected-marc-label" readonly>
				</div>
				<small id="marcFileHelp" class="form-text text-muted">Zip Files, GZip Files, MRC, and MARC Files can be uploaded.</small>
			</div>
			<div class="form-group">
				<label for="replaceExisting"><input type="checkbox" name="replaceExisting" id="replaceExisting"> Replace Existing Files?</label>
			</div>
			<div class="form-group">
				<button type="submit" class="btn btn-primary">Upload File</button>
			</div>
		</form>
	</div>
	<script type="application/javascript">
		{literal}
		$(document).on('change', ':file', function() {
			var input = $(this);
			var label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
			$("#selected-marc-label").val(label);
		});
		{/literal}
	</script>
{/strip}