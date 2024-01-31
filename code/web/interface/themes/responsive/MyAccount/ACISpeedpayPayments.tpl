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
			<label style="display:none">
				<input type="checkbox" data-aci-speedpay="single-use" checked />
			</label>
			<input type="button" id="process-payment" class="btn btn-primary" value="{if !empty($payFinesLinkText)}{$payFinesLinkText}{else}{translate text = 'Submit Payment' isPublicFacing=true}{/if}"/>

			{literal}
			<script>
				fundingAccountGatewayResult = aci.speedpay.fundingAccountGateway.init(
					{
                        "apiAuthKey": '{/literal}{$sdkAuthKey}{literal}',
                        "accessToken": '{/literal}{$accessToken}{literal}',
                        "singleUse": 'true',
                        "paymentMethod": 'Card',
                        "billerAccountId": '{/literal}{$billerAccountId}{literal}',
						"styles": {
							"input": {
								"fontfamily": "Helvetica",
								"color": "{/literal}{$bodyTextColor}{literal}",
								"fontsize": "14px",
								"border": "1px solid {/literal}{$bodyTextColor}{literal}",
								"borderradius": "4px",
								"padding": "6px",
								"lineheight": "1.428571429",
								"background": "{/literal}{$bodyBackgroundColor}{literal}",
								"width": {
									"all": "90%",
									"card-number": "97%",
									"expiration-date": "88%",
									"security-code": "85%"
								},
								"onfocus": {
									"boxshadow": "inset 0 1px 1px rgba(0,0,0,.075), 0 0 8px rgba(102, 175, 233, 0.6)",
									"outline": "0px",
									"border": "1px solid #3174AF",
									"background": "{/literal}{$bodyBackgroundColor}{literal}"
								},
								"valid":{
									"border": "1px solid #3c763d",
									"boxshadow": "0px",
									"background": "#dff0d8"
								},
								"invalid":{
									"border": "1px solid #a94442",
									"boxshadow": "0px",
									"background": "#f2dede"
								},
							},
							"iframe":{
								"height": "2.5em"
							},
							"placeholder": {
								"content": {
									"expiration-date": "MMYY"
								}
							}
						},
					},
                    (onError = function(event) {
                            AspenDiscovery.Account.handleACIError(event.message.default);
                    }),
                );

                var cardButton = document.getElementById('process-payment');
                cardButton.addEventListener("click", function(event) {
	                cardButton.disabled = true;
	                cardButton.value = "Submitting Payment...";
                    fundingAccountGatewayResult.then((handler) =>
                    {
                        handler.createToken()
                       .then((tokenDetails) =>
                        {
                           var paymentId = AspenDiscovery.Account.createACIOrder('#fines{/literal}{$userId}{literal}', 'fine', tokenDetails.token.id, '{/literal}{$accessToken}{literal}');
                           AspenDiscovery.Account.completeACIOrder(tokenDetails.token.id, {/literal}{$userId}{literal}, 'fine', paymentId, '{/literal}{$accessToken}{literal}', '{/literal}{$billerAccountId}{literal}');
	                        cardButton.disabled = false;
	                        cardButton.value = "{/literal}{if !empty($payFinesLinkText)}{$payFinesLinkText}{else}{translate text = 'Submit Payment' isPublicFacing=true}{/if}{literal}";
                        }
                        )
                       .catch((error) =>
                        {
	                        AspenDiscovery.Account.handleACIError(error.message.default);
	                        cardButton.disabled = false;
	                        cardButton.value = "{/literal}{if !empty($payFinesLinkText)}{$payFinesLinkText}{else}{translate text = 'Submit Payment' isPublicFacing=true}{/if}{literal}";
                        });
                    })
				});
			</script>
        {/literal}
			{else}
				<div class="alert alert-warning"><strong>{translate text=$aciError isPublicFacing=true}</strong></div>
			{/if}
			</div>
		</div>
	</div>
{/strip}