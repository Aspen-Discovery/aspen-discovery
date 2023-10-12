{if !empty($userCanChangeFieldLocks)}
	<a id="fieldLock{$property.property}" onclick="return AspenDiscovery.Admin.toggleFieldLock('{$module}', '{$toolName}', '{$property.property}');" role="button" tabindex="0"><i class="text-info fas {if !empty($property.locked)}fa-lock{else}fa-unlock-alt{/if}" title="{translate text="Click to toggle field locking" isAdminFacing=true inAttribute=true}"></i></a>
{elseif !empty($property.locked)}
	<i class="fas fa-lock" title="{translate text="Locked by administration" isAdminFacing=true}"></i>
{/if}