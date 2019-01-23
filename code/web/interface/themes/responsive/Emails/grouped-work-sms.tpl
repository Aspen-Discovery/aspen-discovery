{* This is a text-only email template; do not include HTML! *}
{$title|truncate:30:'...'}
{if $author}
	By {$author|truncate:30:'...'}
{/if}
{if $shelfLocation}
{translate text="Shelf Location"}: {$shelfLocation}
{/if}
{if $callnumber}
{translate text="Call Number"}: {$callnumber}
{/if}
{$url}
