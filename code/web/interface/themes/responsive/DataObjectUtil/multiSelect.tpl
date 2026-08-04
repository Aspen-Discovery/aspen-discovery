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
						   <input class="{$propName}" id="{$propName}_{$propertyValue|escapeCSS}" name='{$propName}[]' type="checkbox" value='{$propertyValue}' {if is_array($propValue) && in_array($propertyValue, array_keys($propValue))}checked='checked'{/if} {if !empty($property.readOnly)}readonly disabled{/if} {if !empty($property.onchange)}onchange="{$property.onchange}"{/if}> {if !empty($property.translateValues)}{translate text=$propertyName|escape inAttribute=true isPublicFacing=$property.isPublicFacing isAdminFacing=$property.isAdminFacing }{else}{$propertyName|escape}{/if}<br>
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
		{elseif $property.listStyle == 'checkboxWithOptions'}
			{if empty($property.readOnly)}
				<div class="checkbox" style="margin-bottom: 15px;">
					<label for="selectAll{$propName}" style="cursor: pointer;">
						<input type="checkbox" name="selectAll{$propName}" id="selectAll{$propName}" onchange="AspenDiscovery.Admin.toggleAllCheckboxOptions('{$propName}', '#selectAll{$propName}');">
						<strong>{translate text="Select All" isAdminFacing=true}</strong>
					</label>
				</div>
			{/if}
			<div class="checkboxWithOptions" style="display: flex; flex-direction: column; gap: 8px;">
				{foreach from=$property.values item=propertyName key=propertyValue}
					{assign var="isChecked" value=false}
					{assign var="optionValues" value=[]}
					{if is_array($propValue) && array_key_exists($propertyValue, $propValue)}
						{assign var="isChecked" value=true}
						{assign var="optionValues" value=$propValue[$propertyValue]}
					{/if}
					<div class="panel panel-default checkboxWithOptionsItem" id="{$propName}_{$propertyValue|escapeCSS}_container">
						<div class="panel-body">
							<div class="row">
								<div class="col-xs-12">
									<label for="{$propName}_{$propertyValue|escapeCSS}" style="cursor: pointer;">
										<input class="{$propName}Checkbox" id="{$propName}_{$propertyValue|escapeCSS}" name='{$propName}[{$propertyValue}][_checked]' type="checkbox" value='1' {if $isChecked}checked='checked'{/if} {if !empty($property.readOnly)}readonly disabled{/if} onchange="AspenDiscovery.Admin.toggleCheckboxOptions('{$propName}_{$propertyValue|escapeCSS}');">
										{if !empty($property.translateValues)}{translate text=$propertyName|escape inAttribute=true isPublicFacing=$property.isPublicFacing isAdminFacing=$property.isAdminFacing }{else}{$propertyName|escape}{/if}
									</label>
								</div>
							</div>
							<div class="checkboxOptions" id="{$propName}_{$propertyValue|escapeCSS}_options" style="display: {if $isChecked}block{else}none{/if};">
								{if !empty($property.optionsStructure)}
									{foreach from=$property.optionsStructure key=optionKey item=optionProperty}
										<div class="form-group">
											<label for="{$propName}_{$propertyValue|escapeCSS}_{$optionKey}" style="font-weight: normal;">
												{$optionProperty.label}
												{if !empty($optionProperty.description)}
													<a id="{$propName}_{$propertyValue|escapeCSS}_{$optionKey}_tooltip" class="text-info" role="tooltip" tabindex="0" data-toggle="tooltip" data-placement="right" data-title="{$optionProperty.description|escape}">
														<i class="fas fa-question-circle"></i>
													</a>
												{/if}
											</label>
											{if $optionProperty.type == 'text' || $optionProperty.type == 'url'}
												<input type="{$optionProperty.type}" class="form-control" id="{$propName}_{$propertyValue|escapeCSS}_{$optionKey}" name="{$propName}[{$propertyValue}][{$optionKey}]" value="{if is_array($optionValues) && isset($optionValues[$optionKey])}{$optionValues[$optionKey]|escape}{/if}" {if !empty($optionProperty.maxLength)}maxlength="{$optionProperty.maxLength}"{/if} {if !empty($optionProperty.size)}size="{$optionProperty.size}"{/if} {if !empty($property.readOnly)}readonly disabled{/if}>
											{elseif $optionProperty.type == 'textarea'}
												<textarea class="form-control" id="{$propName}_{$propertyValue|escapeCSS}_{$optionKey}" name="{$propName}[{$propertyValue}][{$optionKey}]" {if !empty($optionProperty.rows)}rows="{$optionProperty.rows}"{/if} {if !empty($property.readOnly)}readonly disabled{/if}>{if is_array($optionValues) && isset($optionValues[$optionKey])}{$optionValues[$optionKey]|escape}{/if}</textarea>
											{/if}
										</div>
									{/foreach}
								{/if}
							</div>
						</div>
					</div>
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