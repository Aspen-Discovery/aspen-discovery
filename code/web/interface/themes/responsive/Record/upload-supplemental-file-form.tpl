{strip}
<form enctype="multipart/form-data" name="uploadSupplementalFileForm" id="uploadSupplementalFileForm" method="post">
	<input type="hidden" name="id" value="{$id}"/>
	<input type="hidden" name="method" value="uploadSupplementalFile"/>
	<div class="form-group">
		<div class="form-group">
			<label for="title">
				{translate text="Title" isPublicFacing=true}<span class="required-input">*</span>
			</label>
			<input type="text" class="form-control required" id="title" name="title" maxlength="255">
		</div>
		<div class="input-group">
			<label class="input-group-btn">
				<span class="btn btn-primary">
					{translate text="Select File"}&hellip; <input type="file" style="display: none;" name="supplementalFile" id="supplementalFile">
				</span>
			</label>
			<input type="text" class="form-control" id="selected-file-label" readonly>
		</div>
		<small id="supplementalFileHelp" class="form-text text-muted">File must be {$max_file_size}MB or less and must be one of the following types: <br/>.CSV, .DOC, .DOCX, .ODP, .ODS, .ODT, .PDF, .PPT, .PPTX, .XLS, .XLSX</small>
	</div>
</form>
	<script type="application/javascript">
		{literal}
		$("#uploadSupplementalFileForm").validate({
			submitHandler: function(){
				AspenDiscovery.Record.uploadSupplementalFile("{/literal}{$id}{literal}");
			}
		});
		$(document).on('change', ':file', function() {
			var input = $(this);
			var label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
			$("#selected-file-label").val(label);
		});
		{/literal}
	</script>
{/strip}