{strip}
{* Recommendations *}
{if $topRecommendations}
    {foreach from=$topRecommendations item="recommendations"}
        {include file=$recommendations}
    {/foreach}
{/if}

<h1>{translate text="No Results Found" isPublicFacing=true}</h1>

<p class="alert alert-info">
    {if (empty($lookfor))}
        {translate text="Your search - <b>&lt;empty&gt;</b> - did not match any resources." isPublicFacing=true}
    {else}
        {translate text="Your search - <b>%1%</b> - did not match any resources." 1=$lookfor|escape:html isPublicFacing=true}
    {/if}
</p>

{if !empty($solrSearchDebug)}
    <div id="solrSearchOptionsToggle" onclick="$('#solrSearchOptions').toggle()">{translate text="Show Search Options" isPublicFacing=true}</div>
    <div id="solrSearchOptions" style="display:none">
        <pre>{translate text="Search options" isPublicFacing=true} {$solrSearchDebug}</pre>
    </div>
{/if}

{if !empty($solrLinkDebug)}
    <div id='solrLinkToggle' onclick='$("#solrLink").toggle()'>{translate text="Show Solr Link" isPublicFacing=true}</div>
    <div id='solrLink' style='display:none'>
        <pre>{$solrLinkDebug}</pre>
    </div>
{/if}

{if !empty($parseError)}
    <div class="alert alert-danger">
        {$parseError}
    </div>
{/if}

{include file="Search/spellingSuggestions.tpl"}

{include file="Search/searchSuggestions.tpl"}

{if $userIsAdmin}
    <a href='/Admin/People?objectAction=addNew' class='btn btn-sm btn-info'>{translate text="Add someone new" isPublicFacing=true}</a>
{/if}
{/strip}