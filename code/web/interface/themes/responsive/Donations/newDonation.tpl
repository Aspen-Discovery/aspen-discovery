<div id="page-content" class="content">
    <div id="main-content">
        <h1>{translate text='Make a Donation' isPublicFacing=true}</h1>
        <div id="newDonation">
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
                    {/if}
                </form>
            {/if}
        </div>
    </div>
</div>
<script type="text/javascript">
    $("#donationForm").validate();
</script>