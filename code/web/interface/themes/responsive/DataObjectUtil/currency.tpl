<div class="controls">
	{assign var=propDisplayFormat value=$property.displayFormat}
	<input type='text' name='{$propName}' id='{$propName}' value='{$propValue|string_format:$propDisplayFormat}' class='form-control' ></input>
</div>