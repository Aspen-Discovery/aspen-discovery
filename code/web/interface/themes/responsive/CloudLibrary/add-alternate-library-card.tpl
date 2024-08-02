{strip}
    <form method="post" action="" id="addAlternateLibraryCardForm" class="form">
        <input type="hidden" name="id" id="id" value="{$id}"/>
        <input type="hidden" name="patronId" id="patronId" value="{$patronId}"/>
        <input type="hidden" name="type" id="type" value="{$type}"/>
        <div>
            {if !empty($alternateLibraryCardFormMessage)}
                <div class="row">
                    <div class="col-xs-12">{$alternateLibraryCardFormMessage}</div>
                </div>
            {/if}
            <div class="form-group row">
                <label for="alternateLibraryCard" class="control-label col-xs-12 col-sm-4">
                    {if !empty($alternateLibraryCardLabel)}
                        {translate text=$alternateLibraryCardLabel isPublicFacing=true isAdminEnteredData=true}
                    {else}
                        Alternate Library Card
                    {/if}
                </label>
                <div class="col-md-6">
                    <input type="text" name="alternateLibraryCard" id="alternateLibraryCard" value="{$user->alternateLibraryCard}" maxlength="60" class="form-control" >
                </div>
            </div>
            {if !empty($showAlternateLibraryCardPassword)}
                <div class="form-group row">
                    <label for="alternateLibraryCardPassword" class="control-label col-xs-12 col-sm-4">
                        {if !empty($alternateLibraryCardPasswordLabel)}
                            {translate text=$alternateLibraryCardPasswordLabel isPublicFacing=true isAdminEnteredData=true}
                        {else}
                            Password/PIN
                        {/if}
                    </label>
                    <div class="col-md-6">
                        <input type="password" name="alternateLibraryCardPassword" id="alternateLibraryCardPassword" value="{$user->alternateLibraryCardPassword}"  maxlength="60" class="form-control">
                    </div>
                </div>
            {/if}
        </div>
    </form>
{/strip}