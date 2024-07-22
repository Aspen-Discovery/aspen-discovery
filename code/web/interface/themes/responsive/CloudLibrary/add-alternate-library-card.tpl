{strip}
    <form method="post" action="" id="addAlternateLibraryCardForm" class="form">
        <input type="hidden" name="id" id="id" value="{$id}"/>
        <input type="hidden" name="patronId" id="patronId" value="{$patronId}"/>
        <input type="hidden" name="type" id="type" value="{$type}"/>
        <div>
            <div class="form-group">
                <label for="alternateLibraryCard" class="control-label col-xs-12 col-sm-4">{translate text=$alternateLibraryCardLabel isPublicFacing=true isAdminEnteredData=true} </label>
                <div class="col-md-6">
                    <input type="text" name="alternateLibraryCard" id="alternateLibraryCard" value="{$user->alternateLibraryCard}" maxlength="60" class="form-control" >
                </div>
            </div>
            {if !empty($showAlternateLibraryCardPassword)}
                <br/><br/>
                <div class="form-group">
                    <label for="alternateLibraryCardPassword" class="control-label col-xs-12 col-sm-4">{translate text=$alternateLibraryCardPasswordLabel isPublicFacing=true isAdminEnteredData=true} </label>
                    <div class="col-md-6">
                        <input type="password" name="alternateLibraryCardPassword" id="alternateLibraryCardPassword" value="{$user->alternateLibraryCardPassword}"  maxlength="60" class="form-control">
                    </div>
                </div>
            {/if}
{*            <div class="form-group">*}
{*                <div class="col-md-6 col-md-offset-3 text-center">*}
{*                    <input type="submit" name="submit" value="{translate text="Update" isPublicFacing=true}" id="alternateLibraryCardFormSubmit" class="btn btn-primary">*}
{*                </div>*}
{*            </div>*}
        </div>
    </form>
{/strip}