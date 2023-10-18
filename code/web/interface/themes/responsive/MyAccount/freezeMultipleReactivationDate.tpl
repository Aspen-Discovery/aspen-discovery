{strip}
	<form class="form" role="form">
		<input type="hidden" name="patronId" value="{$patronId}" id="patronId">
		<div class="form-group">
			<label for="reactivationDate">{translate text="Select the date when you want the holds thawed." isPublicFacing=true}</label>
			<input type="date" name="reactivationDate" id="reactivationDate" min="{$smarty.now|date_format:"%Y-%m-%d"}" {if $allowMaxDaysToFreeze > -1}max="{$maxDaysToFreeze|date_format:"%Y-%m-%d"}"{/if} class="form-control{if empty($reactivateDateNotRequired)} required{/if}">
		</div>
        {if !empty($reactivateDateNotRequired)}
			<p class="alert alert-info">
                {translate text="If a date is not selected, the holds will be frozen until you thaw them." isPublicFacing=true}
			</p>
        {/if}
	</form>
{/strip}