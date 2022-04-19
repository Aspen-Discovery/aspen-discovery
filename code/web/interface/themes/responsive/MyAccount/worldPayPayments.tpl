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
	<input type="hidden" name="PaymentAmount" id="{$userId}FineAmount" value="{$fineTotalsVal.$userId}" />
	<input type="hidden" name="PaymentMethod" value="CreditOrDebit" />
	<input type="hidden" name="ReturnUrl" id="{$userId}ReturnUrl" value="{$aspenUrl}/MyAccount/WorldPayCompleted?payment=" />
	<input type="hidden" name="CancelUrl" id="{$userId}CancelUrl" value="{$aspenUrl}/MyAccount/WorldPayCancel?payment=" />
	<input type="hidden" name="PostUrl" value="{$aspenUrl}/MyAccount/Fines/" />
	<input type="hidden" name="UserPart1" value="{$profile->firstname}" />
	<input type="hidden" name="UserPart2" value="{$profile->lastname}" />
	<input type="hidden" name="UserPart3" value="{$profile->cat_username}" />
	<input type="hidden" name="LineItems" id="{$userId}LineItems" value="[]"/>
	<div class="row">
		<div class="col-tn-12 col-sm-8 col-md-6 col-lg -3">
			<div id="msb-button-container{$userId}">
				{if $finesToPay == 2}<button type="submit" id="{$userId}PayFines" class="btn btn-sm btn-primary">{if $payFinesLinkText}{$payFinesLinkText}{else}{translate text = 'Go to payment form' isPublicFacing=true}{/if}</button>
				{else}<button type="submit" class="btn btn-sm btn-primary">{if $payFinesLinkText}{$payFinesLinkText}{else}{translate text = 'Go to payment form' isPublicFacing=true}{/if}</button>
                {/if}
			</div>
		</div>
	</div>

	<script>
	$(document).ready(function () {ldelim}
		$('#fines{$userId}').attr('action', '{$paymentSite}');
	{rdelim});
	</script>
	<script>
		$(document).ready(function () {ldelim}
			$('formattedTotal{$userId}').change(function(){ldelim}
				document.getElementById("{$userId}FineAmount").value = document.getElementById("formattedTotal{$userId}").text;
			{rdelim})
		{rdelim});
	</script>
	{if $finesToPay == 1}
		<script>
			$('#fines{$userId}').submit(function() {ldelim}
				var totalFineAmt = 0;
				var totalOutstandingAmt = 0;
				var lineItems = "";
				var lineItemNum = 0;
				$("#fines{$userId} .selectedFine:checked").each(
					function() {ldelim}
						lineItemNum += 1;
						var fineId = $(this).data('fine_id');
						var fineDescription = $(this).attr("aria-label");
						var fineAmount =  $(this).data('fine_amt');
						var lineItem = "["+lineItemNum+"*"+fineId+"*"+fineDescription+"*"+fineAmount+"]";
						totalFineAmt += fineAmount * 1;
						totalOutstandingAmt += fineAmount * 1;
						if(lineItems === '') {ldelim}
							lineItems = lineItems.concat(lineItem);
                            {rdelim} else {ldelim}
							lineItems = lineItems.concat(",", lineItem);
                            {rdelim}
                        {rdelim}
				);
				document.getElementById("{$userId}FineAmount").value = totalFineAmt;
				document.getElementById("{$userId}LineItems").value = lineItems;

				var paymentId = AspenDiscovery.Account.createWorldPayOrder('#fines{$userId}', '#formattedTotal{$userId}', 'fine');
				var returnUrl = document.getElementById("{$userId}ReturnUrl").value;
				var cancelUrl = document.getElementById("{$userId}CancelUrl").value;

				returnUrl = returnUrl.concat(paymentId);
				cancelUrl = cancelUrl.concat(paymentId);

				document.getElementById("{$userId}CancelUrl").value = cancelUrl;
				document.getElementById("{$userId}ReturnUrl").value = returnUrl;

                {rdelim});
		</script>
	{/if}
    {if $finesToPay == 2}
	<script>
		$('#fines{$userId}').submit(function() {ldelim}
			var totalFineAmt = 0;
			var totalOutstandingAmt = 0;
			var lineItems = "";
			var lineItemNum = 0;
			$("#fines{$userId} .selectedFine:checked").each(
				function() {ldelim}
					lineItemNum += 1;
					var fineId = $(this).data('fine_id');
					var fineDescription = $(this).attr("aria-label");
					var fineAmountInput = $("#amountToPay" + fineId);
					var lineItem = "["+lineItemNum+"*"+fineId+"*"+fineDescription+"*"+fineAmountInput.val()+"]";
					totalFineAmt += fineAmountInput.val() * 1;
					totalOutstandingAmt += fineAmountInput.val() * 1;
						if(lineItems === '') {ldelim}
							lineItems = lineItems.concat(lineItem);
	                    {rdelim} else {ldelim}
							lineItems = lineItems.concat(",", lineItem);
	                    {rdelim}
                    {rdelim}
			);
			document.getElementById("{$userId}FineAmount").value = totalFineAmt;
			document.getElementById("{$userId}LineItems").value = lineItems;

			var paymentId = AspenDiscovery.Account.createWorldPayOrder('#fines{$userId}', '#formattedTotal{$userId}', 'fine');
			var returnUrl = document.getElementById("{$userId}ReturnUrl").value;
			var cancelUrl = document.getElementById("{$userId}CancelUrl").value;

			returnUrl = returnUrl.concat(paymentId);
			cancelUrl = cancelUrl.concat(paymentId);

			document.getElementById("{$userId}CancelUrl").value = cancelUrl;
			document.getElementById("{$userId}ReturnUrl").value = returnUrl;

            {rdelim});
	</script>
    {/if}
{/strip}


