{strip}
<div>
	<form method="post" name="createRole" id="createRole" class="form-horizontal">
		<div class="form-group">
			<label for="roleName" class="col-sm-4">{translate text="Name" isAdminFacing=true} <span class="label label-danger" style="margin-right: .5em">{translate text="required" isAdminFacing=true}</span></label>
			<div class="col-sm-8">
				<input type="text" id="roleName" name="roleName" value="" class="form-control required" maxlength="50">
			</div>
		</div>
		<div class="form-group">
			<label for="description" class="col-sm-4">{translate text="Description" isAdminFacing=true}</label>
			<div class="col-sm-8">
				<input type="text" id="description" name="description" value="" class="form-control required" maxlength="100">
			</div>
		</div>
	<div class="form-group">
        <label for="roleCopySelector" class="col-sm-4">{translate text="Copy permissions from" isAdminFacing=true}</label>
        <div class="col-sm-8">
	        <select id="roleCopySelector" name="roleCopySelector" class="form-control">
	            <option value="-1">{translate text="None" isAdminFacing=true}</option>
	            {foreach from=$permissionRoles item=role}
	                <option value="{$role.roleId}">{translate text=$role.name isAdminFacing=true}</option>
	            {/foreach}
	        </select>
        </div>
    </div>
	</form>
	<script type="text/javascript">
		$(function(){ldelim}
			$("#createRole").validate();
		{rdelim});
	</script>
</div>
{/strip}