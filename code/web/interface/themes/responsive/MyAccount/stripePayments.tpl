{strip}
	<input type="hidden" name="patronId" value="{$userId}"/>
	<div class="row">
		<div class="col-tn-12 col-sm-8 col-md-6 col-lg -3">
			<script src="https://js.stripe.com/v3/"></script>
			<script>
				const stripe = Stripe('{$stripePublicKey}');
				const appearance = {
					theme: 'flat',
				};
				const options = {
					layout: {
						type: 'tabs',
						defaultCollapsed: false,
					}
				};
				const elements = stripe.elements({
					'{$stripeSecretKey}',
					appearance
				});
				const paymentElement = elements.create('payment', options);

			</script>
			<form action="/process-payment" method="post" id="stripe-payment-form">
				<div>
					<label>Card Info</label>
					<div id="payment-element"></div>
				</div>
				<button type="submit">Submit Payment</button>
			</form>
			<script>
				paymentElement.mount('#payment-element');
			</script>
		</div>
	</div>
{/strip}