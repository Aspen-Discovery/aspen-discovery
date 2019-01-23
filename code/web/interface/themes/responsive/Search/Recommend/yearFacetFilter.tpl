<form id='{$title}Filter' action='{$fullPath}' class="form-inline">
	<div class="facet-form">
		<div class="form-group">
			<label for="{$title}yearfrom" class='yearboxlabel sr-only control-label'>From</label>
			<input type="text" size="4" maxlength="4" class="yearbox form-control" placeholder="from" name="{$title}yearfrom" id="{$title}yearfrom" value="" />
		</div>
		<div class="form-group">
			<label for="{$title}yearto" class='yearboxlabel sr-only control-label'>To</label>
			<input type="text" size="4" maxlength="4" class="yearbox form-control" placeholder="to" name="{$title}yearto" id="{$title}yearto" value="" />
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
		<input type="submit" value="Go" class="goButton btn btn-sm btn-primary" />

		{if $title == 'publishDate'}
			<div id='yearDefaultLinks'>
				{assign var=thisyear value=$smarty.now|date_format:"%Y"}
				Published in the last:<br/>
				<a onclick="$('#{$title}yearfrom').val('{$thisyear-1}');$('#{$title}yearto').val('');" href='javascript:void(0);'>year</a>
				&bullet; <a onclick="$('#{$title}yearfrom').val('{$thisyear-5}');$('#{$title}yearto').val('');" href='javascript:void(0);'>5&nbsp;years</a>
				&bullet; <a onclick="$('#{$title}yearfrom').val('{$thisyear-10}');$('#{$title}yearto').val('');" href='javascript:void(0);'>10&nbsp;years</a>
			</div>
		{/if}
	</div>
</form>
