<h1>{translate text='Make a Donation' isPublicFacing=true}</h1>
<div id="newDonation">
    <div class="col-xs-12">
        {if !empty($donationsContent)}
        <div class="donationsContent">
            {translate text=$donationsContent isPublicFacing=true isAdminEnteredData=true}
        </div>
        {/if}
        {if $paymentType == 0 || $paymentType == 1}
            <div class="alert alert-danger">{translate text='Unable to load form. The library has not setup a payment processor.' isPublicFacing=true}</div>
        {else}
            <form id="donation{$userId}" action="/Donations/Submit" method="post" class="form" role="form">
                {include file="Donations/form-fields.tpl"}

                <p class="h1 text-center" id="thisDonation" style="display: none"><strong>{$currencySymbol}<span id="thisDonationValue"></span></strong><br><small>{$currencyCode}</small></p>
                {* get the right payment processor template *}
                {if $paymentType == 2}
                    {include file="Donations/paypalPayments.tpl"}
                {elseif $paymentType == 3}
                    {include file="Donations/msbPayments.tpl"}
                {elseif $paymentType == 4}
                    {include file="Donations/comprisePayments.tpl"}
                {elseif $paymentType == 5}
                    {include file="Donations/proPayPayments.tpl"}
                {elseif $paymentType == 6}
                    {include file="Donations/xpressPayPayments.tpl"}
                {elseif $paymentType == 7}
                    {include file="Donations/worldPayPayments.tpl"}
                {elseif $paymentType == 8}
                    {include file="Donations/ACISpeedpayPayments.tpl"}
                {elseif $paymentType == 9}
                    {include file="Donations/invoiceCloudPayments.tpl"}
                {elseif $paymentType == 10}
                    {include file="Donations/deluxeCertifiedPaymentsPayments.tpl"}
                {elseif $paymentType == 11}
                    {include file="Donations/paypalPayflowPayments.tpl"}
                {elseif $paymentType == 12}
                    {include file="Donations/squarePayments.tpl"}
                {elseif $paymentType == 13}
                    {include file="Donations/stripePayments.tpl"}
                {elseif $paymentType == 14}
                    {include file="Donations/NCRPayments.tpl"}
                {elseif $paymentType == 15}
                    {include file="Donations/snapPayPayments.tpl"}
                {/if}
            </form>
        {/if}
    </div>
</div>
<script type="text/javascript">
    $("#donationForm").validate();
</script>