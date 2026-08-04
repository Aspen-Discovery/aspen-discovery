{strip}
    <div class="table-responsive">
        <table class="table table-striped table-bordered table-condensed">
            <thead>
                <tr>
                    <th>{translate text="Description" isPublicFacing=true}</th>
                    <th class="text-right">{translate text="Amount Paid" isPublicFacing=true}</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$lineItems item=lineItem}
                    <tr>
                        <td>{$lineItem->description|escape}</td>
                        <td class="text-right">
                            ${$lineItem->amountPaid|string_format:"%.2f"}
                        </td>
                    </tr>
                {/foreach}

                {if count($lineItems) == 0}
                    <tr>
                        <td colspan="2" class="text-center">
                            {translate text="No payment line items found." isPublicFacing=true}
                        </td>
                    </tr>
                {/if}
            </tbody>
            {if $payment->totalPaid > 0}
                <tfoot>
                    <tr>
                        <td class="text-right" colspan="2">
                            <strong>${$payment->totalPaid|string_format:"%.2f"}</strong>
                        </td>
                    </tr>
                </tfoot>
            {/if}
        </table>
    </div> 
{/strip}