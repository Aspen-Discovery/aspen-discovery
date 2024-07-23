{strip}
    <div id="main-content" class="col-md-12">
        <h1>{translate text="NoveList API Data" isAdminFacing=true}</h1>
        {if $novelist_enabled}
            <form class="navbar form-inline row">
                <div class="form-group col-xs-12">
                    <label for="ISBN" class="control-label">{translate text="ISBN" isAdminFacing=true}</label>
                    <input id ="ISBN" type="text" name="id" class="form-control" value="">
                    <input type="hidden" name="settingId" value="">
                    <label for="allInfo" class="control-label">{translate text="Check this box to include data for all records in the series:" isAdminFacing=true}&nbsp;</label>
                    <input id ="allInfo" type="checkbox" name="allInfo"><br/>
                    <button class="btn btn-primary" type="submit">{translate text=Go isAdminFacing=true}</button>
                </div>
            </form>
            {$novelistAPIData}
        {else}
            <p>Novelist is not enabled for this library.</p>
        {/if}
    </div>
{/strip}