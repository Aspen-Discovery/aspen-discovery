{strip}
	<input type="hidden" name="patronId" value="{$userId}"/>
	<div class="row">
		<div class="col-tn-12 col-sm-8 col-md-6 col-lg -3">
			<div id="invoiceCloud-button-container{$userId}">
				<button type="button" class="btn btn-sm btn-primary" onclick="return AspenDiscovery.Account.createInvoiceCloudOrder('#donation{$userId}', 'donation');"><i class="fas fa-lock"></i> {translate text='Continue to Payment' isPublicFacing=true}</button>
			</div>
		</div>
	</div>
{/strip}