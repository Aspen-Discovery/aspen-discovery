{* This is a text-only email template; do not include HTML! *}
{$from} has sent you a record from the Anythink catalog.
------------------------------------------------------------

{$emailDetails}  {translate text="email_link"}: {$url}/{$activeRecordProfileModule}/{$recordID|escape:"url"}
------------------------------------------------------------

{if !empty($message)}
{translate text="Message From"} {$from}:
------------------------------------------------------------
{$message}
{/if}

------------------------------------------------------------
For information about events, resources available 24/7 and more, visit www.anythinklibraries.org
