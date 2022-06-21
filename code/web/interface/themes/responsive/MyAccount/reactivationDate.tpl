{strip}
	<form class="form" role="form">
		<input type="hidden" name="holdId" value="{$holdId}" id="holdId">
		<input type="hidden" name="patronId" value="{$patronId}" id="patronId">
		<input type="hidden" name="recordId" value="{$recordId}" id="recordId">
		<div class="form-group">
			<label for="reactivationDate">Select the date when you want the hold {translate text="thawed"}.</label>
			<input type="date" name="reactivationDate" id="reactivationDate" min="{$smarty.now|date_format:"%Y-%m-%d"}" {if $allowMaxDaysToFreeze > -1}max="{$maxDaysToFreeze|date_format:"%Y-%m-%d"}"{/if} class="form-control{if !$reactivateDateNotRequired} required{/if}">
		</div>
		{if $reactivateDateNotRequired}
			<p class="alert alert-info">
				If a date is not selected, the hold will be {translate text="frozen"} until you {translate text="thaw"} it.
			</p>
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