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
            <div class="form-group propertyRow">
                <label for="alternateLibraryCard" class="control-label">
                    {if !empty($alternateLibraryCardLabel)}
                        {translate text=$alternateLibraryCardLabel isPublicFacing=true isAdminEnteredData=true}
                    {else}
                        {translate text="Alternate Library Card" isPublicFacing=true isAdminEnteredData=false}
                    {/if}
                </label>
                <div>
                    <input type="text" name="alternateLibraryCard" id="alternateLibraryCard" value="{$user->alternateLibraryCard}" maxlength="60" class="form-control" >
                </div>
            </div>
            {if !empty($showAlternateLibraryCardPassword)}
                <div class="form-group propertyRow">
                    <label for="alternateLibraryCardPassword" class="control-label">
                        {if !empty($alternateLibraryCardPasswordLabel)}
                            {translate text=$alternateLibraryCardPasswordLabel isPublicFacing=true isAdminEnteredData=true}
                        {else}
                            {translate text="Password/PIN" isPublicFacing=true isAdminEnteredData=false}
                        {/if}
                    </label>
                    <div>
                        <input type="password" name="alternateLibraryCardPassword" id="alternateLibraryCardPassword" value="{$user->alternateLibraryCardPassword}"  maxlength="60" class="form-control">
                    </div>
                </div>
            {/if}
        </div>
    </form>
{/strip}