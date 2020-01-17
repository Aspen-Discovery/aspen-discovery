{css filename="listWidget.css"}
{strip}
	<div id="main-content">
		<h1>Edit Featured Title Display</h1>
		<div class="btn-group">
			<a class="btn btn-sm btn-default" href="/Admin/CollectionSpotlights">All Featured Title Displays</a>
			<a class="btn btn-sm btn-default" href="/Admin/CollectionSpotlights?objectAction=view&id={$object->id}">View</a>
			<a class="btn btn-sm btn-default" href="/API/SearchAPI?method=getCollectionSpotlight&id={$object->id}">Preview</a>
			{if $canDelete}
				<a class="btn btn-sm btn-danger" href="/Admin/CollectionSpotlights?objectAction=delete&id={$object->id}" onclick="return confirm('Are you sure you want to delete {$object->name}?');">Delete</a>
			{/if}
		</div>

		{$editForm}
	</div>
{/strip}
{if $edit}
<script type="text/javascript">{literal}
	$(function(){
		let selectedWidgetBody = $('#selectedWidgetLists tbody');
		selectedWidgetBody.sortable({
			update: function(event, ui){
				let listOrder = $(this).sortable('toArray').toString();
				alert("ListOrder = " + listOrder);
			}
		});
		selectedWidgetBody.disableSelection();
	});{/literal}
</script>
{/if}