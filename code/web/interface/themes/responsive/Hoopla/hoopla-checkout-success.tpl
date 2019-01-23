{strip}
	{if $hooplaUser}{* Linked User that is not the main user *}
		<p>
			Using card for {$hooplaUser->getNameAndLibraryLabel()} :
		</p>
	{/if}
	{if $hooplaPatronStatus}
		<div class="alert alert-info">
			You have <span class="badge">{$hooplaPatronStatus->currentlyBorrowed}</span> Hoopla title{if $hooplaPatronStatus->currentlyBorrowed > 1}s{/if} currently checked out. <br>
			You can borrow <span class="badge">{$hooplaPatronStatus->borrowsRemaining}</span> more Hoopla title{if $hooplaPatronStatus->borrowsRemaining != 1}s{/if} this month.
		</div>
	{/if}
{/strip}