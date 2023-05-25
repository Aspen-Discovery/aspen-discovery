{strip}
<form enctype="multipart/form-data" name="uploadCoverFormByURL" id="uploadCoverFormByURL" method="post" action="/GroupedWork/AJAX">
	<input type="hidden" name="groupedWorkId" value="{$groupedWorkId}"/>
	<input type="hidden" name="recordType" value="{$recordType}"/>
	<input type="hidden" name="recordId" value="{$recordId}"/>
	<input type="hidden" name="method" value="uploadCoverByURL"/>
	<div class="form-group">
		<label for="coverFileURL">{translate text="Image URL" isAdminFacing=true}</label>
		<input type="text" class="form-control" name="coverFileURL" id="coverFileURL">
	</div>
	<div id="uploadOption" class="form-group">
		<div class="controls">
			<select name="uploadOption" id="uploadOption" class="form-control">
				{if $recordType == "grouped_work"}
					<option value="alldefault">{translate text="Also Apply to All Records in Grouped Work with Default Covers" isAdminFacing=true}</option>
					<option value="groupedwork">{translate text="Apply to Grouped Work Only" isAdminFacing=true}</option>
					<option value="all">{translate text="Also Apply to All Records in Grouped Work" isAdminFacing=true}</option>
				{else}
					<option value="andgrouped">{translate text="Also Apply to Grouped Work" isAdminFacing=true}</option>
					<option value="recordonly">{translate text="Apply to This Record Only" isAdminFacing=true}</option>
				{/if}
			</select>
		</div>
	</div>
		<small id="coverFileHelp" class="form-text text-muted">{translate text="JPG/JPEG, GIF, and PNG Files can be uploaded." isAdminFacing=true}</small>
</form>
	<script type="application/javascript">
		{literal}
		$("#uploadCoverFormByURL").validate({
			submitHandler: function(){
				AspenDiscovery.GroupedWork.uploadCoverByURL("{/literal}{$groupedWorkId}{literal}", "{/literal}{$recordType}{literal}", "{/literal}{$recordId}{literal}")
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