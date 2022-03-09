{* This is a text-only email template; do not include HTML! *}
{translate text="This email was sent from %1%" 1=$from isPublicFacing=true}
------------------------------------------------------------

{if !empty($message)}
{translate text="Message From Sender" isPublicFacing=true}
{$message}

{/if}
  {translate text="Link" isPublicFacing=true} {$msgUrl}
------------------------------------------------------------

