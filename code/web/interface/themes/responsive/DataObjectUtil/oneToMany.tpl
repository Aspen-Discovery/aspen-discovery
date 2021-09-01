{strip}
<div class="controls table-responsive">
	<div class="oneToManyTable">
		<table id="{$propName}" class="{if $property.sortable}sortableProperty{/if} table table-striped table-sticky" title="Values for {$property.label}">
			<thead>
			<tr>
				{if $property.sortable}
					<th>{translate text="Sort" isAdminFacing=true}</th>
				{/if}
				{foreach from=$property.structure item=subProperty}
					{if in_array($subProperty.type, array('text', 'regularExpression', 'enum', 'date', 'checkbox', 'integer', 'textarea', 'html')) || ($subProperty.type == 'multiSelect' && $subProperty.listStyle == 'checkboxList') }
						<th{if in_array($subProperty.type, array('text', 'regularExpression', 'enum', 'html', 'multiSelect'))} style="min-width:150px"{/if}>{translate text=$subProperty.label isAdminFacing=true}</th>
					{/if}
				{/foreach}
				<th>{translate text="Actions" isAdminFacing=true}</th>
			</tr>
			</thead>
			<tbody>
			{foreach from=$propValue item=subObject}
				{assign var=subObjectId value=$subObject->getPrimaryKeyValue()}
				<tr id="{$propName}{$subObject->id}">
					<input type="hidden" id="{$propName}Id_{$subObject->id}" name="{$propName}Id[{$subObject->id}]" value="{$subObject->id}"/>
					{if $property.sortable}
						<td>
							<span class="glyphicon glyphicon-resize-vertical"></span>
							<input type="hidden" id="{$propName}Weight_{$subObject->id}" name="{$propName}Weight[{$subObject->id}]" value="{$subObject->weight}"/>
						</td>
					{/if}
					{foreach from=$property.structure item=subProperty}
						{if in_array($subProperty.type, array('text', 'regularExpression', 'enum', 'date', 'checkbox', 'integer', 'textarea', 'html')) }
							<td>
								{assign var=subPropName value=$subProperty.property}
								{assign var=subPropValue value=$subObject->$subPropName}
								{if $subProperty.type=='text' || $subProperty.type=='regularExpression' || $subProperty.type=='integer' || $subProperty.type=='html'}
									<input type="text" name="{$propName}_{$subPropName}[{$subObject->id}]" value="{$subPropValue|escape}" class="form-control{if $subProperty.type=="integer"} integer{/if}{if $subProperty.required == true} required{/if}">
								{elseif $subProperty.type=='date'}
									<input type="date" name="{$propName}_{$subPropName}[{$subObject->id}]" value="{$subPropValue|escape}" class="form-control{if $subProperty.required == true} required{/if}">
								{elseif $subProperty.type=='textarea'}
									<textarea name="{$propName}_{$subPropName}[{$subObject->id}]" class="form-control">{$subPropValue|escape}</textarea>
								{elseif $subProperty.type=='checkbox'}
									<input type='checkbox' name='{$propName}_{$subPropName}[{$subObject->id}]' {if $subPropValue == 1}checked='checked'{/if}/>
								{else}
									<select name='{$propName}_{$subPropName}[{$subObject->id}]' id='{$propName}{$subPropName}_{$subObject->id}' class='form-control {if $subProperty.required == true} required{/if}' {if !empty($subProperty.onchange)}onchange="{$subProperty.onchange}"{/if}>
										{foreach from=$subProperty.values item=propertyName key=propertyValue}
											<option value='{$propertyValue}' {if $subPropValue == $propertyValue}selected='selected'{/if}>{if !empty($subProperty.translateValues)}{translate text=$propertyName inAttribute=true isPublicFacing=$subProperty.isPublicFacing isAdminFacing=$subProperty.isAdminFacing }{else}{$propertyName}{/if}</option>
										{/foreach}
									</select>
								{/if}
							</td>
						{elseif $subProperty.type == 'multiSelect'}
							{if $subProperty.listStyle == 'checkboxList'}
								<td>
									<div class="checkbox">
										{*this assumes a simple array, eg list *}
										{assign var=subPropName value=$subProperty.property}
										{assign var=subPropValue value=$subObject->$subPropName}
										{foreach from=$subProperty.values item=propertyName}
											<input name='{$propName}_{$subPropName}[{$subObject->id}][]' type="checkbox" value='{$propertyName}' {if is_array($subPropValue) && in_array($propertyName, $subPropValue)}checked='checked'{/if}>
											{$propertyName}
											<br>
										{/foreach}
									</div>
								</td>
							{/if}
						{/if}
					{/foreach}
					<td>
						{if $property.editLink neq ''}
							<a href='{$property.editLink}?objectAction=edit&widgetListId={$subObject->id}&widgetId={$widgetid}' class="btn btn-sm btn-default" title="edit">
								{translate text="Edit" isAdminFacing=true}
							</a>
						{elseif $property.canEdit}
							{if method_exists($subObject, 'getEditLink')}
								<a href='{$subObject->getEditLink()}' title='Edit' class="btn btn-sm btn-default">
									{translate text="Edit" isAdminFacing=true}
								</a>
							{else}
								{translate text="Please add a getEditLink method to this object" isAdminFacing=true}
							{/if}
						{/if}
						{* link to delete*}
						<input type="hidden" id="{$propName}Deleted_{$subObject->id}" name="{$propName}Deleted[{$subObject->id}]" value="false">
						{* link to delete *}
						<a href="#" class="btn btn-sm btn-warning" onclick="if (confirm('{translate text='Are you sure you want to delete this?' inAttribute=true isAdminFacing=true})'){literal}{{/literal}$('#{$propName}Deleted_{$subObject->id}').val('true');$('#{$propName}{$subObject->id}').hide().find('.required').removeClass('required'){literal}}{/literal};return false;">
							{* On delete action, also remove class 'required' to turn off form validation of the deleted input; so that the form can be submitted by the user  *}
							{translate text="Delete" isAdminFacing=true}
						</a>
					</td>
				</tr>
				{foreachelse}
				<tr style="display:none">
					<td></td>
				</tr>
			{/foreach}
			</tbody>
		</table>
	</div>
	<div class="{$propName}Actions">
		<a href="#" onclick="addNew{$propName}();return false;" class="btn btn-primary btn-sm">{translate text="Add New" isAdminFacing=true}</a>
		{if $property.additionalOneToManyActions && $id}{* Only display these actions for an existing object *}
			<div class="btn-group pull-right">
				{foreach from=$property.additionalOneToManyActions item=action}
					<a class="btn {if $action.class}{$action.class}{else}btn-default{/if} btn-sm" href="{$action.url|replace:'$id':$id}">{translate text=$action.text isPublicFacing=true}</a>
				{/foreach}
			</div>
		{/if}
	</div>
	{/strip}
	<script type="text/javascript">
		{literal}$(function () {{/literal}
			{if $property.sortable}
			{literal}$('#{/literal}{$propName}{literal} tbody').sortable({
				update: function (event, ui) {
					$.each($(this).sortable('toArray'), function (index, value) {
						var inputId = '#{/literal}{$propName}Weight_' + value.substr({$propName|@strlen}); {literal}
						$(inputId).val(index + 1);
					});
				}
			});
			{/literal}
			{/if}
		{literal}});{/literal}
		var numAdditional{$propName} = 0;

		function addNew{$propName}{literal}() {
			numAdditional{/literal}{$propName}{literal} = numAdditional{/literal}{$propName}{literal} - 1;
			var newRow = "<tr>";
			{/literal}
			newRow += "<input type='hidden' id='{$propName}Id_" + numAdditional{$propName} + "' name='{$propName}Id[" + numAdditional{$propName} + "]' value='" + numAdditional{$propName} + "'>";
			{if $property.sortable}
			newRow += "<td><span class='glyphicon glyphicon-resize-vertical'></span>";
			newRow += "<input type='hidden' id='{$propName}Weight_" + numAdditional{$propName} + "' name='{$propName}Weight[" + numAdditional{$propName} + "]' value='" + (100 - numAdditional{$propName}) + "'>";
			newRow += "</td>";
			{/if}
			{foreach from=$property.structure item=subProperty}
			{if in_array($subProperty.type, array('text', 'regularExpression', 'enum', 'date', 'checkbox', 'integer', 'textarea', 'html')) }
			newRow += "<td>";
			{assign var=subPropName value=$subProperty.property}
			{assign var=subPropValue value=$subObject->$subPropName}
			{if $subProperty.type=='text' || $subProperty.type=='regularExpression' || $subProperty.type=='integer' || $subProperty.type=='textarea' || $subProperty.type=='html'}
			newRow += "<input type='text' name='{$propName}_{$subPropName}[" + numAdditional{$propName} + "]' value='{if $subProperty.default}{$subProperty.default}{/if}' class='form-control{if $subProperty.type=="integer"} integer{/if}{if $subProperty.required == true} required{/if}'>";
			{elseif $subProperty.type=='date'}
			newRow += "<input type='date' name='{$propName}_{$subPropName}[" + numAdditional{$propName} + "]' value='{if $subProperty.default}{$subProperty.default}{/if}' class='form-control{if $subProperty.required == true} required{/if}'>";
			{elseif $subProperty.type=='checkbox'}
			newRow += "<input type='checkbox' name='{$propName}_{$subPropName}[" + numAdditional{$propName} + "]' {if $subProperty.default == 1}checked='checked'{/if}>";
			{else}
			newRow += "<select name='{$propName}_{$subPropName}[" + numAdditional{$propName} + "]' id='{$propName}{$subPropName}_" + numAdditional{$propName} + "' class='form-control{if $subProperty.required == true} required{/if}' {if !empty($subProperty.onchange)}onchange=\"{$subProperty.onchange}\"{/if}>";
			{foreach from=$subProperty.values item=propertyName key=propertyValue}
			newRow += "<option value='{$propertyValue}' {if $subProperty.default == $propertyValue}selected='selected'{/if}>{$propertyName}</option>";
			{/foreach}
			newRow += "</select>";
			{/if}
			newRow += "</td>";
			{elseif $subProperty.type == 'multiSelect'}
			{if $subProperty.listStyle == 'checkboxList'}
			newRow += '<td>';
			newRow += '<div class="checkbox">';
			{*this assumes a simple array, eg list *}
			{assign var=subPropName value=$subProperty.property}
			{assign var=subPropValue value=$subObject->$subPropName}
			{foreach from=$subProperty.values item=propertyName}
			newRow += '<input name="{$propName}_{$subPropName}[' + numAdditional{$propName} + '][]" type="checkbox" value="{$propertyName}"> {$propertyName}<br>';
			{/foreach}
			newRow += '</div>';
			newRow += '</td>';
			{/if}
			{/if}
			{/foreach}
			newRow += "</tr>";
			{literal}
			$('#{/literal}{$propName}{literal} tr:last').after(newRow);
			return false;
		}
		{/literal}
	</script>
</div>