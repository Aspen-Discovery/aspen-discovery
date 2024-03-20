<div class="controls">
	{if isset($property.listStyle)}
		{if $property.listStyle == 'checkbox'}
			<div class="checkbox">
				{* Original Behavior *}
				{foreach from=$property.values item=propertyName key=propertyValue}
					<input name='{$propName}[{$propertyValue}]' type="checkbox" value='{$propertyValue}' {if is_array($propValue) && in_array($propertyValue, array_keys($propValue))}checked='checked'{/if} {if !empty($property.readOnly)}readonly disabled{/if}> {if !empty($property.translateValues)}{translate text=$propertyName inAttribute=true isPublicFacing=$property.isPublicFacing isAdminFacing=$property.isAdminFacing }{else}{$propertyName}{/if}<br>
				{/foreach}
			</div>
		{elseif $property.listStyle == 'checkboxSimple'}
			{if empty($property.readOnly)}
				<div class="form-group checkbox">
					<label for="selectAll{$propName}">
						<input type="checkbox" name="selectAll{$propName}" id="selectAll{$propName}" onchange="AspenDiscovery.toggleCheckboxes('.{$propName}', '#selectAll{$propName}');">
						<strong>{translate text="Select All" isAdminFacing=true}</strong>
					</label>
				</div>
			{/if}
			<div class="checkbox">
				{* Modified Behavior: $propertyValue is used only as a display name to the user *}
				{foreach from=$property.values item=propertyName key=propertyValue}
					<label for="{$propName}_{$propertyValue|escapeCSS}">
						<input class="{$propName}" id="{$propName}_{$propertyValue|escapeCSS}" name='{$propName}[]' type="checkbox" value='{$propertyValue}' {if is_array($propValue) && array_key_exists($propertyValue, $propValue)}checked='checked'{/if} {if !empty($property.readOnly)}readonly disabled{/if}> {if !empty($property.translateValues)}{translate text=$propertyName|escape inAttribute=true isPublicFacing=$property.isPublicFacing isAdminFacing=$property.isAdminFacing }{else}{$propertyName|escape}{/if}<br>
					</label>
				{/foreach}
			</div>
		{elseif $property.listStyle == 'checkboxList'}
			<div class="checkbox">
				{*this assumes a simple array, eg list *}
				{foreach from=$property.values item=propertyName}
					<input name='{$propName}[]' type="checkbox" value='{$propertyName}' {if is_array($propValue) && in_array($propertyName, $propValue)}checked='checked'{/if} {if !empty($property.readOnly)}readonly disabled{/if}> {if !empty($property.translateValues)}{translate text=$propertyName|escape inAttribute=true isPublicFacing=$property.isPublicFacing isAdminFacing=$property.isAdminFacing }{else}{$propertyName|escape}{/if}<br>
				{/foreach}
			</div>
		{/if}
	{else}
		<br />
		<select name='{$propName}' id='{$propName}' multiple="multiple" {if !empty($property.readOnly)}readonly disabled{/if}>
		{foreach from=$property.values item=propertyName key=propertyValue}
			<option value='{$propertyValue}' {if $propValue == $propertyValue}selected='selected'{/if}>{if !empty($property.translateValues)}{translate text=$propertyName|escape inAttribute=true isPublicFacing=$property.isPublicFacing isAdminFacing=$property.isAdminFacing }{else}{$propertyName|escape}{/if}</option>
		{/foreach}
		</select>
	{/if}
</div>