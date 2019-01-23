{strip}
	<a href='{$randomObject.link}'>
		<img src="{$randomObject.image}" alt="{$randomObject.label|escape}">
		<figcaption class="explore-more-category-title">
			<strong>{$randomObject.label|truncate:120}</strong>
		</figcaption>
	</a>
{/strip}