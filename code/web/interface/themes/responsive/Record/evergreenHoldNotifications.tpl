{strip}
	<p>
	{translate text="Notify when hold is ready for pickup?" isPublicFacing=true}
	</p>
	{if !empty($primaryEmail)}
		<div>
			<label for="emailNotification">
				<input type="checkbox" id="emailNotification" name="emailNotification" {if in_array('email', $opac_hold_notify)}checked{/if}> {translate text="Yes, by email to %1%" isPublicFacing=true 1=$primaryEmail}
			</label>
		</div>
	{/if}
	<div>
		<label for="phoneNotification">
			<input type="checkbox" id="phoneNotification" name="phoneNotification" {if !empty($opac_default_phone) && in_array('phone', $opac_hold_notify)}checked{/if}> {translate text="Yes, by phone" isPublicFacing=true}
		</label>
		<div class="form-group">
			<label class="control-label" for="phoneNumber">{translate text="Phone Number" isPublicFacing=true}</label>
			<input type="tel" name="phoneNumber" id="phoneNumber" class="form-control" size="10" value="{$opac_default_phone}">
		</div>
	</div>
	{if !empty($smsCarriers)}
		<div>
			<label for="smsNotification">
				<input type="checkbox" id="smsNotification" name="smsNotification" {if in_array('sms', $opac_hold_notify)}checked{/if}> {translate text="Yes, by text message" isPublicFacing=true}
			</label>
			<div class="form-group">
				<label class="control-label" for="smsCarrier">{translate text="Mobile Carrier" isPublicFacing=true}</label>
				<select name="smsCarrier" id="smsCarrier" class="form-control">
					<option value="-1">{translate text="Please select a carrier" isPublicFacing=true}</option>
					{foreach from=$smsCarriers item=smsCarrier key=smsCarrierId}
						<option value="{$smsCarrierId}" {if $opac_default_sms_carrier == $smsCarrierId}selected{/if}>{$smsCarrier}</option>
					{/foreach}
				</select>
				<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small class="text-warning"><i class="fas fa-exclamation-triangle"></i> {translate text="Note: carrier charges may apply " isAdminFacing=true}</small></span>
			</div>
			<div class="form-group">
				<label class="control-label" for="smsNumber">{translate text="Mobile Number" isPublicFacing=true}</label>
				<input type="tel" name="smsNumber" id="smsNumber" class="form-control" size="10" value="{$opac_default_sms_notify}">
				<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0"><small class="text-warning"><i class="fas fa-exclamation-triangle"></i> {translate text="Hint: use the full 10 digits of your phone #, no spaces, no dashes" isAdminFacing=true}</small></span>
			</div>
		</div>
	{/if}
{/strip}