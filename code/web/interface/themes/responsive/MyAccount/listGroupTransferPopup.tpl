<div>
	{if !empty($hasListValidationError) && $hasListValidationError}
		<div id="listValidationError">
			<p class="alert alert-danger">
				{translate text="The barcode/username is not a valid staff user." isAdminFacing=true}
			</p>
		</div>
	{/if}
	<form method="post" name="transferListGroupForm" id="transferListGroupForm" action="/MyAccount/MyList" class="form">
		<div>
			<input type="hidden" name="listGroupId" id="listGroupId" value="{$listGroupId}"/>
			<div class="form-group">
				<label for="newListGroupOwner" class="control-label">
					{translate text="Enter the barcode or local administrator username for the staff user to whom you would like to transfer this list group" isAdminFacing=true}
				</label>
				<input type="text" id="newListGroupOwner" name="newListGroupOwner" value="" class="form-control required">
			</div>
		</div>
	</form>
</div>
<script type="text/javascript">
	{literal}
	$("#transferListGroupForm").validate({
		submitHandler: function(){
			AspenDiscovery.Lists.listGroupTransferValidation()
		}
	});
	{/literal}
</script>
