{strip}
{* Recommendations *}
{if $topRecommendations}
    {foreach from=$topRecommendations item="recommendations"}
        {include file=$recommendations}
    {/foreach}
{/if}

<h2>{translate text='nohit_heading'}</h2>
<p class="error">{translate text='nohit_prefix'} - <b>{$lookfor|escape:"html"}</b> - {translate text='nohit_suffix'}</p>

{if $solrSearchDebug}
    <div id="solrSearchOptionsToggle" onclick="$('#solrSearchOptions').toggle()">Show Search Options</div>
    <div id="solrSearchOptions" style="display:none">
        <pre>Search options: {$solrSearchDebug}</pre>
    </div>
{/if}

{if $solrLinkDebug}
    <div id='solrLinkToggle' onclick='$("#solrLink").toggle()'>Show Solr Link</div>
    <div id='solrLink' style='display:none'>
        <pre>{$solrLinkDebug}</pre>
    </div>
{/if}

{if $parseError}
    <div class="alert alert-danger">
        {$parseError}
    </div>
{/if}

{if $spellingSuggestions}
    <div class="correction">{translate text='nohit_spelling'}:<br/>
    {foreach from=$spellingSuggestions item=details key=term name=termLoop}
      {$term|escape} &raquo; {foreach from=$details.suggestions item=data key=word name=suggestLoop}<a href="{$data.replace_url|escape}">{$word|escape}</a>{if $data.expand_url} <a href="{$data.expand_url|escape}"><img src="{$path}/images/silk/expand.png" alt="{translate text='spell_expand_alt'}"/></a> {/if}{if !$smarty.foreach.suggestLoop.last}, {/if}{/foreach}{if !$smarty.foreach.termLoop.last}<br/>{/if}
    {/foreach}
    </div>
    <br/>
{/if}

{if $userIsAdmin}
    <a href='{$path}/Admin/People?objectAction=addNew' class='btn btn-sm btn-info'>Add someone new</a>
{/if}
{/strip}