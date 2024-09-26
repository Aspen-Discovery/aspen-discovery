<div class="row" style="padding-top: 1em">
	<div class="col-sm-4">
		<label for="newStatus" class="control-label">{translate text="Change status of selected to" isAdminFacing=true}</label>
	</div>
	<div class="col-sm-8">
		<select name="newStatus" id="newStatus" class="form-control">
			<option value="unselected">{translate text="Select One" isAdminFacing=true}</option>
			{foreach from=$availableStatuses item=statusLabel key=status}
				<option value="{$status}">{translate text="$statusLabel"  isAdminFacing=true inAttribute=true}</option>
			{/foreach}
		</select>

		<button class="btn btn-default" type="submit" onclick="$('#objectAction').val('updateRequestStatus');">{translate text="Update Status" isAdminFacing=true}</button>
	</div>
</div>