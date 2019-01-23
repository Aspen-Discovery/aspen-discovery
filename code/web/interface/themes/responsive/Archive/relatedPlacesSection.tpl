{strip}
	{if $mapsKey && $relatedPlaces.centerX && $relatedPlaces.centerY}
		<iframe width="100%" height="" frameborder="0" style="border:0" src="https://www.google.com/maps/embed/v1/place?q={$relatedPlaces.centerX|escape}%2C%20{$relatedPlaces.centerX|escape}&key={$mapsKey}" allowfullscreen></iframe>
	{/if}
	{include file="Archive/accordion-items.tpl" relatedItems=$relatedPlaces}
{/strip}