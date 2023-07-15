{strip}
{if count($whileYouWaitTitles) == 0}
	<div class="alert alert-info">Sorry, we could not find any additional titles.</div>
{else}
	<div class="row">
		{foreach from=$whileYouWaitTitles item=whileYouWaitTitle}
			<div class="col-tn-12 col-sm-4">
				<a href="{$whileYouWaitTitle.url}">
					<img src="{$whileYouWaitTitle.coverUrl}" class="listResultImage img-thumbnail {$coverStyle}" alt="{$whileYouWaitTitle.title|escape}">
				</a>
				{*<div class="formatIcons" style="padding-top: 5px">
					{foreach from=$whileYouWaitTitle.formatCategories item=formatCategory}
						{if $formatCategory.formatCategory != 'Other'}
							<span class="{if !empty($formatCategory.available)}available{/if}" style="padding: 2px;"><img src="{$formatCategory.image}" alt="{$formatCategory.formatCategory}"/></span>
						{else}
							<span class="{if !empty($formatCategory.available)}statusValue available{/if}" style="padding: 2px;"><i class="fas fa-box" aria-label="{$formatCategory.formatCategory}"></i></span>
                        {/if}
					{/foreach}
				*}
			</div>
		{/foreach}
	</div>
{/if}
{/strip}