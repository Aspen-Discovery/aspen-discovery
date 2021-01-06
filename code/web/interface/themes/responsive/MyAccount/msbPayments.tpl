{strip}
	<input type="hidden" name="patronId" value="{$userId}"/>
	<div class="row">
		<div class="col-tn-12 col-sm-8 col-md-6 col-lg -3">
			<div id="msb-button-container{$userId}">
				<button type="button" onclick="return AspenDiscovery.Account.createMSBOrder('#fines{$userId}');">Go to payment form</button>
			</div>
		</div>
	</div>
{/strip}
