{strip}
<form enctype="multipart/form-data" name="uploadListCoverFormByURL" id="uploadListCoverFormByURL" method="post" action="/MyAccount/MyList/{$id}">
	<input type="hidden" name="id" value="{$id}"/>
	<input type="hidden" name="method" value="uploadListCoverByURL"/>
	<div class="form-group">
		<label for="coverFileURL">{translate text="Image URL" isAdminFacing=true}</label>
		<input type="text" class="form-control" name="coverFileURL" id="coverFileURL">
	</div>
		<small id="coverFileHelp" class="form-text text-muted">{translate text="JPG/JPEG, GIF, and PNG Files can be uploaded." isAdminFacing=true}</small>
</form>
	<script type="application/javascript">
		{literal}
		$("#uploadListCoverFormByURL").validate({
			submitHandler: function(){
				AspenDiscovery.Lists.uploadListCoverByURL("{/literal}{$id}{literal}")
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