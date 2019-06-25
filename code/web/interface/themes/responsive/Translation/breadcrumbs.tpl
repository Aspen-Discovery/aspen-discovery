<a href="{$path}/MyAccount/Home">{translate text='Your Account'}</a> <span class="divider">&raquo;</span>
{if $pageTitleShort}
<em>{$pageTitleShort}</em>
{elseif $pageTitle}
<em>{$pageTitle}</em>
{else}
<em>{$pageTemplate|replace:'.tpl':''}</em>
{/if}
<span class="divider">&raquo;</span>