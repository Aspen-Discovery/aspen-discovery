{strip}
	<div id="main-content" class="col-md-12">
		<div class="btn-group">
			<a class="btn btn-sm btn-default" href="/SideLoads/SideLoads?objectAction=edit&amp;id={$id}">{translate text="Edit Profile" isAdminFacing=true}</a>
			{if !empty($additionalObjectActions)}
				{foreach from=$additionalObjectActions item=action}
					{if $smarty.server.REQUEST_URI != $action.url}
						<a class="btn btn-default btn-sm" href='{$action.url}'>{$action.text}</a>
					{/if}
				{/foreach}
			{/if}
			<a class="btn btn-sm btn-default" href='/SideLoads/SideLoads?objectAction=list'>{translate text="Return to List" isAdminFacing=true}</a>
		</div>

		<h1>{translate text="Upload MARC Record" isAdminFacing=true}</h1>

		{if !empty($error)}
			<div class="alert alert-warning">
				{$error}
			</div>
		{elseif !empty($message)}
			<div class="alert alert-info">
				{$message}
			</div>
		{/if}
		<form enctype="multipart/form-data" name="uploadMarc" method="post">
			<input type="hidden" name="id" value="{$id}"/>
			<div class="form-group">
				<div class="input-group">
					<label class="input-group-btn">
					<span class="btn btn-primary">
						{translate text="Select MARC File" isAdminFacing=true}&hellip; <input type="file" style="display: none;" name="marcFile" id="marcFile">
					</span>
					</label>
					<input type="text" class="form-control" id="selected-marc-label" readonly>
				</div>
				<small id="marcFileHelp" class="form-text text-muted">{translate text="MRC, and MARC Files can be uploaded, must be %1%MB or less." 1=$max_file_size isAdminFacing=true}</small>
			</div>
			<div class="form-group">
				<label for="replaceExisting"><input type="checkbox" name="replaceExisting" id="replaceExisting"> {translate text="Replace Existing Files?" isAdminFacing=true}</label>
			</div>
			<div class="form-group">
				<button type="submit" class="btn btn-primary">{translate text="Upload File" isAdminFacing=true}</button>
			</div>
		</form>
	</div>
	<script type="application/javascript">
		{literal}
		$(document).on('change', ':file', function() {
			var input = $(this);
			var label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
			$("#selected-marc-label").val(label);
		});
		{/literal}
	</script>
{/strip}
