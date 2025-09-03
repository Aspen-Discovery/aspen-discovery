{strip}
<form method="post" action="" name="popupForm" class="form-horizontal" id="groupedWorkDisplayInfoForm">
	<div>
		<div class="form-group">
			<div class="col-tn-3">
				<label for="title">{translate text="Title" isAdminFacing=true}</label>
			</div>
			<div class="col-tn-9">
				<input type="text" name="title" id="title" class="form-control" value="{$title}" maxlength="276"/>
			</div>
		</div>
		<div class="form-group">
			<div class="col-tn-3">
				<label for="author">{translate text="Author" isAdminFacing=true} </label>
			</div>
			<div class="col-tn-9">
				<input type="text" name="author" id="author" class="form-control" value="{$author}" maxlength="50"/>
			</div>
		</div>
		{if !array_key_exists('Series', $enabledModules)}
			<div class="form-group">
				<div class="col-tn-3">
					<label for="seriesName">{translate text="Series Name" isAdminFacing=true}</label>
				</div>
				<div class="col-tn-9">
					<input type="text" name="seriesName" id="seriesName" class="form-control" value="{$seriesName}" maxlength="255"/>
				</div>
			</div>
			<div class="form-group">
				<div class="col-tn-3">
					<label for="seriesDisplayOrder">{translate text="Series Display Order" isAdminFacing=true}</label>
				</div>
				<div class="col-tn-9">
					<input type="number" name="seriesDisplayOrder" id="seriesDisplayOrder" class="form-control" value="{$seriesDisplayOrder|format_float_with_min_decimals}" maxlength="255" step="0.01"/>
				</div>
			</div>
		{/if}
	</div>
	<input type="hidden" name="id" id="id" value="{$id}"/>
</form>
{/strip}
