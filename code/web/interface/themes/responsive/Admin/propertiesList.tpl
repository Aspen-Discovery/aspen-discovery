<div class="row">
	<div class="col-xs-12 col-md-9">
		<h1 id="pageTitle">{$pageTitleShort}</h1>
	</div>
	<div class="col-xs-12 col-md-3 help-link">
        {if $instructions}<a href="{$instructions}"><img src="/images/silk/help.png" alt="Help" /> Documentation</a>{/if}
	</div>
</div>

{if $lastError}
	<div class="alert alert-danger">
		{$lastError}
	</div>
{/if}

<form action="" method="get" id='compare' class="form-inline">
	<div class='adminTableRegion'>
		<table class="adminTable table table-striped table-condensed smallText table-sticky" id="adminTable">
			<thead>
				<tr>
					{if $canCompare}
						<th>{translate text='Select'} </th>
					{/if}
					{foreach from=$structure item=property key=id}
						{if !isset($property.hideInLists) || $property.hideInLists == false}
						<th><label title='{$property.description}'>{$property.label|translate}</label></th>
						{/if}
					{/foreach}
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				{if isset($dataList) && is_array($dataList)}
					{foreach from=$dataList item=dataItem key=id}
					<tr class='{cycle values="odd,even"} {$dataItem->class}'>
						{if $canCompare}
							<td><input type="checkbox" class="selectedObject" name="selectedObject[{$id}]" aria-label="Select Item {$id}"> </td>
						{/if}
						{foreach from=$structure item=property}
							{assign var=propName value=$property.property}
							{assign var=propValue value=$dataItem->$propName}

							{if !isset($property.hideInLists) || $property.hideInLists == false}
								<td>
								{if $property.type == 'label'}
									{if $dataItem->class != 'objectDeleted'}
										<a href='/{$module}/{$toolName}?objectAction=edit&amp;id={$id}'> {$propValue}</a>
									{/if}
								{elseif $property.type == 'text' || $property.type == 'hidden' || $property.type == 'file' || $property.type == 'integer' || $property.type == 'email' || $property.type == 'url'}
									{$propValue}
								{elseif $property.type == 'date'}
									{$propValue|date_format}
								{elseif $property.type == 'timestamp'}
									{$propValue|date_format:"%D %T"}
								{elseif $property.type == 'partialDate'}
									{assign var=propNameMonth value=$property.propNameMonth}
									{assign var=propMonthValue value=$dataItem->$propNameMonth}
									{assign var=propNameDay value=$property.propNameDay}
									{assign var=propDayValue value=$dataItem->$propDayValue}
									{assign var=propNameYear value=$property.propNameYear}
									{assign var=propYearValue value=$dataItem->$propNameYear}
									{if $propMonthValue}$propMonthValue{else}??{/if}/{if $propDayValue}$propDayValue{else}??{/if}/{if $propYearValue}$propYearValue{else}??{/if}
								{elseif $property.type == 'currency'}
									{assign var=propDisplayFormat value=$property.displayFormat}
									${$propValue|string_format:$propDisplayFormat}
								{elseif $property.type == 'enum'}
									{foreach from=$property.values item=propertyName key=propertyValue}
										{if $propValue == $propertyValue}{$propertyName}{/if}
									{/foreach}
								{elseif $property.type == 'multiSelect'}
									{if is_array($propValue) && count($propValue) > 0}
										{foreach from=$property.values item=propertyName key=propertyValue}
											{if in_array($propertyValue, array_keys($propValue))}{$propertyName}<br/>{/if}
										{/foreach}
									{else}
										No values selected
									{/if}
								{elseif $property.type == 'oneToMany'}
									{if is_array($propValue) && count($propValue) > 0}
										{$propValue|@count}
									{else}
										Not set
									{/if}
								{elseif $property.type == 'checkbox'}
									{if ($propValue == 1)}Yes{else}No{/if}
								{else}
									Unknown type to display {$property.type}
								{/if}
								</td>
							{/if}
						{/foreach}
						{if $dataItem->class != 'objectDeleted'}
							<td>
								<div class="btn-group-vertical">
								<a href='/{$module}/{$toolName}?objectAction=edit&amp;id={$id}' class="btn btn-default btn-sm">Edit</a>
								<a href='/{$module}/{$toolName}?objectAction=history&amp;id={$id}' class="btn btn-default btn-sm">History</a>
								{if $additionalActions}
									{foreach from=$additionalActions item=action}
										<a href='{$action.path}&amp;id={$id}' class="btn btn-default btn-sm">{$action.name}</a>
									{/foreach}
								{/if}
								</div>
							</td>
						{/if}
					</tr>
					{/foreach}
			{/if}
			</tbody>
		</table>
	</div>

	<input type='hidden' name='objectAction' id='objectAction' value='' />
	{if $canCompare}
		<div class="btn-group">
			<button type='submit' value='compare' class="btn btn-default" onclick="$('#objectAction').val('compare');return AspenDiscovery.Admin.validateCompare();">{translate text='Compare'}</button>
		</div>
	{/if}
	{if $canAddNew}
		<div class="btn-group">
			<button type='submit' value='addNew' class="btn btn-primary" onclick="$('#objectAction').val('addNew')">{translate text='Add New'}</button>
		</div>
	{/if}
	<div class="btn-group">
		{foreach from=$customListActions item=customAction}
			<button type='submit' value='{$customAction.action}' class="btn btn-default" onclick="$('#objectAction').val('{$customAction.action}')">{$customAction.label}</button>
		{/foreach}
	</div>
</form>

{if isset($dataList) && is_array($dataList) && count($dataList) > 5}
<script type="text/javascript">
	{literal}
	$("#adminTable").tablesorter({cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', widgets:['zebra', 'filter'] });
	{/literal}
</script>
{/if}
