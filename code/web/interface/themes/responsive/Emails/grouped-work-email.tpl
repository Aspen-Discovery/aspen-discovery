{* This is a text-only email template; do not include HTML! *}
{if $from}
{translate text="This email was sent from %1%" 1=$from isPublicFacing=true}
{/if}
------------------------------------------------------------
{$recordDriver->getTitle()}
{if $recordDriver->getPrimaryAuthor()}
  By {$recordDriver->getPrimaryAuthor()}
{/if}
{if !empty($callnumber)}
	{translate text="Call Number" isPublicFacing=true} {$callnumber}
{/if}
{if !empty($shelfLocation)}
	{translate text="Shelf Location" isPublicFacing=true} {$shelfLocation}
{/if}

{translate text="Link" isPublicFacing=true} {$url}
------------------------------------------------------------

{if !empty($message)}
{translate text="Message From Sender" isPublicFacing=true}
{$message}
{/if}
