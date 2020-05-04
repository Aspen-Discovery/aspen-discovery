{strip}
{if count($whileYouWaitTitles) == 0}
	<div class="alert alert-info">Sorry, we could not find any additional titles.</div>
{else}
	<div class="row">
		{foreach from=$whileYouWaitTitles item=whileYouWaitTitle}
			<div class="col-tn-12 col-sm-4" style="text-align: center">
				<div class="row">
					<div class="col-tn-12">
						<a href="{$whileYouWaitTitle.url}">
							<img src="{$whileYouWaitTitle.coverUrl}" class="listResultImage img-thumbnail" alt="{$whileYouWaitTitle.title|escape}">
						</a>
						<div class="formatIcons" style="padding-top: 5px">
							{foreach from=$whileYouWaitTitle.formatCategories item=formatCategory}
								<span class="{if $formatCategory.available}available{/if}" style="padding: 2px;"><img src="{img filename=$formatCategory.image}" alt="{$formatCategory.formatCategory}"/></span>
							{/foreach}
						</div>
					</div>
				</div>
{*				<div class="row">*}
{*					<div class="col-tn-12" style="padding-top: 5px">*}
{*					<a href="{$whileYouWaitTitle.url}" class="btn btn-primary btn-sm">{translate text="More Info"}</a>*}
{*					</div>*}
{*				</div>*}
			</div>
		{/foreach}
	</div>
{/if}
{/strip}