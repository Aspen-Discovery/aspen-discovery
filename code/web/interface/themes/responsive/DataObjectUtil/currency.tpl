<div class="controls">
	{assign var=propDisplayFormat value=$property.displayFormat}
	<input type='text' name='{$propName}' id='{$propName}' value='{$propValue|string_format:$propDisplayFormat}' class='form-control' {if !empty($property.readOnly)}readonly{/if} {if !empty($property.required)}required{/if}></input>
	{if !empty($property.note)}<span id="{$propName}HelpBlock" class="help-block" style="margin-top:0" ><small><i class="fas fa-info-circle"></i> {$property.note}</small></span>{/if}
</div>
