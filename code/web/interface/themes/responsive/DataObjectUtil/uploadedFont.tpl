<div class="row">
	<div class="col-sm-7">
		<div class="input-group">
			<label class="input-group-btn">
				<span class="btn btn-primary">
					{translate text="Select a font" isAdminFacing=true}&hellip; <input type="file" style="display: none;" name="{$propName}" id="{$propName}">
				</span>
			</label>
			<input type="text" class="form-control" id="importFile-label-{$propName}" readonly value="{$propValue}">
		</div>
		{if !empty($propValue)}
			<div class="checkbox" style="margin-top: 0;">
				<label for="remove{$propName}"><small class="text-danger"><i class="fas fa-trash"></i> {translate text="Remove" isAdminFacing=true}</small>
				<input type='checkbox' name='remove{$propName}' id='remove{$propName}'>
				</label>
			</div>
		{/if}
		<script type="application/javascript">
			{literal}
			$(document).on('change', '#{/literal}{$propName}{literal}:file', function() {
				var input = $(this);
				var label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
				$("#importFile-label-{/literal}{$propName}{literal}").val(label);
			});
			{/literal}
		</script>
	</div>
	<div class="col-sm-5">
		{if !empty($propValue)}
			<style>
				@font-face {ldelim} font-family: 'uploaded-{$propName}'; src: url('/fonts/{$propValue|escape:'url'}'); {rdelim}
			</style>
		{/if}
		<div id="{$propName}-sample-text" style="font-family: 'uploaded-{$propName}',arial; font-size: {if !empty($property.previewFontSize)}{$property.previewFontSize}{else}var(--aspen-base-font-size){/if}">
			English, Español, 中文(简体), עברית
		</div>
	</div>
</div>
