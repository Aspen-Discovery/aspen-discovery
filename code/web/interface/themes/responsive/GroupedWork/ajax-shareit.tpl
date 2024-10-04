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
	{foreach from=$shareItResults item=shareItTitle}
		<tr>
			<td>
				<a href="{$shareItTitle.link}" rel="external" onclick="window.open (this.href, 'child'); return false"><h5>{$shareItTitle.title|removeTrailingPunctuation|escape}</h5></a>
			</td>

			<td>
				{if !empty($shareItTitle.author)}<small>{$shareItTitle.author|escape}</small>{/if}
			</td>
			<td>
				{if !empty($shareItTitle.pubDate)}<small>{$shareItTitle.pubDate|escape}</small>{/if}
			</td>
			<td>
				{if !empty($shareItTitle.format)}<small>{$shareItTitle.format|escape}</small>{/if}
			</td>
		</tr>
	{/foreach}
</table>
