{* This is a text-only email template; do not include HTML! *}
Book Cart Contents
--------------------------------------------------------------
{foreach from=$cartContents item=cartItem}
	{$cartItem.title} ({$cartItem.url})
	
{/foreach}
