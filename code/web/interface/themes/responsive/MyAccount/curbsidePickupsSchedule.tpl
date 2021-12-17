<div>
    <table class="table table-striped">
        <thead>
        <tr>
            <th{if !$useNote} colspan="2"{/if}>{translate text="Date & Time" isPublicFacing=true}</th>
            <th>{translate text="Location" isPublicFacing=true}</th>
            {if $useNote}<th>{translate text=$noteLabel isPublicFacing=true isAdminEnteredData=true}</th>{/if}
            <th></th>
        </tr>
        </thead
        <tbody>

        {foreach from=$currentCurbsidePickups.pickups item=pickup name="pickupLoop"}
        <tr style="vertical-align: middle">
            <th scope="row" style="width: 25%; word-wrap: break-word"{if !$useNote} colspan="2"{/if}>
                <h4 style="margin: 0">
                    {$pickup->scheduled_pickup_datetime|date_format:"%b %e, %Y at %l:%M %p"}
                </h4>
                {if $pickup->staged_datetime}
                    <span class="label label-success">{translate text="Ready" isPublicFacing=true}</span>
                {else}
                    <span class="label label-warning">{translate text="Pending" isPublicFacing=true}</span>
                {/if}
            </th>
            <td style="width: 25%; word-wrap: break-word">
                {$pickup->branchname}
            </td>
            {if $useNote}<td style="width: 40%; word-wrap: break-word">
                <small><i>{$pickup->notes}</i></small>
            </td>{/if}
            <td style="width: 10%">
                <div class="btn btn-group-vertical">
                    {if $pickup->staged_datetime}
                        {if $allowCheckIn}
                            <button class="btn btn-primary btn-sm" onclick="return AspenDiscovery.Account.checkInCurbsidePickup('{$patron}', '{$pickup->id}')"><i class="fas fa-check"></i> {translate text="I'm here" isPublicFacing=true inAttribute=true}</button>
                        {else}
                            {if $pickupInstructions}
                                <a role="button" tabindex="0" class="btn btn-primary btn-sm" data-toggle="popover" data-trigger="focus" data-placement="left" data-title="{translate text='Checking-in' isPublicFacing=true}" data-content="{translate text=$pickupInstructions isPublicFacing=true isAdminEnteredData=true}" data-html="true" data-container="body"><i class="fas fa-check"></i> {translate text="I'm here" isPublicFacing=true inAttribute=true}</a>
                            {/if}
                        {/if}
                    {/if}
                    <button class="btn btn-default btn-sm" onclick="return AspenDiscovery.Account.getCancelCurbsidePickup('{$patron}', '{$pickup->id}')">{translate text="Cancel Pickup" isPublicFacing=true inAttribute=true}</button>
                </div>
            </td>
        </tr>
        {/foreach}
        </tbody>
    </table>
</div>