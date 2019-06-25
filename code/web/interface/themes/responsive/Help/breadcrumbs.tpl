<li>
	{if !empty($pageTitleShort)}
	<em>{$pageTitleShort}</em>
	{else}
	<em>{$pageTemplate|replace:'.tpl':''}</em>
	{/if}
	<span class="divider">&raquo;</span>
</li>