{if count($allInstances) > 1}
	<form name="selectInterface" id="selectInterface" class="form-inline row">
		<div class="form-group col-tn-12">
			<label for="instance" class="control-label">{translate text="Instance to show stats for" isAdminFacing=true}</label>&nbsp;
			<select id="instance" name="instance" class="form-control input-sm" onchange="$('#selectInterface').submit()">
				{foreach from=$allInstances key=instanceId item=curInstance}
					<option value="{$instanceId}" {if $instanceId == $selectedInstance}selected{/if}>{$curInstance}</option>
				{/foreach}
			</select>
		</div>
	</form>
{/if}