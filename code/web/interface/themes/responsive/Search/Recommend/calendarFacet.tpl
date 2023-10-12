<form id='{$title}Filter' action='{$fullPath}' class="form-horizontal-narrow">
	<div class="facet-form">
		<div class="form-group">
			<label class="control-label" for="{$title}Start">{translate text="From" isPublicFacing=true}</label>
			<input type="date" name="{$title}Start" id="{$title}Start" placeholder="mm/dd/yyyy" class="form-control" size="10" min="{$smarty.now|date_format:"%Y-%m-%d"}" max="{$maxEventDate|date_format:"%Y-%m-%d"}" {if !empty($cluster.start)}value="{$cluster.start}"{/if}>
		</div>
		<div class="form-group">
			<label class="control-label" for="{$title}End">{translate text="To" isPublicFacing=true}</label>
			<input type="date" name="{$title}End" id="{$title}End" placeholder="mm/dd/yyyy" class="form-control" size="10" min="{$smarty.now|date_format:"%Y-%m-%d"}" max="{$maxEventDate|date_format:"%Y-%m-%d"}" {if !empty($cluster.end)}value="{$cluster.end}"{/if}>
		</div>

		{* To make sure that applying this filter does not remove existing filters we need to copy the get variables as hidden variables *}
		{foreach from=$smarty.get item=parmValue key=paramName}
			{if is_array($smarty.get.$paramName)}
				{foreach from=$smarty.get.$paramName item=parmValue2}
				{* Do not include the filter that this form is for. *}
					{if strpos($parmValue2, $title) === FALSE}
						<input type="hidden" name="{$paramName}[]" value="{$parmValue2|escape}" />
					{/if}
				{/foreach}
			{else}
				<input type="hidden" name="{$paramName}" value="{$parmValue|escape}" />
			{/if}
		{/foreach}

		<input type="submit" value="{translate text="Go" inAttribute="true" isPublicFacing=true}" class="goButton btn btn-sm btn-primary" />
		<br/>&nbsp;
	</div>
</form>