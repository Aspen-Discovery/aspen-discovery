<table class="table-striped table table-condensed notranslate">
  <tr>
    <th>{translate text="Last File Modification Time" isPublicFacing=true}</th>
    <td>{$lastMarcModificationTime|date_format:"%b %d, %Y %r"}</td>
  </tr>
</table>

<table class="citation">
  {foreach from=$solrRecord key='field' item='values'}
    <tr>
      <th>{$field|escape}</th>
      <td>
        <div style="width: 500px; overflow: auto;">
        {foreach from=$values item='value'}
          {$value|escape}<br />
        {/foreach}
        </div>
      </td>
    </tr>
  {/foreach}
</table>