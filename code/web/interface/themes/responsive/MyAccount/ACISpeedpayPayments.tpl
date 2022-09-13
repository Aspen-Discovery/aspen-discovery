{strip}
	<input type="hidden" name="patronId" value="{$userId}"/>
	<div class="row">
		<div class="col-tn-12 col-sm-8 col-md-6 col-lg -3">
			<script data-aci-speedpay src="https://{$sdkUrl}/js-sdk/1.4.0/speedpay.js?billerId={$billerId}" integrity="{$sriHash}" crossorigin="anonymous" referrerpolicy="strict-origin"></script>

			<div class="form-group">
				<label id="card-number" class="control-label">Card Number</label>
				<div data-aci-speedpay="card-number" class="form-control"></div>
			</div>
			<div class="form-group">
				<label id="expire-date" class="control-label">Expiration Date</label>
				<div data-aci-speedpay="expiration-date" class="form-control"></div>
			</div>
			<div class="form-group">
				<label id="cvv" class="control-label">CVV</label>
				<div data-aci-speedpay="security-code" class="form-control"></div>
			</div>
			<div class="form-group">
                <label id="account-holdername" class="control-label">Cardholder Name</label>
                <input type="text" data-aci-speedpay="account-holder-name" class="form-control"/>
            </div>
			<div class="form-group">
				<label id="region-code" class="control-label">State</label>
                <input type="text" data-aci-speedpay="account-region-code" class="form-control" />
            </div>
            <div class="form-group">
                <label id="postal-code" class="control-label">ZIP Code</label>
                <input type="text" data-aci-speedpay="account-postal-code" class="form-control" />
            </div>

			<input type="button" id="card-submit-button" class="btn btn-primary" value="Card Submit" data-aci-speedpay="card-submit-button"/>

			<script>
				fundingAccountGatewayResult = aci.speedpay.fundingAccountGateway.init(
					{ldelim}
                        apiAuthKey: '{$apiAuthKey}',
                        accessToken: '{$accessToken}',
                        billerAccountId: '{$billerAccountId}',
                        paymentMethod: 'Card',
                        styles: {ldelim}
                            input: {ldelim}
                                color: '#555555',
                                fontsize: '14px',
                                border: '0px',
                                borderradius: '0px',
                                padding: '0px',
                                lineheight: '1.428571429',
                                background: '#ffffff',
                                width: {ldelim}
                                    all: '100%'
                                {rdelim},
                                focus: {ldelim}
                                    boxshadow: '0px',
                                    outline: '0px',
                                    bordercolor: '#ffffff',
                                {rdelim}
                            {rdelim},
                            iframe: {ldelim}
                                height: '2.5em'
                            {rdelim}
                        {rdelim}
                    {rdelim},
                    (onValidate = function(event) {ldelim}
                          console.log(event);
                          if(event.kind === 'ValidationError')
                            console.log(event.message.default);
                    {rdelim}),
                    (onCreateToken = function(event) {ldelim}
                        console.log(event);
                        if(event.token.id) {ldelim}
                            console.log('Funding account has been created.');
                            return AspenDiscovery.Account.createACISpeedpayOrder('#fines{$userId}', 'fine', event.token.id);
                        {rdelim}
                    {rdelim}),
                    (onGetToken = function(event) {ldelim}
                        console.log(event);
                        if(event.id) {ldelim}
                            console.log('Funding account has been obtained successfully.');
                            return AspenDiscovery.Account.completeACISpeedpayOrder(event.id, {$userId}, 'fine');
                        {rdelim}
                    {rdelim}),
                    (onError = function(event)
                        {ldelim}
                            console.log(event);
                            AspenDiscovery.Account.handleACISpeedpayError(event.message.default);
                        {rdelim}
                    )
                );
			</script>
			<script>
			var form = document.getElementById('card-submit-button');
			form.addEventListener("click", handleCreateToken);
			function handleCreateToken() {ldelim}
				console.log('Creating token..');
				fundingAccountGatewayResult.createToken();
			{rdelim}
            </script>
		</div>
	</div>
{/strip}
