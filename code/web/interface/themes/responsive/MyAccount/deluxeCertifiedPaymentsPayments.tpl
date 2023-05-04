{strip}
	<input type="hidden" name="application_id" value="{$deluxeApplicationId}"/>
	<input type="hidden" name="remittance_id" value="{$deluxeRemittanceId}"/>
	<input type="hidden" name="message_version" value="2.7"/>
	<input type="hidden" name="patronId" value="{$userId}"/>
	<input type="hidden" name="user_defined1" value="{$profile->cat_username}"/>
	<input type="hidden" name="user_defined2" value="Unknown" id="{$userId}ItemBarcodes"/>
	<input type="hidden" name="user_defined3" value="Unknown" id="{$userId}BillReasons"/>
	<input type="hidden" name="user_defined4" value="Unknown" id="{$userId}ItemTitles"/>
	<div class="row">
        <div class="col-tn-12 col-sm-8 col-md-6 col-lg -3">
            <div id="certifiedPaymentsByDeluxe-button-container{$userId}">
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-lock"></i> {if !empty($payFinesLinkText)}{$payFinesLinkText}{else}{translate text = 'Go to payment form' isPublicFacing=true}{/if}</button>
            </div>
        </div>
    </div>
    <script>
    $(document).ready(function () {ldelim}
        $('#fines{$userId}').attr('action', '{$deluxeAPIConnectionUrl}');
    {rdelim});
    </script>
    <script>
        $('#fines{$userId}').submit(function() {ldelim}
            var itemBarcodes = "";
            var billReasons = "";
            var itemTitles = "";

            $("#fines{$userId} .selectedFine:checked").each(
                function() {ldelim}
                    var itemBarcode = $(this).data('fine_item_barcode');

                    if(itemBarcodes === '') {ldelim}
                        itemBarcodes = itemBarcode;
                    {rdelim} else {ldelim}
                        itemBarcodes = itemBarcodes.concat(",", itemBarcode);
                    {rdelim}

                    var billReason = $(this).data('fine_reason');
                    console.log("billReason: " + billReason);
                    if(billReasons === '') {ldelim}
                        billReasons = billReason;
                    {rdelim} else {ldelim}
                        billReasons = billReasons.concat(",", billReason);
                    {rdelim}
                    console.log("billReasons: " + billReasons);

                    var itemTitle = $(this).data('fine_item_description');
                    console.log(itemTitle);
                    if(itemTitles === '') {ldelim}
                        itemTitles = itemTitle;
                    {rdelim} else {ldelim}
                        itemTitles = itemTitles.concat(",", itemTitle);
                    {rdelim}
                {rdelim}
            );
            document.getElementById("{$userId}ItemBarcodes").value = itemBarcodes;
            document.getElementById("{$userId}BillReasons").value = billReasons;
            document.getElementById("{$userId}ItemTitles").value = itemTitles;

            AspenDiscovery.Account.createCertifiedPaymentsByDeluxeOrder('#fines{$userId}', 'fine', '{$deluxeRemittanceId}');
        {rdelim});
    </script>

{/strip}