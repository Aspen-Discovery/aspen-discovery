{strip}
<form enctype="multipart/form-data" name="uploadListCoverForm" id="uploadListCoverForm" method="post" action="/MyAccount/MyList/{$id}">
	<input type="hidden" name="id" value="{$id}"/>
	<input type="hidden" name="method" value="uploadListCover"/>
	<div class="form-group">
		<div class="input-group">
			<label class="input-group-btn">
				<span class="btn btn-primary">
					{translate text="Select Cover" isAdminFacing=true}&hellip; <input type="file" style="display: none;" name="coverFile" id="coverFile">
				</span>
			</label>
			<input type="text" class="form-control" id="selected-cover-label" readonly>
		</div>
		<small id="coverFileHelp" class="form-text text-muted">{translate text="JPG, GIF, and PNG Files can be uploaded." isAdminFacing=true}</small>
	</div>
</form>
	<script type="application/javascript">
		{literal}
		$("#uploadListCoverForm").validate({
			submitHandler: function(){
				AspenDiscovery.Lists.uploadListCover("{/literal}{$id}{literal}")
			}
		});
		$(document).on('change', ':file', function() {
			var input = $(this);
			var label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
			$("#selected-cover-label").val(label);
		});
		{/literal}
	</script>
{/strip}