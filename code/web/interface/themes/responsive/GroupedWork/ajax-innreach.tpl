<table class="table table-striped">
	<th>
		{translate text="Title" isPublicFacing=true}
	</th>
	<th>
		{translate text="Author" isPublicFacing=true}
	</th>
	<th>
		{translate text="Pub. Date" isPublicFacing=true}
	</th>
	<th>
		{translate text="Format" isPublicFacing=true}
	</th>
  {foreach from=$innReachResults item=innReachTitle}
	  {if $similar.recordId != -1}
		  <tr>
			  <td>
		      <a href="{$innReachTitle.link}" rel="external" onclick="window.open (this.href, 'child'); return false"><h5>{$innReachTitle.title|removeTrailingPunctuation|escape}</h5></a>
			  </td>

		    <td>
				  {if !empty($innReachTitle.author)}<small>{$innReachTitle.author|escape}</small>{/if}
		    </td>
			  <td>
				  {if !empty($innReachTitle.pubDate)}<small>{$innReachTitle.pubDate|escape}</small>{/if}
			  </td>
			  <td>
				  {if !empty($innReachTitle.format)}<small>{$innReachTitle.format|escape}</small>{/if}
			  </td>
		  </tr>
	  {/if}
  {/foreach}
</table>
