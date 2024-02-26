<div class="page">
    <div id="tosBody">
        {$tosBody}
    </div>
    <div id="tosDenialBody" style="display: none">
        {$tosDenialBody}
    </div>
    <div id="tosButtons" class="col-xs-12" style="display: flex;align-items: center;justify-content: center;">
        <div id="tosChoicesButtons" class="btn-toolbar">
            <button id="tosAcceptButton" class="btn btn-default" type="button" onclick="window.location = '/MyAccount/SelfReg?tosAccept=true'" style="margin: 0px 12px 0px 0px; width: 10em"</button> {translate text="I Accept" isPublicFacing=true}
            <button id="tosDenyButton" class="btn btn-default" type="button" onclick="$('#tosDenialBody').show();$('#tosDenied').show();$('#tosBody').hide();$('#tosChoicesButtons').hide();" style="margin: 0px 0px 0px 12px; width: 10em"</button> {translate text="I Do Not Accept" isPublicFacing=true}
        </div>
        <div id="tosDenied" class="btn-toolbar" style="display: none">
            <span><button id="tosDeniedButton" class="btn btn-default" type="button" onclick="window.location = '/MyAccount/Home'" style="margin: 0px 12px 0px 0px; width: 10em"</button> {translate text="Ok" isPublicFacing=true}</span>
            <span><button id="tosReadAgainButton" class="btn btn-default" type="button" onclick="$('#tosDenialBody').hide();$('#tosDenied').hide();$('#tosBody').show();$('#tosChoicesButtons').show();" style="margin: 0px 0px 0px 12px; width: 10em"</button> {translate text="Read Terms Again" isPublicFacing=true}</span>
        </div>
    </div>
</div>