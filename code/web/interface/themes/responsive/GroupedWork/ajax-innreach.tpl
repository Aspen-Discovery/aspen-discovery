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
  {foreach from=$prospectorResults item=prospectorTitle}
	  {if $similar.recordId != -1}
		  <tr>
			  <td>
		      <a href="{$prospectorTitle.link}" rel="external" onclick="window.open (this.href, 'child'); return false"><h5>{$prospectorTitle.title|removeTrailingPunctuation|escape}</h5></a>
			  </td>

		    <td>
				  {if $prospectorTitle.author}<small>{$prospectorTitle.author|escape}</small>{/if}
		    </td>
			  <td>
				  {if $prospectorTitle.pubDate}<small>{$prospectorTitle.pubDate|escape}</small>{/if}
			  </td>
			  <td>
				  {if $prospectorTitle.format}<small>{$prospectorTitle.format|escape}</small>{/if}
			  </td>
		  </tr>
	  {/if}
  {/foreach}
</table>
