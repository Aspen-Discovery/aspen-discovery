{foreach from=$structure item=property key=id}
	{if (!isset($property.hideInLists) || $property.hideInLists == false) && $property.type != 'section'}
		{translate text=$property.label isAdminFacing=true}
	{/if}
{/foreach}
{if isset($dataList) && is_array($dataList)}
	{foreach from=$dataList item=dataItem key=id}
		{foreach from=$structure item=property}
			{if (!isset($property.hideInLists) || $property.hideInLists == false) && $property.type != 'section'}
				{assign var=propName value=$property.property}
				{assign var=propValue value=$dataItem->$propName}
				{if $property.type == 'label'}
					{if empty($dataItem->class) || $dataItem->class != 'objectDeleted'}
							{$propValue}
					{/if}
				{elseif $property.type == 'regularExpression' || $property.type =='multilineRegularExpression'}
					{$propValue|escape}
				{elseif $property.type == 'text' || $property.type == 'hidden' || $property.type == 'file' || $property.type == 'integer' || $property.type == 'email' || $property.type == 'url'}
					{$propValue}
				{elseif $property.type == 'date'}
					{$propValue|date_format}
				{elseif $property.type == 'timestamp'}
					{if $propValue == 0}
						{if empty($property.unsetLabel)}
							{translate text="Never" isAdminFacing=true}
						{else}
							{translate text=$property.unsetLabel isAdminFacing=true}
						{/if}
					{else}
						{$propValue|date_format:"%D %T"}
					{/if}
				{elseif $property.type == 'partialDate'}
					{assign var=propNameMonth value=$property.propNameMonth}
					{assign var=propMonthValue value=$dataItem->$propNameMonth}
					{assign var=propNameDay value=$property.propNameDay}
					{assign var=propDayValue value=$dataItem->$propDayValue}
					{assign var=propNameYear value=$property.propNameYear}
					{assign var=propYearValue value=$dataItem->$propNameYear}
					{if !empty($propMonthValue)}$propMonthValue{else}??{/if}/{if !empty($propDayValue)}$propDayValue{else}??{/if}/{if !empty($propYearValue)}$propYearValue{else}??{/if}
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
							{* for csv output what delimiter should go here? *}
							{if array_key_exists($propertyValue, $propValue)}{$propertyName} delimiter {/if}
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
					{if ($propValue == 1)}{translate text="Yes" isAdminFacing=true}{elseif ($propValue == 0)}{translate text="No" isAdminFacing=true}{else}{$propValue}{/if}
				{elseif $property.type == 'image'}
{*									<img src="{$property.displayUrl}{$dataItem->id}" class="img-responsive" alt="{$propName}">*}
				{elseif $property.type == 'textarea'}
					{$propValue|truncate:255:'...'}
				{else}
					{translate text="Unknown type to display %1%" 1=$property.type isAdminFacing=true}
				{/if}
				{* CSV field delimiter *}
								,
			{/if}
		{/foreach}
		{* CSV record delimiter *}
	{/foreach}
{/if}
{*

					</tr>
					{/foreach}
			{/if}
			</tbody>
		</table>
	</div>

	{if !empty($pageLinks.all)}<div class="text-center">{$pageLinks.all}</div>{/if}

	<input type='hidden' name='objectAction' id='objectAction' value='' />
	{if !empty($canCompare)}
		<div class="btn-group">
			<button type='submit' value='compare' class="btn btn-default" onclick="$('#objectAction').val('compare');return AspenDiscovery.Admin.validateCompare();">{translate text='Compare' isAdminFacing=true}</button>
		</div>
	{/if}
	{if !empty($canBatchUpdate)}
		<div class="btn-group">
			<button type='submit' value='batchUpdate' class="btn btn-default" onclick="return AspenDiscovery.Admin.showBatchUpdateFieldForm('{$module}', '{$toolName}', 'selected')">{translate text='Batch Update Selected' isAdminFacing=true}</button>
			<button type='submit' value='batchUpdate' class="btn btn-default" onclick="return AspenDiscovery.Admin.showBatchUpdateFieldForm('{$module}', '{$toolName}', 'all')">{translate text='Batch Update All' isAdminFacing=true}</button>
		</div>
	{/if}
	{if !empty($canExportToCSV)}
		<div class="btn-group">
			<input type='submit' name='exportToCSV' value="{translate text='Export Selected to CSV' isAdminFacing=true}" class="btn btn-default" onclick="$('#objectAction').val('exportToCSV');" />
			<input type='submit' name='exportToCSV' value="{translate text='Export All to CSV' isAdminFacing=true}" class="btn btn-default" onclick="$('#objectAction').val('exportToCSV');" />
		</div>
	{/if}
	{if !empty($canAddNew)}
		<div class="btn-group">
			<button type='submit' value='addNew' class="btn btn-primary" onclick="$('#objectAction').val('addNew')"><i class="fas fa-plus"></i> {translate text='Add New' isAdminFacing=true}</button>
		</div>
	{/if}
	{if !empty($canFetchFromCommunity)}
		<div class="btn-group">
			<button type='submit' value='findGreenhouseContent' class="btn btn-default" onclick="return AspenDiscovery.Admin.showFindCommunityContentForm('{$module}', '{$toolName}', '{$objectType}')"><i class="fas fa-file-download"></i> {translate text='Import Community Content' isAdminFacing=true}</button>
		</div>
	{/if}
	<div class="btn-group">
		{foreach from=$customListActions item=customAction}
			<button type='submit' value='{$customAction.action}' class="btn btn-default" onclick="$('#objectAction').val('{$customAction.action}')">{translate text=$customAction.label isAdminFacing=true}</button>
		{/foreach}
	</div>

	{if !empty($canDelete) && $canBatchDelete}
	<div class="row" style="padding-top: 1em">
		<div class="btn-group btn-group-sm col-sm-12">
			<button type='submit' value='batchDelete' class="btn btn-danger" onclick="return AspenDiscovery.Admin.showBatchDeleteForm('{$module}', '{$toolName}', 'selected')"><i class="fas fa-trash"></i> {translate text='Batch Delete Selected' isAdminFacing=true}</button>
			<button type='submit' value='batchDelete' class="btn btn-danger" onclick="return AspenDiscovery.Admin.showBatchDeleteForm('{$module}', '{$toolName}', 'all')"><i class="fas fa-trash"></i> {translate text='Delete All' isAdminFacing=true}</button>
		</div>
	</div>
	{/if}
{if $canCompare || $canAddNew || $canBatchUpdate || $canFilter|| !empty($customListActions) || $canBatchDelete || $canFetchFromCommunity}
</form>
{/if}

{if !empty($showQuickFilterOnPropertiesList) && isset($dataList) && is_array($dataList) && count($dataList) > 5}
<script type="text/javascript">
	{literal}
	$("#adminTable").tablesorter({cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', widgets:['zebra', 'filter'] });
	{/literal}
</script>
{/if}
*}