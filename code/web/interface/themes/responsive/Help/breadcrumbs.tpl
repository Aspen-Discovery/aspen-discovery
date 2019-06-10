<li>
	{if !empty($pageTitleShort)}
	<em>{$pageTitleShort}</em>
	{else}
	<em>{$pageTemplate|replace:'.tpl':''|capitalize|translate}</em>
	{/if}
	<span class="divider">&raquo;</span>
</li>