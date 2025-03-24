{strip}
{assign var=hours value= (int) ($propValue / 60)}
{assign var=minutes value=$propValue % 60}
<fieldset class="form-inline">
	<div class="form-group col-sm-6">
		<label for='{$propName}_hours'>
			{translate text="Hours" isPublicFacing=true}
		</label>
		<input type="number" name='{$propName}_hours' id='{$propName}_hours' min='0' class='form-control duration-input' {if !empty($property.required) && (empty($objectAction) || $objectAction != 'edit')}required{/if} {if !empty($property.readOnly)}readonly disabled{/if} {if !empty($property.autocomplete)}autocomplete="{$property.autocomplete}"{/if} value="{$hours}">
	</div>
	<div class="form-group col-sm-6">
		<label for='{$propName}_minutes'>
			{translate text="Minutes" isPublicFacing=true}
		</label>
		<input type="number" name='{$propName}_minutes' id='{$propName}_minutes' min='0' class='form-control duration-input' {if !empty($property.required) && (empty($objectAction) || $objectAction != 'edit')}required{/if} {if !empty($property.readOnly)}readonly disabled{/if} {if !empty($property.autocomplete)}autocomplete="{$property.autocomplete}"{/if} value="{$minutes}">
		<input type="hidden" name="{$propName}" id='{$propName}' min='0' value="{$propValue}" class='form-control' {if !empty($property.required) && (empty($objectAction) || $objectAction != 'edit')}required{/if} {if !empty($property.readOnly)}readonly disabled{/if} {if !empty($property.autocomplete)}autocomplete="{$property.autocomplete}"{/if} {if !empty($property.onchange)} onchange="{$property.onchange}"{/if}>
	</div>
</fieldset>
	<script>
		$(".duration-input").change(function() {
			var hours = Number($("#{$propName}_hours").val()) * 60;
			var minutes = Number($("#{$propName}_minutes").val());
			$("#{$propName}").val(hours + minutes);
			$("#{$propName}").change();
		});
	</script>
{/strip}
