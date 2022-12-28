{strip}
    <div class="donationFields" style="padding-top: 1em">
        {foreach from=$donationFormFields key=category item=formFields}
            <fieldset class="row" style="margin-top: .5em">
                <legend>{translate text=$category isPublicFacing=true}</legend>
                {foreach from=$formFields item=formField}
                    {* DONATION INFORMATION *}

                    <div id="{$formField->textId}Row">
                    {* Donation Value Options *}
                    {if $formField->textId == 'valueList'}
                        <div class="col-md-12">
                            <div class="btn-group btn-group-justified" data-toggle="buttons">
                                {foreach from=$donationValues item=value key=valueKey}
                                        <label class="btn btn-default btn-lg predefinedAmount bold">
                                            <input type="radio" id="amount{$value}" class="predefinedAmount" name="predefinedAmount" value="{$value}" onchange="return AspenDiscovery.Account.getDonationValuesForDisplay();"> {$currencySymbol}{$value}
                                        </label>
                                {/foreach}
                            </div>
                        </div>
                        <div class="col-md-12" style="padding-top:.5em; padding-bottom: 2em">
                            <label class="sr-only" for="amount">{translate text='Other amount' isPublicFacing=true}</label>
                            <div class="input-group input-group-lg">
                                <div class="input-group-addon" >{$currencySymbol}</div>
                                <input type="number" step="0.01" class="form-control" name="customAmount" id="customAmount" placeholder="{translate text='Other amount' isPublicFacing=true inAttribute=true}" onchange="return AspenDiscovery.Account.getDonationValuesForDisplay();">
                            </div>
                        </div>
                    {* Donation Earmark *}
                    {elseif $formField->textId == 'earmarkList'}
                        {if $allowDonationEarmark == 1}
                        <div class="col-xs-12">
                        <div class="form-group {$formField->textId}">
                            <label id="{$formField->textId}Label" for="{$formField->textId}" class="control-label">{translate text=$formField->label isPublicFacing=true isAdminEnteredData=true}{if $formField->required}<span class="requiredIndicator">*</span>{/if}</label>
                            <select name="earmark" id="{$formField->textId}" class="form-control input-lg">
                                <option value="null" selected></option>
                                {foreach from=$donationEarmarks item=value key=earmarkKey}
                                    <option value={$value}>{$earmarkKey}</option>
                                {/foreach}
                            </select>
                        </div>
                        </div>
                        {/if}

                    {* Donation to Specific Location *}
                    {elseif $formField->textId == 'locationList'}
                        {if $allowDonationsToBranch == 1}
                        <div class="col-xs-12">
                        <div class="form-group {$formField->textId}">
                            <label id="{$formField->textId}Label" for="{$formField->textId}" class="control-label">{translate text=$formField->label isPublicFacing=true isAdminEnteredData=true}{if $formField->required}<span class="requiredIndicator">*</span>{/if}</label>
                            <select name="toLocation" id="{$formField->textId}" class="form-control input-lg">
                                <option value=0 selected></option>
                                {foreach from=$donationLocations item=value key=locationKey}
                                    <option value={$value}>{$locationKey}</option>
                                {/foreach}
                            </select>
                        </div>
                        </div>
                        {/if}

                    {* Donation Dedication *}
                    {elseif $formField->textId == 'shouldBeDedicated'}
                        {if $allowDonationDedication == 1}
                        <div class="col-xs-12">
                        <div class="checkbox">
                            <label id="{$formField->textId}Label" for="{$formField->textId}" class="control-label">
                                <input type="checkbox" name="{$formField->textId}" id="{$formField->textId}">
                                {translate text=$formField->label isPublicFacing=true isAdminEnteredData=true}
                            </label>
                        </div>
                        </div>
                        {/if}

                    {elseif $formField->textId == 'dedicationType'}
                        {if $allowDonationDedication == 1}
                        <div class="col-xs-12">
                        <div class="form-group {$formField->textId}">
                            {foreach from=$donationDedications item=value key=dedicationKey}
                                <div class="radio-inline">
                                    <label class="control-label">
                                        <input type="radio" name="{$formField->textId}" id="{$formField->textId}-{$value}" value="{$value}">
                                        {$dedicationKey}
                                    </label>
                                </div>
                            {/foreach}
                        </div>
                        </div>
                        {/if}

                    {elseif $formField->textId == 'honoreeFirstName' || $formField->textId == 'honoreeLastName'}
                        {if $allowDonationDedication == 1}
                        <div class="col-xs-6">
                        <div class="form-group {$formField->textId}">
                            <label id="{$formField->textId}Label" for="{$formField->textId}" class="control-label">{translate text=$formField->label isPublicFacing=true isAdminEnteredData=true}</label>
                            <input type="text" name="{$formField->textId}" id="{$formField->textId}" class="form-control input-lg">
                        </div>
                        </div>
                        {/if}

                    {* USER INFORMATION *}
                    {elseif $formField->textId == 'firstName' || $formField->textId == 'lastName'}
                        <div class="col-xs-6">
                        <div class="form-group {$formField->textId}">
                            <label id="{$formField->textId}Label" for="{$formField->textId}" class="control-label">{translate text=$formField->label isPublicFacing=true isAdminEnteredData=true}</label>
                            <input type="text" name="{$formField->textId}" id="{$formField->textId}" class="form-control input-lg" {if $formField->textId == 'firstName' && $newDonation->firstName}value="{$newDonation->firstName}"{/if}{if $formField->textId == 'lastName' && $newDonation->lastName}value="{$newDonation->lastName}"{/if} autocomplete>
                        </div>
                        </div>

                    {elseif $formField->textId == 'makeAnonymous'}
                        <div class="col-xs-12">
                        <div class="checkbox">
                            <label id="{$formField->textId}Label" for="{$formField->textId}" class="control-label">
                                <input type="checkbox" name="{$formField->textId}" id="{$formField->textId}">
                                {translate text=$formField->label isPublicFacing=true isAdminEnteredData=true}
                            </label>
                        </div>
                        </div>

                    {elseif $formField->textId == 'emailAddress'}
                        <div class="col-xs-12">
                        <div class="form-group {$formField->textId}">
                            <label id="{$formField->textId}Label" class="control-label" for="{$formField->textId}">{translate text=$formField->label isPublicFacing=true isAdminEnteredData=true}</label>
                            <input type="email" name="{$formField->textId}" id="{$formField->textId}" class="form-control input-lg" value="{if $newDonation->email}{$newDonation->email}{/if}" autocomplete>
                            {if $formField->note}<span id="{$formField->textId}_helpBlock" class="help-block">{$formField->note}</span>{/if}
                        </div>
                        </div>

                    {* ADDITIONAL FIELDS *}
                    {elseif $formField->type == 'text'}
                        <div class="col-xs-12">
                        <div class="form-group {$formField->textId}">
                            <label id="{$formField->textId}Label" class="control-label" for="{$formField->textId}">{translate text=$formField->label isPublicFacing=true isAdminEnteredData=true}</label>
                            <input name="{$formField->textId}" id="{$formField->textId}" class="form-control input-lg">
                            {if $formField->note}<span id="{$formField->textId}_helpBlock" class="help-block">{$formField->note}</span>{/if}
                        </div>
                        </div>

                    {elseif $formField->type == 'textbox'}
                        <div class="col-xs-12">
                        <div class="form-group {$formField->textId}">
                            <label id="{$formField->textId}Label" class="control-label" for="{$formField->textId}">{translate text=$formField->label isPublicFacing=true isAdminEnteredData=true}</label>
                            <textarea id="{$formField->textId}" class="form-control" rows="3"></textarea>
                            {if $formField->note}<span id="{$formField->textId}_helpBlock" class="help-block">{$formField->note}</span>{/if}
                        </div>
                        </div>

                    {/if}
                    </div>
                {/foreach}
            </fieldset>
        {/foreach}
        {* Make Sure Id is always included when set, even if it isn't displayed *}
        {if empty($hasId) && !empty($newDonation->id)}
            <input type="hidden" name="id" id="id" value="{$newDonation->id}">
        {/if}
        {if $newDonation->donationSettingId}
            <input type="hidden" name="settingId" id="settingId" value="{$newDonation->donationSettingId}">
        {/if}
    </div>
{/strip}
{literal}
<script type="text/javascript">

    $("#shouldBeDedicated").change(function() {
        if ($(this).is(':checked')) {
            $('#dedicationTypeRow').show();
            $('#honoreeFirstNameRow').show();
            $('#honoreeLastNameRow').show();
        } else {
            $('#dedicationTypeRow').hide();
            $('#honoreeFirstNameRow').hide();
            $('#honoreeLastNameRow').hide();
        }
    });
    $("#shouldBeDedicated").trigger("change");

    $('#customAmount').click(function () {
        $('.btn.btn-default.btn-lg.predefinedAmount').removeClass("active");
        $('input[name="amount"]').attr("checked", false);
    });

</script>
{/literal}