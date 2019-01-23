{assign var=propNameMonth value=$property.propNameMonth}
{assign var=propNameDay value=$property.propNameDay}
{assign var=propNameYear value=$property.propNameYear}
<div class="controls">
	<select name='{$propNameMonth}' id='{$propNameMonth}' class="form-control">
		<option value=""></option>
		<option value="1" {if $object->$propNameMonth == '1'}selected='selected'{/if}>January</option>
		<option value="2" {if $object->$propNameMonth == '2'}selected='selected'{/if}>February</option>
		<option value="3" {if $object->$propNameMonth == '3'}selected='selected'{/if}>March</option>
		<option value="4" {if $object->$propNameMonth == '4'}selected='selected'{/if}>April</option>
		<option value="5" {if $object->$propNameMonth == '5'}selected='selected'{/if}>May</option>
		<option value="6" {if $object->$propNameMonth == '6'}selected='selected'{/if}>June</option>
		<option value="7" {if $object->$propNameMonth == '7'}selected='selected'{/if}>July</option>
		<option value="8" {if $object->$propNameMonth == '8'}selected='selected'{/if}>August</option>
		<option value="9" {if $object->$propNameMonth == '9'}selected='selected'{/if}>September</option>
		<option value="10" {if $object->$propNameMonth == '10'}selected='selected'{/if}>October</option>
		<option value="11" {if $object->$propNameMonth == '11'}selected='selected'{/if}>November</option>
		<option value="12" {if $object->$propNameMonth == '12'}selected='selected'{/if}>December</option>
		</select>
	Day: <input type='text' name='{$propNameDay}' id='{$propNameDay}' value='{$object->$propNameDay}' maxLength='2' size='2' class="input-mini"/>
	Year: <input type='text' name='{$propNameYear}' id='{$propNameYear}' value='{$object->$propNameYear}' maxLength='4' size='4' class="input-mini"/>
</div>