 {literal}
<script type="text/javascript">
	$(document).ready(function(){
		$('#widgetsListLinks tbody').sortable({
			update: function(event, ui){
				$.each($(this).sortable('toArray'), function(index, value)
				{
					$('#' + value + ' input.weightValue').val(index);
				});
			}
		});
	});
 
	
	$(document).ready(function(){ 
		$("#objectEditor").validate();
	});

	function deleteLink(linkId) {
		if(confirm('Are you sure you want to delete this link?')) {
			$('#toDelete_' + linkId).val(1);
			$('#linkId_' + linkId).fadeOut(500);
		}
	}

	var numNewLink = 0;
	
	function addNewLink() {
		var htmlLink =	"<tr id='newLinkId_" + numNewLink + "'>";
				htmlLink += "	<td>";
				htmlLink += "		<span class='ui-icon ui-icon-arrowthick-2-n-s' style='pointer:cursor;'></span>";
				htmlLink += "		<input class='weightValue' type='hidden' name='weightNewLink[" + numNewLink + "]' value='900'/>";
				htmlLink += "	</td>";
			  htmlLink += "	<td>";
			  htmlLink += "		<input type='hidden' name='newLink[" + numNewLink + "]' value='" + numNewLink + "'/>";
			  htmlLink += "		<input class='required' type='text' size='40' name='nameNewLink[" + numNewLink + "]' value=''/>";
			  htmlLink += "	</td>";
			  htmlLink += "	<td>";
			  htmlLink += "		<input class='required' type='text' size='40' name='linkNewLink[" + numNewLink + "]' value=''/>";
			  htmlLink += "	</td>";
			  htmlLink += "	<td>";
				htmlLink += "		&nbsp;";
			  htmlLink += "	</td>";
			  htmlLink += "</tr>";
			  $('#widgetsListLinks').append(htmlLink);
		numNewLink++;
	}
</script>
{/literal}
{css filename="listWidget.css"}

<div id="main-content">
	<h1>Edit Links</h1>
	<div id="header">
		<h2> <a href='/Admin/ListWidgets?objectAction=edit&id={$widgetId}'>{$widgetName}</a> | {$widgetListName}</h2>
	</div>

	<form id='objectEditor' method="post" enctype="multipart/form-data">
		<table id='widgetsListLinks'>
			<thead>
				<tr>
					<th>Weight</th>
					<th>Name</th>
					<th>Link</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			<tbody>
				{foreach from=$availableLinks item=link}
					<tr id='linkId_{$link->id}'>
						<td>
							<span class="glyphicon glyphicon-resize-vertical" style='pointer:cursor;'></span>
							<input class='weightValue' type="hidden" id="Weight[{$link->id}]" name="weight[{$link->id}]" value="{$link->weight}"/>
						</td>
						<td>
							<input type='hidden' id='toDelete_{$link->id}' name='toDelete_{$link->id}' value='0'/>
							<input type='hidden' name='id[{$link->id}]' value='{$link->id}'/>
							<input type='hidden' name='listWidgetListsId[{$link->id}]' value='{$link->listWidgetListsId}'/>
							<input class='required' type='text' size='40' name='name[{$link->id}]' value='{$link->name}'/>
						</td>
						<td>
							<input class='required'	type='text' size='40' name='link[{$link->id}]' value='{$link->link}'/>
						</td>
						<td>
							<a href="#" onclick='deleteLink({$link->id});'>
								<img src="/images/silk/delete.png" alt="Delete Link" title="Delete Link"/>
							</a>
						</td>
					</tr>
				{/foreach}
			</tbody>
		</table>
		<div class="Actions">
			<a href="#" onclick="addNewLink();return false;"	class="button">Add New</a>
		</div>
		<br/>
		<input type='hidden' name='objectAction' value='save' />
		<input type="submit" name="submit" value="Save Changes"/>
	</form>
</div>