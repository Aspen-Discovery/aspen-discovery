{strip}
    <input type="hidden" name="patronId" value="{$userId}"/>
    <div id="comprise-button-container{$userId}" class="center-block text-center">
        <button type="button" class="btn btn-primary btn-lg" onclick="return AspenDiscovery.Account.createProPayOrder('#donation{$userId}', 'donation')"><i class="fas fa-lock"></i> {translate text='Continue to Payment' isPublicFacing=true}</button>
    </div>
{/strip}
