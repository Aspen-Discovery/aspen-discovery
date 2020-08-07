<input type='password' name='{$propName}' id='{$propName}' {if $propValue}value='{$propValue|escape}'{/if} {if $property.maxLength}maxlength='{$property.maxLength}'{/if} {if !empty($property.size)}size='{$property.size}'{/if} class='form-control {if $property.required}required{/if}' {if !empty($property.readOnly)}readonly{/if} />
{if !isset($property.showConfirm) || $property.showConfirm == true}
Repeat the Password
<input type='password' name='{$propName}Repeat' id='{$propName}Repeat' {if {$propValue_Repeat}value='{$propValue_Repeat|escape}'{/if} {if $property.maxLength}maxlength='{$property.maxLength}'{/if} {if !empty($property.size)}size='{$property.size}'{/if} class='form-control repeat-password' {if !empty($property.readOnly)}readonly{/if} />
{/if}