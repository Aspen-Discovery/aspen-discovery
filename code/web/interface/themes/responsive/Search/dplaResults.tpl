	<div id="dplaSearchResults">
		{if !empty($showDplaDescription)}
		<h2>{translate text="More from Digital Public Library of America" isPublicFacing=true}</h2>
		<p>{translate text="The Digital Public Library of America brings together the riches of America’s libraries, archives, and museums, and makes them freely available to the world. It strives to contain the full breadth of human expression, from the written word, to works of art and culture, to records of America’s heritage, to the efforts and data of science. DPLA aims to expand this crucial realm of openly available materials, and make those riches more easily discovered and more widely usable and used." isPublicFacing=true}</p>
		{/if}
		{foreach from=$searchResults item=result}
			<div class="dplaResult row result">
				{if !empty($showCovers)}
					<div class="col-xs-2">
						{if !empty($result.object)}
							<a href="{$result.link}" aria-hidden="true">
								<img src="{$result.object}" class="listResultImage img-thumbnail img-responsive {$coverStyle}" alt="{$result.title}{if !empty($result.publisher)} {translate text="from" isPublicFacing=true} {$result.publisher}{/if}"/>
							</a>
						{/if}
					</div>
				{/if}
				<div class="{if !empty($showCovers)}col-xs-10{else}col-xs-12{/if}">
					<div class="result-title"><a href="{$result.link}">{$result.title}</a></div>
					{if !empty($result.publisher) || !empty($result.dataProvider)}
						<div class="row">
							<div class="result-label col-tn-3">
								{translate text="Provider" isPublicFacing=true}
							</div>
							<div class="result-value col-tn-8 notranslate">
								{if !empty($result.publisher)}
									{$result.publisher}
								{/if}
								{if !empty($result.publisher) && !empty($result.dataProvider)}
									<br>
								{/if}
								{if !empty($result.dataProvider)}
									{$result.dataProvider}
								{/if}
							</div>
						</div>
					{/if}
					{if !empty($result.format)}
						<div class="row">
							<div class="result-label col-tn-3">
								{translate text="Format" isPublicFacing=true}
							</div>
							<div class="result-value col-tn-8 notranslate">
								{$result.format}
							</div>
						</div>
					{/if}

					<p>{$result.description|truncate_html:450:"..."}</p>
				</div>
			</div>
		{/foreach}
	</div>