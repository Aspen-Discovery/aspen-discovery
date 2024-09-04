{strip}
    <input type="hidden" name="patronId" value="{$profile->id}"/>
    <div class="row">
        <div class="col-tn-12 col-sm-8 col-md-6 col-lg -3">
            <div id="snapPay-button-container{$userId}">
                <button type="button" class="btn btn-sm btn-primary" onclick="return AspenDiscovery.Account.createSnapPayOrder('#fines{$userId}', 'fines');">{if !empty($payFinesLinkText)}{$payFinesLinkText}{else}{translate text = 'Go to payment form' isPublicFacing=true}{/if}</button>
            </div>
        </div>
    </div>
{/strip}