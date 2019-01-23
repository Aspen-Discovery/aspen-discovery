{css filename="listWidget.css"}
{strip}
	<div id="main-content">
		<div class="alert alert-info">
			For more information on how to create List Widgets, please see the <a href="https://docs.google.com/document/d/1RySv7NbaYjaw_F9Gs7cP9pu3P894s_4J05o46m6z3bQ">online documentation</a>
		</div>
		<h1>Edit List Widget</h1>
		<div class="btn-group">
			<a class="btn btn-sm btn-default" href="{$path}/Admin/ListWidgets">All Widgets</a>
			<a class="btn btn-sm btn-default" href="{$path}/Admin/ListWidgets?objectAction=view&id={$object->id}">View</a>
			<a class="btn btn-sm btn-default" href="{$path}/API/SearchAPI?method=getListWidget&id={$object->id}">Preview</a>
			{if $canDelete}
				<a class="btn btn-sm btn-danger" href="{$path}/Admin/ListWidgets?objectAction=delete&id={$object->id}" onclick="return confirm('Are you sure you want to delete {$object->name}?');">Delete</a>
			{/if}
		</div>

		{$editForm}
	</div>
{/strip}
{if $edit}
<script type="text/javascript">{literal}
	$(function(){
		$('#selectedWidgetLists tbody').sortable({
			update: function(event, ui){
				var listOrder = $(this).sortable('toArray').toString();
				alert("ListOrder = " + listOrder);
			}
		});
		$('#selectedWidgetLists tbody').disableSelection();
	});{/literal}
</script>
{/if}