{strip}
<div>
	<div id="createWidgetComments">
		<p class="alert alert-info">
			{if count($existingWidgets) > 0}
				You may either add this {$source} to an existing widget as a new tab, <br> or you may create a new widget to display this {$source} in.
			{else}
				Please enter a name for the widget to be created.
			{/if}
		</p>
	</div>
	<form method="post" name="bulkAddToList" id="bulkAddToList" action="{$path}/Admin/CreateListWidget" class="form-horizontal">
			<input type="hidden" name="source" value="{$source}">
			<input type="hidden" name="id" value="{$id}">
			{if count($existingWidgets) > 0}
				<div class="form-group">
					<label for="widget" class="col-sm-4">Select a widget:</label>
					<div class="col-sm-8">
						<select id="widgetId" name="widgetId" class="form-control">
							<option value="-1">Create a new widget</option>
							{foreach from=$existingWidgets item=widgetName key=widgetId}
								<option value="{$widgetId}">{$widgetName}</option>
							{/foreach}
						</select>
					</div>
				</div>
			{/if}
			<div class="form-group">
				<label for="widgetName" class="col-sm-4">New Widget Name / New Tab Name:</label>
				<div class="col-sm-8">
					<input type="text" id="widgetName" name="widgetName" value="" class="form-control required">
				</div>
			</div>
	</form>
	<script type="text/javascript">
		$(function(){ldelim}
			$("#bulkAddToList").validate();
		{rdelim});
	</script>
</div>
{/strip}