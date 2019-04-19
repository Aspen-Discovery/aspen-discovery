{if $searchId}
	<li>Genealogy {translate text="Search"} <span class="divider">&raquo;</span> {$lookfor|capitalize|escape:"html"} <span class="divider">&raquo;</span></li>
{elseif $subTemplate}
	<li>{translate text=$subTemplate|replace:'.tpl':''|capitalize|translate} <span class="divider">&raquo;</span></li>
{elseif $pageTemplate!=""}
	<li>{translate text=$pageTemplate|replace:'.tpl':''|capitalize|translate} <span class="divider">&raquo;</span></li>
{/if}
