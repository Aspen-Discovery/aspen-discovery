{strip}
    <input type="hidden" id="eventRegistrationUserId-{$eventSourceId}" value="{$userId}">
    {if $numberOfSeats !== null}{include file="AspenEvents/seats.tpl"}{/if}
    {include file="AspenEvents/registrationUserSelector.tpl"}
    {include file="AspenEvents/registrationUserDetails.tpl"}
    {include file="AspenEvents/customRegistrationForm.tpl"}
{/strip}
