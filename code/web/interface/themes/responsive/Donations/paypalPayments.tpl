{strip}
    <input type="hidden" name="patronId" value="{$userId}"/>
    <div class="row">
        <div class="col-md-6 col-md-offset-3 col-sm-12">
            <script src="https://www.paypal.com/sdk/js?client-id={$payPalClientId}&currency={$currencyCode}{if !$showPayLater}&disable-funding=credit{/if}"></script>

            <div id="paypal-button-container{$userId}"></div>

            <script>
                $(document).ready(function () {ldelim}
                    paypal.Buttons({ldelim}
                        createOrder: function (data, actions) {ldelim}
                            return AspenDiscovery.Account.createPayPalOrder('#donation{$userId}', 'donation');
                            {rdelim},
                        onApprove: function (data, actions) {ldelim}
                            {* This function captures the funds from the transaction. *}
                            return actions.order.capture().then(
                                function (details) {ldelim}
                                    {* This function shows a transaction success message to your buyer. *}
                                    AspenDiscovery.Account.completePayPalOrder(details.id, '{$userId}', 'donation');
                                    {rdelim}
                            );
                            {rdelim},
                        onError: function(error) {ldelim}
                            AspenDiscovery.Account.handlePayPalError(error);
                            {rdelim},
                        onCancel: function(data) {ldelim}
                            AspenDiscovery.Account.cancelPayPalError();
                            {rdelim},
                        {rdelim}).render('#paypal-button-container{$userId}');
                    {rdelim});
            </script>
        </div>
    </div>
{/strip}
