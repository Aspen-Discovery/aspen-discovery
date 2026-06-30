{strip}
	<form>
		<div class='form-group'>
			<label for='timeframe'>{translate text='Report Usage per' isAdminFacing=true}</label>
			<select name='timeframe' id='timeframe' class='form-control' onchange=toggleCustomPeriodInputFieldDisplay()>
				<option {if $timeframe == 'day'}selected{/if} value='day'>{translate text='Day' isAdminFacing=true}</option>
				<option {if $timeframe == 'month'}selected{/if} value='month'>{translate text='Month' isAdminFacing=true}</option> 
				<option {if $timeframe == 'year'}selected{/if} value='year'>{translate text='Year' isAdminFacing=true}</option>
				<option {if $timeframe == 'custom'}selected{/if} value='custom'>{translate text='Custom period' isAdminFacing=true}</option>
			</select>
			<div id='custom-usage-period-wrapper' hidden>
				<label for='customUsagePeriodStart'>{translate text='Custom period start (date)' isAdminFacing=true}</label>
				<input type='date' name='customUsagePeriodStart' id='customUsagePeriodStart' min='1' class='form-control' hidden>			
				<label for='customUsagePeriodDuration'>{translate text='Custom period duration (days)' isAdminFacing=true}</label>
				<input type='number' name='customUsagePeriodDuration' id='customUsagePeriodDuration' min='1' class='form-control' hidden>
			</div>
			<input type="hidden" value="{$stat}" name="stat"/>
			{if isset($sideloadId)}
				<input type="hidden" value="{$sideloadId}" name="sideloadId"/>
			{/if}
		</div>
		<div class="form-group">
			<input type="submit" value="{translate text="Update Report" isAdminFacing=true inAttribute=true}" class="form-control btn btn-primary"/>
		</div>
	</form>
{/strip}
{literal}
<script>
	function toggleCustomPeriodInputFieldDisplay() {
		const selectedTimeFrame = document.getElementById('timeframe').value;
		const customPeriodInputWrapper = document.getElementById('custom-usage-period-wrapper');
		const customUsagePeriodStart = document.getElementById('customUsagePeriodStart');
		const customUsagePeriodDuration = document.getElementById('customUsagePeriodDuration');

		if (selectedTimeFrame === 'custom') {
			customPeriodInputWrapper.removeAttribute('hidden');
			customUsagePeriodStart.setAttribute('required', true);
			customUsagePeriodDuration.setAttribute('required', true);
			return;
		}

		if (customPeriodInputWrapper.hidden) {
			return;
		}

		customPeriodInputWrapper.setAttribute('hidden', true);
		customUsagePeriodStart.removeAttribute('required');
		customUsagePeriodDuration.removeAttribute('required');
	}
</script>
{/literal}