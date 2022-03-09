{strip}
	<form class="form" role="form">
		<input type="hidden" name="patronId" value="{$patronId}" id="patronId">
		<input type="hidden" name="overDriveId" value="{$overDriveId}" id="overDriveId">
		<div class="form-group">
			<label for="reactivationDate">{translate text="Select the date when you want the hold thawed." isPublicFacing=true}</label>
			<input type="date" name="reactivationDate" id="reactivationDate" class="form-control input-sm" min="{$smarty.now|date_format:"%Y-%m-%d"}">
		</div>
		<p class="alert alert-info">
			{translate text="If a date is not selected, the hold will be frozen until you thaw it." isPublicFacing=true}
		</p>
	</form>
	<script	type="text/javascript">
		{literal}
		$(function(){
			$(".form").validate({
				submitHandler: function(){
					AspenDiscovery.OverDrive.doFreezeHoldWithReactivationDate('#doFreezeHoldWithReactivationDate');
				}
			});
		});
		{/literal}
	</script>
{/strip}