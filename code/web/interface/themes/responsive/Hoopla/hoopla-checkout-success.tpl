{strip}
	{if !empty($hooplaUser)}{* Linked User that is not the main user *}
		<p>
			Using card for {$hooplaUser->getNameAndLibraryLabel()} :
		</p>
	{/if}
	{if $hooplaPatronStatus}
		<div class="alert alert-info">
			You have <span class="badge">{$hooplaPatronStatus->numCheckedOut}</span> Hoopla title{if $hooplaPatronStatus->numCheckedOut > 1}s{/if} currently checked out. <br>
			You can borrow <span class="badge">{$hooplaPatronStatus->numCheckoutsRemaining}</span> more Hoopla title{if $hooplaPatronStatus->numCheckoutsRemaining != 1}s{/if} this month.
		</div>
	{/if}
{/strip}