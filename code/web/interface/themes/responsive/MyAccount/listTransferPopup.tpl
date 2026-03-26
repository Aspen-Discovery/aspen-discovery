<div>
	{if !empty($hasListValidationError) && $hasListValidationError}
		<div id="listValidationError">
			<p class="alert alert-danger">
                {translate text="The barcode/username is not a valid staff user." isAdminFacing=true}
			</p>
		</div>
	{/if}
	<form method="post" name="transferListForm" id="transferListForm" action="/MyAccount/MyList" class="form">
		<div>
			<input type="hidden" name="listId" id="listId" value="{$listId}"/>
			<div class="form-group">
				<label for="newListOwner" class="control-label">
                    {translate text="Enter the barcode or local administrator username for the staff user to whom you would like to transfer this list" isAdminFacing=true}
				</label>
				<input type="text" id="newListOwner" name="newListOwner" value="" class="form-control required">
			</div>
		</div>
	</form>
</div>
<script type="text/javascript">
    {literal}
	$("#transferListForm").validate({
		submitHandler: function(){
			AspenDiscovery.Lists.listTransferValidation()
		}
	});
    {/literal}
</script>