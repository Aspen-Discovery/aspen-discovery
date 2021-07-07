{strip}
	<input type="hidden" name="patronId" value="{$userId}"/>
	<div class="row">
		<div class="col-tn-12 col-sm-8 col-md-6 col-lg -3">
			<div id="comprise-button-container{$userId}">
				<button type="button" onclick="return AspenDiscovery.Account.createCompriseOrder('#fines{$userId}', '#formattedTotal{$userId}');">{if $payFinesLinkText}{$payFinesLinkText}{else}{translate text = 'Go to payment form'}{/if}</button>
			</div>
		</div>
	</div>
{/strip}
