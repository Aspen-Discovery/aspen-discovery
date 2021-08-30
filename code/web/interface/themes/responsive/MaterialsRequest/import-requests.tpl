{strip}
<form enctype="multipart/form-data" name="importRequestsForm" id="importRequestsForm" method="post" action="/MaterialsRequest/AJAX">
	<input type="hidden" name="method" value="importRequests"/>
	<div class="alert alert-info">
		{translate text="This will allow you to import requests that have been previously exported.&nbsp;There is generally no reason to do this unless Aspen is being moved to a new system." isAdminFacing=true}
	</div>
	<div class="form-group">
		<div class="input-group">
			<label class="input-group-btn">
				<span class="btn btn-primary">
					{translate text="Select File To Upload" isAdminFacing=true}&hellip; <input type="file" style="display: none;" name="exportFile" id="exportFile">
				</span>
			</label>
			<input type="text" class="form-control" id="selected-export-label" readonly>
		</div>
		<small id="exportFileHelp" class="form-text text-muted">{translate text="XLS Files previously exported from Pika or Aspen can be uploaded." isAdminFacing=true}</small>
	</div>
</form>
	<script type="application/javascript">
		{literal}
		$("#importRequestsForm").validate({
			submitHandler: function(){
				AspenDiscovery.MaterialsRequest.importRequests()
			}
		});
		$(document).on('change', ':file', function() {
			var input = $(this);
			var label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
			$("#selected-export-label").val(label);
		});
		{/literal}
	</script>
{/strip}