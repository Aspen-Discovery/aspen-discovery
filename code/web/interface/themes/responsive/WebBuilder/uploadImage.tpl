{strip}
	<form enctype="multipart/form-data" name="uploadImageForm" id="uploadImageForm" method="post" action="/WebBuilder/AJAX">
		<input type="hidden" name="editorName" id="editorName" value="{$editorName}"/>
		<input type="hidden" name="method" value="uploadImage"/>
		<div class="form-group">
			<div class="input-group">
				<label class="input-group-btn">
				<span class="btn btn-primary">
					{translate text="Select Image" isAdminFacing=true}&hellip; <input type="file" style="display: none;" name="imageFile" id="imageFile">
				</span>
				</label>
				<input type="text" class="form-control" id="selected-image-label" readonly>
			</div>
			<small id="imageFileHelp" class="form-text text-muted">{translate text="JPG, GIF, and PNG Files can be uploaded." isAdminFacing=true}</small>
		</div>
	</form>
	<script type="application/javascript">
        {literal}
		$("#uploadCoverForm").validate({
			submitHandler: function(){
				AspenDiscovery.WebBuilder.doImageUpload();
			}
		});
		$(document).on('change', ':file', function() {
			var input = $(this);
			var label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
			$("#selected-image-label").val(label);
		});
        {/literal}
	</script>
{/strip}