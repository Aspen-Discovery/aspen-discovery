{if $totalPages > 1}
	<div class="text-center">
		<div class="pagination btn-group btn-group-sm justify-content-end">
            {if $currentPage > 1}
				<a href="#" class="page-link btn btn-default btn-sm" onclick="AspenDiscovery.Account.loadSearchHistory('{$userSearchType}', {$currentPage - 1}, {$limit}, '{$sort}'); return false;">&laquo;</a>
            {/if}

            {for $i=1 to $totalPages}
	            <a href="#" class="page-link btn btn-default btn-sm {if $i == $currentPage}active{/if}" onclick="AspenDiscovery.Account.loadSearchHistory('{$userSearchType}', {$i}, {$limit}, '{$sort}'); return false;">{$i}</a>
            {/for}

            {if $currentPage < $totalPages}
				<a href="#" class="page-link btn btn-default btn-sm" onclick="AspenDiscovery.Account.loadSearchHistory('{$userSearchType}', {$currentPage + 1}, {$limit}, '{$sort}'); return false;">&raquo;</a>
            {/if}
		</div>
	</div>
{/if}
