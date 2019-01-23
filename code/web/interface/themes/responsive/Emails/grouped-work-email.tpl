{* This is a text-only email template; do not include HTML! *}
{if $from}
{translate text="This email was sent from"}: {$from}
{/if}
------------------------------------------------------------
{$recordDriver->getTitle()}
{if $recordDriver->getPrimaryAuthor()}
  By {$recordDriver->getPrimaryAuthor()}
{/if}
{if $callnumber}
	{translate text="Call Number"}: {$callnumber}
{/if}
{if $shelfLocation}
	{translate text="Shelf Location"}: {$shelfLocation}
{/if}

{translate text="email_link"}: {$url}
------------------------------------------------------------

{if !empty($message)}
{translate text="Message From Sender"}:
{$message}
{/if}
