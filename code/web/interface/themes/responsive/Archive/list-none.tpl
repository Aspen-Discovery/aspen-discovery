<p class="alert alert-info">{translate text='nohit_prefix'} - <b>{if $lookfor}{$lookfor|escape:"html"}{else}&lt;empty&gt;{/if}</b> - {translate text='nohit_suffix'}</p>

{if !empty($parseError)}
  <p class="error">{translate text='nohit_parse_error'}</p>
{/if}

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

{if $spellingSuggestions}
<div class="correction">{translate text='nohit_spelling'}:<br/>
{foreach from=$spellingSuggestions item=details key=term name=termLoop}
  {$term|escape} &raquo; {foreach from=$details.suggestions item=data key=word name=suggestLoop}<a href="{$data.replace_url|escape}">{$data.phrase|escape}</a>{if $data.expand_url} <a href="{$data.expand_url|escape}"><img src="{$path}/images/silk/expand.png" alt="{translate text='spell_expand_alt'}"/></a> {/if}{if !$smarty.foreach.suggestLoop.last}, {/if}{/foreach}{if !$smarty.foreach.termLoop.last}<br/>{/if}
{/foreach}
</div>
<br/>
{/if}

{if $showExploreMoreBar}
  <div id="explore-more-bar-placeholder"></div>
  <script type="text/javascript">
    $(document).ready(
      function () {ldelim}
        AspenDiscovery.Searches.loadExploreMoreBar('{$exploreMoreSection}', '{$exploreMoreSearchTerm|escape:"html"}');
      {rdelim}
    );
  </script>
{/if}
