{if $lastSearch}
	<a href="{$lastSearch|escape}#record{$id|escape:"url"}">EBSCO Research {translate text="Search Results"}</a> <span class="divider">&raquo;</span>
{else}
	EBSCO Research <span class="divider">&raquo;</span>
{/if}
{if $breadcrumbText}
	<em>{$breadcrumbText|truncate:30:"..."|escape}</em> <span class="divider">&raquo;</span>
{/if}
{if !empty($recordCount)}
	{translate text="Showing"}
	{$recordStart} - {$recordEnd}
	{translate text='of'} {$recordCount|number_format}
{/if}