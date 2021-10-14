{strip}
	{if !empty($hooplaUser)}{* Linked User that is not the main user *}
		<p>
			{translate text="Using card for %1%" 1=$hooplaUser->getNameAndLibraryLabel() isPublicFacing=true}
		</p>
	{/if}
	{if $hooplaPatronStatus}
		<div class="alert alert-info">
			{if $hooplaPatronStatus->numCheckedOut == 1}
				{translate text="You have 1 Hoopla title currently checked out." isPublicFacing=true}
			{else}
				{translate text="You have %1% Hoopla titles currently checked out." 1=$hooplaPatronStatus->numCheckedOut isPublicFacing=true}
			{/if}
			<br>
			{if $hooplaPatronStatus->numCheckoutsRemaining == 1}
				{translate text="You can borrow 1 more Hoopla title this month." isPublicFacing=true}
			{else}
				{translate text="You can borrow %1% more Hoopla titles this month." 1=$hooplaPatronStatus->numCheckoutsRemaining isPublicFacing=true}
			{/if}
		</div>
	{/if}
{/strip}