{if $loggedIn}
<a href="/MyAccount/Home">{translate text='Your Account'}</a> <span class="divider">&raquo;</span>
{/if}
{if !empty($pageTitleShort)}
<em>{$pageTitleShort}</em>
{/if}
<span class="divider">&raquo;</span>
