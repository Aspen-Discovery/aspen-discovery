{strip}
	<input type="hidden" name="patronId" value="{$userId}"/>
	<div class="row" style="margin-left: .25em; margin-right: .25em">
		<div class="panel panel-info col-tn-12" style="padding-left: 0; padding-right: 0">
			<div class="panel-body">
				<div id="stripe-container" class="row">
					<div id="card-element" class="form-group col-tn-12"></div>
					<div class="form-group col-tn-12">
						<button id="process-stripe-payment" type="button" class="btn btn-primary btn-block"><i class="fas fa-lock"></i> {translate text = 'Submit Payment' isPublicFacing=true}</button>
					</div>
				</div>
			</div>
		</div>

		<script src="https://js.stripe.com/v3/"></script>
		{literal}
		<script>
			let stripe = Stripe('{/literal}{$stripePublicKey}{literal}');
			let elements = stripe.elements();
			let card = elements.create('card');
			card.mount('#card-element')

			const cardButton = document.getElementById('process-stripe-payment');

			cardButton.addEventListener('click', async function (event) {
				event.preventDefault();
				cardButton.disabled = true;
				cardButton.innerHTML = "Submitting Payment...";

				var paymentId = AspenDiscovery.Account.createStripeOrder('#donation{/literal}{$userId}{literal}', 'donation');

				stripe.createPaymentMethod({
					type: 'card',
					card: card,
				})
						.then(function(result) {
							// Handle result.error or result.paymentMethod
							if (result.error){
								console.log(result.error.message);
								cardButton.disabled = false;
								cardButton.innerHTML = "{/literal}{translate text = 'Submit Payment' isPublicFacing=true}{literal}";
							} else {
								AspenDiscovery.Account.completeStripeOrder({/literal}{$userId}{literal}, 'donation', paymentId, result.paymentMethod.id);
							}
						});
			});
		</script>
		{/literal}
	</div>
{/strip}