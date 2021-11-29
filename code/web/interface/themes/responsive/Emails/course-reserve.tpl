{* This is a text-only email template; do not include HTML! *}
{$list->getTitle()}
{$list->instructor}
------------------------------------------------------------
{if !empty($from)}
{translate text="This email was sent from"}: {$from}
{/if}
{if !empty($message)}
{translate text="Message From Sender"}:
{$message}
------------------------------------------------------------
{/if}
{if !empty($error)}
{$error}
------------------------------------------------------------
{else}
{foreach from=$titles item=title}

{$title->getTitle()}
{if !empty($title->getPrimaryAuthor())}
{$title->getPrimaryAuthor()}
{/if}
{$title->getLinkUrl(true)}

---------------------
{/foreach}
{/if}

