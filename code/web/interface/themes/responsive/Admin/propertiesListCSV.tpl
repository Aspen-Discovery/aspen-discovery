{strip}
	{foreach from=$structure item=property key=id}
		{if (!isset($property.hideInLists) || $property.hideInLists == false) && $property.type != 'section'}
			{$columnHeader = {translate text=$property.label isAdminFacing=true}}
			{$columnHeader|replace:'"':'""'|regex_replace:'/^.*,.*$/':'"$0"'}
			{* CSV field delimiter *}
			,
		{/if}
	{/foreach}
	{* CSV record delimiter *}
	{"\n"}
	{if isset($dataList) && is_array($dataList)}
		{foreach from=$dataList item=dataItem key=id}
			{foreach from=$structure item=property}
				{if (!isset($property.hideInLists) || $property.hideInLists == false) && $property.type != 'section'}
					{$value = ""}
					{assign var=propName value=$property.property}
					{assign var=propValue value=$dataItem->$propName}
					{if $property.type == 'label'}
						{if empty($dataItem->class) || $dataItem->class != 'objectDeleted'}
								{$value = $propValue}
						{/if}
					{elseif $property.type == 'regularExpression' || $property.type =='multilineRegularExpression'}
						{$value = $propValue|escape}
					{elseif $property.type == 'text' || $property.type == 'hidden' || $property.type == 'file' || $property.type == 'integer' || $property.type == 'email' || $property.type == 'url'}
						{$value = $propValue}
					{elseif $property.type == 'date'}
						{$value = {$propValue|date_format}}
					{elseif $property.type == 'timestamp'}
						{if $propValue == 0}
							{if empty($property.unsetLabel)}
								{$value = {translate text="Never" isAdminFacing=true}}
							{else}
								{$value = {translate text=$property.unsetLabel isAdminFacing=true}}
							{/if}
						{else}
							{$value = {$propValue|date_format:"%D %T"}}
						{/if}
					{elseif $property.type == 'partialDate'}
						{assign var=propNameMonth value=$property.propNameMonth}
						{assign var=propMonthValue value=$dataItem->$propNameMonth}
						{assign var=propNameDay value=$property.propNameDay}
						{assign var=propDayValue value=$dataItem->$propDayValue}
						{assign var=propNameYear value=$property.propNameYear}
						{assign var=propYearValue value=$dataItem->$propNameYear}
						{if !empty($propMonthValue)}
							{$value = $propMonthValue}
						{else}
							{$value = "??"}
						{/if}
						{if !empty($propDayValue)}
							{$value = {$value|cat:$propDayValue}}
						{else}
							{$value = {$value|cat:'??'}}
						{/if}
						{if !empty($propYearValue)}
							{$value = {$value|cat:$propYearValue}}
						{else}
							{$value = {$value|cat:'??'}}
						{/if}
					{elseif $property.type == 'currency'}
						{assign var=propDisplayFormat value=$property.displayFormat}
						{$value = '$'|cat:{$propValue|string_format:$propDisplayFormat}}
					{elseif $property.type == 'enum'}
						{foreach from=$property.values item=propertyName key=propertyValue}
							{if $propValue == $propertyValue}
								{$value = {$value|cat:{$propertyName}}}
							{/if}
						{/foreach}
					{elseif $property.type == 'multiSelect'}
						{if is_array($propValue) && count($propValue) > 0}
							{foreach from=$property.values item=propertyName key=propertyValue}
								{* for csv output what delimiter should go here? *}
								{if array_key_exists($propertyValue, $propValue)}
									{$value = {$value|cat:{$propertyName}}}
								{/if}
							{/foreach}
						{else}
							{$value = 'No values selected'}
						{/if}
					{elseif $property.type == 'oneToMany'}
						{if is_array($propValue) && count($propValue) > 0}
							{$value = {$propValue|@count}}
						{else}
							{$value = 'Not set'}
						{/if}
					{elseif $property.type == 'checkbox'}
						{if ($propValue == 1)}
							{$value = {translate text='Yes' isAdminFacing=true}}
						{elseif ($propValue == 0)}
							{$value = {translate text='No' isAdminFacing=true}}
						{else}
							{$value = $propValue}
						{/if}
					{elseif $property.type == 'image'}
	{*									<img src="{$property.displayUrl}{$dataItem->id}" class="img-responsive" alt="{$propName}">*}
					{elseif $property.type == 'textarea'}
						{$value = {$propValue|truncate:255:'...'}}
					{else}
						{$value = {translate text="Unknown type to display %1%" 1=$property.type isAdminFacing=true}}
					{/if}
					{$value|replace:'"':'""'|regex_replace:'/^.*,.*$/':'"$0"'}
					{* CSV field delimiter *}
					,
				{/if}
			{/foreach}
			{* CSV record delimiter *}
			{"\n"}
		{/foreach}
	{/if}
{/strip}