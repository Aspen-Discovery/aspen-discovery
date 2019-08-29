
<a href="{$path}/MyAccount/Home">{translate text='Your Account'}</a> <span class="divider">&raquo;</span>
{if !empty($sideload)}
	<em><a href="{$path}/SideLoads/SideLoads?objectAction=edit&id={$sideload->id}">{$sideload->name}</a></em> <span class="divider">&raquo;</span>
{/if}
{if $pageTitleShort}
	<em>{$pageTitleShort}</em> <span class="divider">&raquo;</span>
{/if}


