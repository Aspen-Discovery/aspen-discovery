{strip}
	<form class="form" role="form">
		<input type="hidden" name="holdId" value="{$holdId}" id="holdId">
		<input type="hidden" name="patronId" value="{$patronId}" id="patronId">
		<input type="hidden" name="recordId" value="{$recordId}" id="recordId">
		<input type="hidden" name="isAlreadyFrozen" value="{$isAlreadyFrozen}" id="isAlreadyFrozen">
		<div class="form-group">
			<label for="reactivationDate">{translate text="Select the date when you want the hold thawed." isPublicFacing=true}</label>
			{* Calculate max freeze date from hold placement date if available, otherwise use default *}
			{if $allowMaxDaysToFreeze > -1}
				{if !empty($holdCreateDate)}
					{assign var="maxFreezeTimestamp" value=$holdCreateDate+($allowMaxDaysToFreeze*86400)}
				{else}
					{assign var="maxFreezeTimestamp" value=$maxDaysToFreeze}
				{/if}
			{/if}
			<input type="date" name="reactivationDate" id="reactivationDate" min="{$smarty.now|date_format:"%Y-%m-%d"}" {if $allowMaxDaysToFreeze > -1}max="{$maxFreezeTimestamp|date_format:"%Y-%m-%d"}"{/if} class="form-control{if empty($reactivateDateNotRequired)} required{/if}">
		</div>
		{if !empty($reactivateDateNotRequired) && empty($maxFreezeTimestamp)}
			<p class="alert alert-info">
				{translate text="If a date is not selected, the hold will be frozen until you thaw it." isPublicFacing=true}
			</p>
		{/if}
		{if !empty($maxFreezeTimestamp)}
			<p class="alert alert-info">
				{translate text="If a date is not selected, the hold will be frozen until {$maxFreezeTimestamp|date_format:"%Y-%m-%d"}." isPublicFacing=true}
			</p>
			<input type="hidden" id="maxFreezeFormattedDate" value="{$maxFreezeTimestamp|date_format:"%Y-%m-%d"}">
		{/if}
	</form>
	<script	type="text/javascript">
		{literal}
		$(function(){
			$(".form").validate({
				submitHandler: function(){
					AspenDiscovery.Account.doFreezeHoldWithReactivationDate('#doFreezeHoldWithReactivationDate');
				}
			});
		});
		{/literal}
	</script>
{/strip}
