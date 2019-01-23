{if $validData}
<div class="sidegroup">
  <h4>Open Library {* Intentionally not translated -- this is a site name, not a phrase *}</h4>
  <div>{translate text='Results for'} {$subject} ...</div>
  <ul class="similar">
    {foreach from=$worksArray item=work}
      <li>
        <a href="http://openlibrary.org{$work.key}" title="{translate text='Get full text'}" rel="external" onclick="window.open (this.href, 'child'); return false">
          <span class="olSubjectCover">
          {if $work.cover_id}
            <img src="http://covers.openlibrary.org/b/{$work.cover_id_type|escape}/{$work.cover_id|escape}-S.jpg" class="olSubjectImage" alt="{$work.title|escape}" />
          {else}
            <img src="{$path}/images/noCover2.gif" class="olSubjectImage" alt="{$work.title|escape}" />
          {/if}
          </span>
          <span>{$work.title|truncate:50}</span>
          <span class="olSubjectAuthor">{translate text='by'} {$work.mainAuthor|truncate:40}</span>
        </a>
        <div class="clearer"></div>
      </li>
    {/foreach}
  </ul>
  <p class="olSubjectMore">
    <a href="http://openlibrary.org/subjects" title="Open Library" rel="external" onclick="window.open (this.href, 'child'); return false">
      {translate text='more'}...
    </a>
  </p>
</div>
{/if}