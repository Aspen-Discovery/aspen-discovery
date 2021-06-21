{strip}
    {if $cancelResults.title && !is_array($cancelResults.title)}
        {* for single item results *}
        <p><strong>{$cancelResults.title|removeTrailingPunctuation}</strong></p>
    {/if}
    <div class="contents">
        {if $cancelResults.success}
            <div class="alert alert-success">{$cancelResults.message}</div>
        {else}
            {if is_array($cancelResults.message)}
                {*assign var=numFailed value=$cancelResults.message|@count*}
                {assign var=totalCancelled value=$cancelResults.titles|@count}
                <div class="alert alert-warning"><strong>{$cancelResults.numCancelled} of {$totalCancelled}</strong> holds were cancelled successfully.</div>
                {foreach from=$cancelResults.message item=message}
                    <div class="alert alert-danger">{$message}</div>
                {/foreach}
            {else}
                <div class="alert alert-danger">{$cancelResults.message}</div>
            {/if}
        {/if}
    </div>
{/strip}