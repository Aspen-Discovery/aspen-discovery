{strip}
		{if $hooplaUser}{* Linked User that is not the main user *}
			<p>
				{translate text="Using card for %1%" 1=$hooplaUser->getNameAndLibraryLabel() isPublicFacing=true}
			</p>
		{/if}
	{if $hooplaPatronStatus}
		<div class="alert alert-info">
			{translate text="You have <span class='badge'>%1%</span> Hoopla check outs remaining this month. Proceed with checkout?" 1=$hooplaPatronStatus->numCheckoutsRemaining isPublicFacing=true}
		</div>
		<br>
		<div class="form-group">
			<label for="stopHooplaConfirmation" class="checkbox"><input type="checkbox" name="stopHooplaConfirmation" id="stopHooplaConfirmation"> {translate text="Don't ask again. (This can be changed under your Account Settings)" isPublicFacing=true}</label>
		</div>
	{else}
		<div class="alert alert-info">
			{translate text="You haven't created an account at Hoopla yet. Would you like to do so now?" isPublicFacing=true}
		</div>
	{/if}
{/strip}