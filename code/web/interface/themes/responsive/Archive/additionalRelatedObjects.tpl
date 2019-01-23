{strip}
{* Similar Structure to accordion-items.tpl *}
	{if $archiveMoreDetailsRelatedObjectsOrEntitiesDisplayMode == 'list'}
		{foreach from=$directlyRelatedObjects.objects item=entity}
			<a href='{$entity.link}'>
				{$entity.label}
			</a>
			{if $entity.role}
				&nbsp;({$entity.role})
			{/if}
			{if $entity.note}
				&nbsp;- {$entity.note}
			{/if}
			<br>
		{/foreach}
	{else}
		{foreach from=$directlyRelatedObjects.objects item=image}
			<figure class="browse-thumbnail-sorted">
				<a href="{$image.link}" {if $image.label}data-title="{$image.label|urlencode}"{/if}>
					<img src="{$image.image}" {if $image.label}alt="{$image.label|urlencode}"{/if}>
				</a>
				<figcaption class="explore-more-category-title">
					<strong>{$image.label}</strong>
					{if $image.role}
						&nbsp;({$image.role})
					{/if}
					{if $image.note}
						&nbsp;- {$image.note}
					{/if}
				</figcaption>
			</figure>
		{/foreach}
	{/if}
{/strip}