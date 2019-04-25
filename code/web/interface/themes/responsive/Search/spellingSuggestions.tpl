{if !empty($spellingSuggestions)}
    <div class="correction">
        <h3>Spelling Suggestions</h3>
        <p>Didn't find what you want?  Here are some alternative spellings that you can try.</p>
        {*				{foreach from=$spellingSuggestions item=url key=term name=termLoop}*}
        {*					<div class="col-xs-6 col-sm-4 col-md-3 text-left">*}
        {*						<a class='btn btn-xs btn-default btn-block' href="{$url|escape}">{$term|escape|truncate:25:'...'}</a>*}
        {*					</div>*}
        {*				{/foreach}*}
        <div class="row">
            {foreach from=$spellingSuggestions item=data key=word name=suggestLoop}
                <div class="col-xs-6 col-sm-4 col-md-3 text-left">
                    <a class='btn btn-xs btn-default btn-block' href="{$data.replace_url|escape}">{$word|escape}{if $data.freq != 0} ({$data.freq}){/if}</a>
                </div>
            {/foreach}

        </div>
    </div>
    <br>
{/if}