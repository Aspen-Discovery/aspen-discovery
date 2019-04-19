{if $searchId}
	<li>{translate text="Catalog Search"} <span class="divider">&raquo;</span> <em>{$lookfor|capitalize|escape:"html"}</em> <span class="divider">&raquo;</span></li>
{elseif $subTemplate}
	<li>{translate text=$subTemplate|replace:'.tpl':''|capitalize|translate} <span class="divider">&raquo;</span></li>
{elseif $pageTemplate!=""}
	<li>{translate text=$pageTemplate|replace:'.tpl':''|capitalize|translate} <span class="divider">&raquo;</span></li>
{/if}
