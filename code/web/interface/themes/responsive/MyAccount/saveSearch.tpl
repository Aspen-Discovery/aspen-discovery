{strip}
<form enctype="multipart/form-data" method="post" id="saveSearchForm" action="/MyAccount/AJAX">
    <input type="hidden" name="searchId" value="{$searchId}" id="searchId">
    <input type="hidden" name="method" value="saveSearch">
    <div id="saveSearchComments">
        <p class="alert alert-info">
            {translate text="Please enter a name for the search to be saved." isPublicFacing=true}
        </p>
    </div>
    <div class="form-group">
    <b>{translate text="Search" isPublicFacing=true}</b>: {$thisSearch.description}<br>
    {if !empty($thisSearch.filters)}
        {foreach from=$thisSearch.filters item=filters key=field}
            {foreach from=$filters item=filter}
                <b>{translate text=$field|escape isPublicFacing=true}</b>: {$filter.display|escape}<br>
            {/foreach}
        {/foreach}
    {/if}
    </div>
    <div class="form-group">
        <label for="searchName" class="control-label">{translate text="Name for Saved Search" isPublicFacing=true}</label>
        <input type="text" id="searchName" name="searchName" value="" class="form-control required">
    </div>
</form>
<script type="application/javascript">
    {literal}
    $("#saveSearchForm").validate({
        submitHandler: function(){
            AspenDiscovery.Account.saveSearch()
        }
    });
    {/literal}
</script>
{/strip}