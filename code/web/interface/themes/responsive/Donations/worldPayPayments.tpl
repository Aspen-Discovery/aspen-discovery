{strip}
	<input type="hidden" name="MerchantCode" value="{$merchantCode}" />
	<input type="hidden" name="SettleCode" value="{$settleCode}" />
	<input type="hidden" name="patronId" value="{$userId}"/>
	<input type="hidden" name="BillingName" value="{$profile->_fullname}" />
	<input type="hidden" name="BillingAddress" value="{$profile->_address1}" />
	<input type="hidden" name="BillingCity" value="{$profile->_city}" />
	<input type="hidden" name="BillingState" value="{$profile->_state}" />
	<input type="hidden" name="BillingPostalCode" value="{$profile->_zip}" />
	<input type="hidden" name="BillingPhone" value="{$profile->phone}" />
	<input type="hidden" name="BillingEmail" value="{$profile->email}" />
	<input type="hidden" name="PaymentAmount" id="{$userId}DonationAmount" value="0.00" />
	<input type="hidden" name="PaymentMethod" value="CreditOrDebit" />
	<input type="hidden" name="ReturnUrl" id="{$userId}ReturnUrl" value="{$aspenUrl}/MyAccount/WorldPayCompleted?payment=" />
	<input type="hidden" name="CancelUrl" id="{$userId}CancelUrl" value="{$aspenUrl}/MyAccount/WorldPayCancel?payment=" />
	<input type="hidden" name="PostUrl" id="{$userId}PostUrl" value="{$aspenUrl}/WorldPay/Complete" />
	<input type="hidden" name="UserPart1" id="PaymentId" value="0" />
	<input type="hidden" name="UserPart2" value="{$profile->firstname}" />
	<input type="hidden" name="UserPart3" value="{$profile->lastname}" />
	<input type="hidden" name="UserPart4" value="{$profile->getBarcode()}" />
	{if !empty($useLineItems)}
		<input type="hidden" name="LineItems" id="{$userId}LineItems" value="[]"/>
	{/if}
	<div class="row">
		<div class="col-tn-12 col-sm-8 col-md-6 col-lg -3">
			<div id="worldpay-button-container{$userId}">
				<button type="submit" id="{$userId}PayDonation" class="btn btn-sm btn-primary"><i class="fas fa-lock"></i> {translate text='Continue to Payment' isPublicFacing=true}</button>
			</div>
		</div>
	</div>

	<script>
	$(document).ready(function () {ldelim}
		$('#donation{$userId}').attr('action', '{$paymentSite}');
	{rdelim});
	</script>
	<script>
		$(document).ready(function () {ldelim}
			$('formattedTotal{$userId}').change(function(){ldelim}
				document.getElementById("{$userId}DonationAmount").value = document.getElementById("thisDonationValue").text;
			{rdelim})
		{rdelim});
	</script>
{/strip}