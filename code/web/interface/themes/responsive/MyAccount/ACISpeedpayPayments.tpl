{strip}
	<input type="hidden" name="patronId" value="{$userId}"/>
	<div class="row" style="margin-left: .25em; margin-right: .25em">
		<div class="panel panel-info col-tn-12" style="padding-left: 0; padding-right: 0">
			<div class="panel-heading">
				{translate text="Pay Online" isPublicFacing=true}
			</div>
			<div class="panel-body">
		{if (empty($aciError))}
			<script data-aci-speedpay src="https://{$sdkUrl}/js-sdk/1.5.0/speedpay.js?billerId={$billerId}" integrity="{$sriHash}" crossorigin="anonymous" referrerpolicy="strict-origin"></script>

			<div class="row">
				<div class="form-group col-xs-6 col-lg-7">
					<label id="card-number" class="control-label">{translate text="Card Number" isPublicFacing=true}</label>
					<div data-aci-speedpay="card-number"></div>
				</div>
				<div class="form-group col-xs-4 col-lg-3">
					<label id="expire-date" class="control-label">{translate text="Expiration (MM/YY)" isPublicFacing=true}</label>
					<div data-aci-speedpay="expiration-date"></div>
				</div>
				<div class="form-group col-xs-2 col-lg-2">
					<label id="cvv" class="control-label">{translate text="CVV" isPublicFacing=true}</label>
					<div data-aci-speedpay="security-code"></div>
				</div>
			</div>
			<div class="row">
				<div class="form-group col-xs-6 col-lg-7">
	                <label id="account-holdername" class="control-label">{translate text="Cardholder Name" isPublicFacing=true}</label>
	                <input type="text" data-aci-speedpay="account-holder-name" class="form-control" minlength="1" maxlength="45"/>
	            </div>
				<div class="form-group col-xs-2">
					<label id="region-code" class="control-label">{translate text="State" isPublicFacing=true}</label>
					<input type="text" data-aci-speedpay="account-region-code" class="form-control" minlength="2" maxlength="3" />
				</div>
				<div class="form-group col-xs-4 col-lg-3">
					<label id="postal-code" class="control-label">{translate text="Zip Code" isPublicFacing=true}</label>
					<input type="text" data-aci-speedpay="account-postal-code" class="form-control" minlength="5" maxlength="10" />
				</div>
			</div>

			<input type="hidden" data-aci-speedpay="account-country-code" value="US" />
			<input type="hidden" data-aci-speedpay="single-use" value="true" />

			<input type="button" id="card-submit-button" class="btn btn-primary" value="{if !empty($payFinesLinkText)}{$payFinesLinkText}{else}{translate text = 'Submit Payment' isPublicFacing=true}{/if}" data-aci-speedpay="card-submit-button"/>

			<script>
				fundingAccountGatewayResult = aci.speedpay.fundingAccountGateway.init(
					{ldelim}
                        apiAuthKey: '{$sdkAuthKey}',
                        accessToken: '{$accessToken}',
                        singleUse: 'true',
                        paymentMethod: 'Card',
                        billerAccountId: '{$billerAccountId}',
                        styles: {ldelim}
                            input: {ldelim}
	                            fontfamily: 'Helvetica',
                                color: '{$bodyTextColor}',
                                fontsize: '14px',
                                border: '1px solid {$bodyTextColor}',
                                borderradius: '4px',
                                padding: '6px',
                                lineheight: '1.428571429',
                                background: '{$bodyBackgroundColor}',
                                width: {ldelim}
                                    all: '92%',
                                {rdelim},
                                onfocus: {ldelim}
                                    boxshadow: 'inset 0 1px 1px rgba(0,0,0,.075), 0 0 8px rgba(102, 175, 233, 0.6)',
                                    outline: '0px',
	                                border: '1px solid #3174AF',
	                                background: '{$bodyBackgroundColor}',
                                {rdelim},
	                            valid: {ldelim}
		                            border: '1px solid #3c763d',
		                            boxshadow: '0px',
		                            background: '#dff0d8',
	                            {rdelim},
	                            invalid: {ldelim}
		                            border: '1px solid #a94442',
		                            boxshadow: '0px',
		                            background: '#f2dede',
                                {rdelim},
                            {rdelim},
                            iframe: {ldelim}
                                height: '2.5em'
                            {rdelim}
                        {rdelim}
                    {rdelim},
                    (onValidate = function(event) {ldelim}
                          if(event.kind === 'ValidationError')
                            console.log(event.message.default);
                    {rdelim}),
                    (onCreateToken = function(event) {ldelim}
                        if(event.token.id) {ldelim}
                            console.log('Funding account has been created.');
                        {rdelim}
                    {rdelim}),
                    (onGetToken = function(event) {ldelim}
                        if(event.token.id) {ldelim}
                            console.log('Funding account has been obtained successfully.');
                        {rdelim}
                    {rdelim}),
                    (onUpdatedToken = function(event) {ldelim}
                        if(event) {ldelim}
                        console.log(event);
                        {rdelim}
                    {rdelim}),
                    (onError = function(event)
                        {ldelim}
                            AspenDiscovery.Account.handleACIError(event.message.default);
                        {rdelim}
                    )
                );

                var cardButton = document.getElementById('card-submit-button');
                cardButton.addEventListener("click", function(event) {ldelim}
	                cardButton.disabled = true;
	                cardButton.value = "Submitting Payment...";
	                console.log('Creating token..');
                    fundingAccountGatewayResult.then((handler) =>
                    {ldelim}
                        handler.createToken()
                       .then((tokenDetails) =>
                        {ldelim}
                           var paymentId = AspenDiscovery.Account.createACIOrder('#fines{$userId}', 'fine', tokenDetails.token.id, '{$accessToken}');
                           AspenDiscovery.Account.completeACIOrder(tokenDetails.token.id, {$userId}, 'fine', paymentId, '{$accessToken}', '{$billerAccountId}');
	                        cardButton.disabled = false;
	                        cardButton.value = "{if !empty($payFinesLinkText)}{$payFinesLinkText}{else}{translate text = 'Submit Payment' isPublicFacing=true}{/if}";
                        {rdelim})
                       .catch((error) =>
                        {ldelim}
	                        AspenDiscovery.Account.handleACIError(error.message.default);
	                        cardButton.disabled = false;
	                        cardButton.value = "{if !empty($payFinesLinkText)}{$payFinesLinkText}{else}{translate text = 'Submit Payment' isPublicFacing=true}{/if}";
                        {rdelim});
                    {rdelim})
                {rdelim});
			</script>
			{else}
				<div class="alert alert-warning"><strong>{translate text=$aciError isPublicFacing=true}</strong></div>
			{/if}
			</div>
		</div>
	</div>
{/strip}