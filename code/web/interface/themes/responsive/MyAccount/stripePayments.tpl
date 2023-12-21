{strip}
	<input type="hidden" name="patronId" value="{$userId}"/>
	<div class="row" style="margin-left: .25em; margin-right: .25em">
			<div class="panel panel-info col-tn-12" style="padding-left: 0; padding-right: 0">
				<div class="panel-heading">
                    {translate text="Pay Online" isPublicFacing=true}
				</div>
				<div class="panel-body">
					<div id="stripe-container" class="row">
						<div id="card-element" class="form-group col-tn-12"></div>
						<div class="form-group col-tn-12">
							<button type="submit" class="btn btn-primary">{if !empty($payFinesLinkText)}{$payFinesLinkText}{else}{translate text = 'Submit Payment' isPublicFacing=true}{/if}</button>
						</div>
					</div>
				</div>
				</div>

			<script src="https://js.stripe.com/v3/"></script>
			<script>
				let stripe = Stripe('{$stripePublicKey}');
				let options = { /* style options for card element */ };
				let elements = stripe.elements();
				let card = elements.create('card', options);
				card.mount('#card-element')
			</script>
	</div>
{/strip}