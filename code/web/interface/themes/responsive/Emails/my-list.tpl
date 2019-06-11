{* This is a text-only email template; do not include HTML! *}
{$list->title}
{$list->description}
------------------------------------------------------------
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

{if $title.title_display}{$title.title_display}
{$title.author_display}
{$url}/GroupedWork/{$title.id}/Home
{elseif $title.fgs_label_s}{$title.fgs_label_s}
{$title.format}
{if $title.url}{$url}{$title.url}{/if}{/if}

{section name=listEntry loop=$listEntries}
{*If the listEntry has a note see if it is the same work*}
{if $listEntries[listEntry]->notes && ($listEntries[listEntry]->groupedWorkPermanentId == $title.id || $listEntries[listEntry]->groupedWorkPermanentId == $title.PID)}
{translate text="Notes"}: {$listEntries[listEntry]->notes}

{/if}
{/section}
---------------------
{/foreach}
{/if}

