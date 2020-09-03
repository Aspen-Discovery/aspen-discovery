{strip}
<div>
	<form method="post" name="createRole" id="createRole" class="form-horizontal">
		<div class="form-group">
			<label for="roleName" class="col-sm-4">{translate text="Name"} ({translate text="required"})</label>
			<div class="col-sm-8">
				<input type="text" id="roleName" name="roleName" value="" class="form-control required" maxlength="50">
			</div>
		</div>
		<div class="form-group">
			<label for="description" class="col-sm-4">{translate text="Description"}</label>
			<div class="col-sm-8">
				<input type="text" id="description" name="description" value="" class="form-control required" maxlength="100">
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