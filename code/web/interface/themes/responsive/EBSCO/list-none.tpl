<p class="alert alert-info">{translate text='nohit_prefix'} - <b>{if $lookfor}{$lookfor|escape:"html"}{else}&lt;empty&gt;{/if}</b> - {translate text='nohit_suffix'}</p>

{if $parseError}
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

{include file="Search/spellingSuggestions.tpl"}

{include file="Search/searchSuggestions.tpl"}

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