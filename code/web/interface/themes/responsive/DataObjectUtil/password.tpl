<input type='password' name='{$propName}' id='{$propName}' class="form-control"/>
{if !isset($property.showConfirm) || $property.showConfirm == true}
Repeat the Password
<input type='password' name='{$propName}Repeat'  class="form-control"/>
{/if}