
{if $lastsearch}<li><a href="{$lastsearch|escape}">{translate text="Search"}</a> <span class="divider">&raquo;</span></li>{/if}
 
{if $pageTemplate=="home.tpl"}<li><em>{$author.0|escape}, {$author.1|escape}</em> <span class="divider">&raquo;</span></li>{/if}

{if $pageTemplate=="list.tpl"}<li><em>{translate text="Author Results for"} {$lookfor|escape}</em> <span class="divider">&raquo;</span></li>{/if}
