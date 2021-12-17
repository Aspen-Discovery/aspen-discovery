{if $showCurbsidePickups}
    {if $loggedIn}
        <div class="row">
            <div class="col-xs-12" id="curbside-pickups">
                {if $instructionNewPickup}
                    <div id="instructionNewPickup" style="margin-bottom: 3em;">
                        {translate text=$instructionNewPickup isPublicFacing=true isAdminEnteredData=true}
                    </div>
                {/if}
                <div class="col-sm-12">
                <form id="newCurbsidePickupForm">
                <div class="form-group">
                    <label for="pickupDate">{translate text="Pickup date and time" isPublicFacing=true}</label>
                    <input type="text" class="form-control input-lg" name="pickupDate" id="pickupDate"/>
                </div>
                <div class="panel-group accordion" id="availableTimeSlots" style="display: none;">
                    <div class="panel panel-default" id="morningTimeSlotsAccordion">
                        <div class="panel-heading" role="tab" id="headingMorningTimeSlots">
                            <h2 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#availableTimeSlots" href="#morningTimeSlotsGroup" aria-expanded="true" aria-controls="#morningTimeSlotsPanelBody">
                                    {translate text="Morning" isPublicFacing=true}
                                </a>
                            </h2>
                        </div>
                        <div class="panel-collapse collapse" id="morningTimeSlotsGroup" role="tabpanel" aria-labelledby="headingMorningTimeSlots">
                            <div class="panel-body">
                                <div id="morningTimeSlots" data-toggle="buttons"></div>
                            </div>
                        </div>
                    </div>
                    <div class="panel panel-default" id="afternoonTimeSlotsAccordion">
                        <div class="panel-heading" role="tab" id="headingAfternoonTimeSlots">
                            <h2 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#availableTimeSlots" href="#afternoonTimeSlotsGroup" aria-expanded="true" aria-controls="#afternoonTimeSlotsPanelBody">
                                    {translate text="Afternoon" isPublicFacing=true}
                                </a>
                            </h2>
                        </div>
                        <div class="panel-collapse collapse" id="afternoonTimeSlotsGroup" role="tabpanel" aria-labelledby="headingAfternoonTimeSlots">
                            <div class="panel-body">
                                <div id="afternoonTimeSlots" data-toggle="buttons"></div>
                            </div>
                        </div>
                    </div>
                    <div class="panel panel-default" id="eveningTimeSlotsAccordion">
                        <div class="panel-heading" role="tab" id="headingEveningTimeSlots">
                            <h2 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#availableTimeSlots" href="#eveningTimeSlotsGroup" aria-expanded="true" aria-controls="#eveningTimeSlotsPanelBody">
                                    {translate text="Evening" isPublicFacing=true}
                                </a>
                            </h2>
                        </div>
                        <div class="panel-collapse collapse" id="eveningTimeSlotsGroup" role="tabpanel" aria-labelledby="headingEveningTimeSlots">
                            <div class="panel-body">
                                <div id="eveningTimeSlots" data-toggle="buttons"></div>
                            </div>
                        </div>
                    </div>
                </div>
                {if $useNote}
                    <div class="form-group">
                        <label for="pickupNote">{translate text=$noteLabel isPublicFacing=true isAdminEnteredData=true}</label>
                        <textarea id="pickupNote" name="pickupNote" class="form-control input-lg" rows="3"></textarea>
                        <span class="help-block">{translate text=$noteInstruction isPublicFacing=true isAdminEnteredData=true}</span>
                    </div>
                {/if}
                    <input type="hidden" name="patronId" id="patronId" value="{$patronId}">
                    <input type="hidden" name="pickupLibrary" id="pickupLibrary" value="{$pickupLocation.code}">
                </form>
                </div>
            </div>
        </div>
    {/if}
{else}
    <div class="row">
        <div class="col-xs-12" id="curbside-pickups">
            <p class="h3">{translate text="Sorry, curbside pickups are not available at your library." isPublicFacing=true}</p>
        </div>
    </div>
{/if}

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script type="text/javascript">
    {literal}
    $(document).ready(function() {
        AspenDiscovery.Account.curbsidePickupScheduler({/literal}'{$pickupLocation.code}'{literal});
    });
    {/literal}
</script>