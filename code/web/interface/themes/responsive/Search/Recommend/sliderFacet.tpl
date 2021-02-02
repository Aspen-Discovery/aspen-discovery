<form id="{$title}Filter" action="{$fullPath}" class="form-inline">
	<div class="facet-form">
		{if $title == 'lexile_score'}
			<div id="lexile-range"></div>
		{/if}
		<div class="form-group">
			<label for="{$title}from" class="yearboxlabel sr-only control-label">{$cluster.label} from</label>
			<input type="text" size="4" maxlength="4" class="yearbox form-control" placeholder="from" name="{$title}from" id="{$title}from" value="">
		</div>
		<div class="form-group">
			<label for="{$title}to" class="yearboxlabel sr-only control-label">{$cluster.label} to</label>
			<input type="text" size="4" maxlength="4" class="yearbox form-control" placeholder="to" name="{$title}to" id="{$title}to" value="">
		</div>
		{* To make sure that applying this filter does not remove existing filters we need to copy the get variables as hidden variables *}
		{foreach from=$smarty.get item=parmValue key=paramName}
			{if is_array($smarty.get.$paramName)}
				{foreach from=$smarty.get.$paramName item=parmValue2}
				{* Do not include the filter that this form is for. *}
					{if strpos($parmValue2, $title) === FALSE}
						<input type="hidden" name="{$paramName}[]" value="{$parmValue2|escape}">
					{/if}
				{/foreach}
			{else}
				<input type="hidden" name="{$paramName}" value="{$parmValue|escape}">
			{/if}
		{/foreach}
		<input type="submit" value="Go" id="{$title}GoButton" class="goButton btn btn-sm btn-primary">
	</div>
</form>