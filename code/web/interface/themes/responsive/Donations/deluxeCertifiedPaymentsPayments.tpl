{strip}
	<input type="hidden" name="application_id" value="{$deluxeApplicationId}"/>
	<input type="hidden" name="remittance_id" value="{$deluxeRemittanceId}"/>
	<input type="hidden" name="message_version" value="2.7"/>
	<div class="row">
        <div class="col-tn-12 col-sm-8 col-md-6 col-lg -3">
            <div id="certifiedPaymentsByDeluxe-button-container{$userId}">
                <button type="button" class="btn btn-sm btn-primary" onclick="return AspenDiscovery.Account.createCertifiedPaymentsByDeluxeOrder('#fines{$userId}', '#formattedTotal{$userId}', 'fine', '{$deluxeRemittanceId}');"><i class="fas fa-lock"></i> {if !empty($payFinesLinkText)}{$payFinesLinkText}{else}{translate text = 'Go to payment form' isPublicFacing=true}{/if}</button>
            </div>
        </div>
    </div>
    <script>
    $(document).ready(function () {ldelim}
        $('#fines{$userId}').attr('action', '{$deluxeAPIConnectionUrl}');
    {rdelim});
    </script>
{/strip}