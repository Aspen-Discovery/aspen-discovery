
<a href="{$path}/MyAccount/Home">{translate text='Your Account'}</a> <span class="divider">&raquo;</span>
{if $pageTitle}
<em>{$pageTitle}</em>
{else}
<em>{$pageTemplate|replace:'.tpl':''|capitalize|translate}</em>
{/if}
<span class="divider">&raquo;</span>
