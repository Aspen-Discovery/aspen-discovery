{strip}
	<input type="hidden" name="patronId" value="{$userId}"/>
	<div class="row">
	    <div class="col-tn-12">
	        <div id="paypalPayflow-button-container-{$userId}">
	            <button type="button" id="processTokenBtn" class="btn btn-sm btn-primary" onclick="return AspenDiscovery.Account.createPayPalPayflowOrder('{$userId}', '#formattedTotal{$userId}', 'donation');"><i class="fas fa-lock"></i> {translate text='Continue to Payment' isPublicFacing=true}</button>
	        </div>
	    </div>
	</div>
{/strip}