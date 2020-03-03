<h1 id="pageTitle">{translate text="Compare Objects"}</h1>

{if $error}
	<div class="alert alert-danger">
		{$error}
	</div>
{else}
	<table class="adminTable table table-responsive table-condensed smallText">
		<thead>
			<tr>
				<th>{translate text="Property Name"}</th>
				<th>{translate text="Value 1"} <a href="{$object1EditUrl}" class="btn btn-sm btn-default">{translate text="Edit"}</a></th>
				<th>{translate text="Value 2"}  <a href="{$object2EditUrl}" class="btn btn-sm btn-default">{translate text="Edit"}</a></th>
			</tr>
		</thead>
		<tbody>
		{foreach from=$properties item=property}
			<tr>
				<td><strong>{$property.name}</strong></td>
				<td class="{if $property.uniqueProperty}unique compareSame{elseif $property.value1 == $property.value2}compareSame{else}compareChanged{/if}">{$property.value1}</td>
				<td class="{if $property.uniqueProperty}unique compareSame{elseif $property.value1 == $property.value2}compareSame{else}compareChanged{/if}">{$property.value2}</td>
			</tr>
		{/foreach}
		</tbody>
		<tfoot>
			<tr>
				<th></th>
				<th><a href="{$object1EditUrl}" class="btn btn-sm btn-default">{translate text="Edit"}</a></th>
				<th><a href="{$object2EditUrl}" class="btn btn-sm btn-default">{translate text="Edit"}</a></th>
			</tr>
		</tfoot>
	</table>
{/if}