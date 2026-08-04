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
			<div id='custom-usage-period-wrapper' {if $timeframe != 'custom'}hidden{/if}>
				<div class="{if !empty($customPeriodStartWarning)}has-error{/if}">
					<label for='customUsagePeriodStart' class="control-label">
						{translate text="Custom period start (date) - available from %1%" 1={$earliestUsageDate|format_date_locale:'short'} isAdminFacing=true}
					</label>
					<input type='date' name='customUsagePeriodStart' id='customUsagePeriodStart' min='{$earliestUsageDate|default:"2019-01-01"}' value='{$customUsagePeriodStart|default:''}' class='form-control' {if $timeframe == 'custom'}required{/if}>
				</div>
				<label for='customUsagePeriodDuration'>{translate text='Custom period duration (days)' isAdminFacing=true}</label>
				<input type='number' name='customUsagePeriodDuration' id='customUsagePeriodDuration' min='1' value='{$customUsagePeriodDuration|default:''}' class='form-control' {if $timeframe == 'custom'}required{/if}>			</div>
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