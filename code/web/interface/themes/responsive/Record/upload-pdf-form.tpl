{strip}
<form enctype="multipart/form-data" name="uploadPDFForm" id="uploadPDFForm" method="post">
	<input type="hidden" name="id" value="{$id}"/>
	<input type="hidden" name="method" value="uploadPDF"/>
	<div class="form-group">
		<div class="form-group">
			<label for="title">
				{translate text="Title" isAdminFacing=true}<span class="required-input">*</span>
			</label>
			<input type="text" class="form-control required" id="title" name="title" maxlength="255">
		</div>
		<div class="input-group">
			<label class="input-group-btn">
				<span class="btn btn-primary">
					{translate text="Select PDF"}&hellip; <input type="file" style="display: none;" name="pdfFile" id="pdfFile">
				</span>
			</label>
			<input type="text" class="form-control" id="selected-pdf-label" readonly>
		</div>
		<small id="pdfFileHelp" class="form-text text-muted">{translate text="PDF must be %1%MB or less." 1=$max_file_size isAdminFacing=true}</small>
	</div>
</form>
	<script type="application/javascript">
		{literal}
		$("#uploadPDFForm").validate({
			submitHandler: function(){
				AspenDiscovery.Record.uploadPDF("{/literal}{$id}{literal}");
			}
		});
		$(document).on('change', ':file', function() {
			var input = $(this);
			var label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
			$("#selected-pdf-label").val(label);
		});
		{/literal}
	</script>
{/strip}