{if !empty($searchSuggestions)}
    <div id="searchSuggestions">
        <h3>{translate text="Similar Searches"}</h3>
        <p>{translate text="similar_search_instructions" defaultText="These searches are similar to the search you tried. Would you like to try one of these instead?"}</p>
        <div class="row">
            {foreach from=$searchSuggestions item=suggestion}
                <div class="col-xs-6 col-sm-4 col-md-3 text-left">
                    <a class='btn btn-xs btn-default btn-block' href="/Search/Results?lookfor={$suggestion.phrase|escape:url}&searchIndex={$searchIndex|escape:url}" title="{$suggestion.phrase|strip_tags}">{$suggestion.phrase|truncate:25:'...'}</a>
                </div>
            {/foreach}
        </div>
    </div>
{/if}