{strip}
	<input type="hidden" name="patronId" value="{$userId}"/>
	<div class="row">
		<div class="col-tn-12 col-sm-8 col-md-6 col-lg -3">
			<div id="heycentric-button-container{$userId}">
				<button type="button" class="btn btn-sm btn-primary" onclick="return AspenDiscovery.Account.createHeyCentricOrder('#fines{$userId}', 'fine');">{if !empty($payFinesLinkText)}{$payFinesLinkText}{else}{translate text = 'Go to payment form' isPublicFacing=true}{/if}</button>
			</div>
		</div>
	</div>
{/strip}