<select name='{$propName}' id='{$propName}Select' class="form-control"{if !empty($property.onchange)}onchange="{$property.onchange}"{/if}>
{foreach from=$property.values item=propertyName key=propertyValue}
	<option value='{$propertyValue}'{if $propValue == $propertyValue} selected='selected'{/if}>{$propertyName}</option>
{/foreach}
</select>
