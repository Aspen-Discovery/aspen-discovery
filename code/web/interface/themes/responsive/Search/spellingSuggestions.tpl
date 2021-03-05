{if !empty($spellingSuggestions)}
    <div class="correction">
        <h3>{translate text="Spelling Suggestions"}</h3>
        <p>{translate text="spelling_instructions" defaultText="Didn't find what you want?  Here are some alternative spellings that you can try."}</p>
        <div class="row">
            {foreach from=$spellingSuggestions item=data key=word name=suggestLoop}
                <div class="col-xs-6 col-sm-4 col-md-3 text-left">
                    <a class='btn btn-xs btn-default btn-block btn-wrap' href="{$data.replace_url|escape}">{$data.phrase|escape}</a>
                </div>
            {/foreach}
        </div>
    </div>
    <br>
{/if}