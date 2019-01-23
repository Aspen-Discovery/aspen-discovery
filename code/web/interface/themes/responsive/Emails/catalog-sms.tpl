{* This is a text-only email template; do not include HTML! *}
{if $callnumber}
{translate text="callnumber_abbrev"}: {$callnumber}
{/if}
{translate text="Location"}: {$availableAt}
{if $downloadLink}
{translate text="Download Link"}: {$downloadLink}
{/if}
{$title}
{$url}/{$activeRecordProfileModule}/{$recordID|escape:"url"}