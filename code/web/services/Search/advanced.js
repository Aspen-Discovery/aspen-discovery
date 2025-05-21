var nextGroupNumber = 0;
var groupSearches = new Array();

function addSearch(group, term, field)
{
	if (term  == undefined) {term  = '';}
	if (field == undefined) {field = '';}

	var newSearch = "";

	newSearch += "<div class='row form-inline'>";
	// Label
	newSearch += "<div class='searchLabel col-sm-2'>"
			+ ((groupSearches[group] == 0) ? searchLabel+' ' : '&nbsp;')
			+ '</div>';

	// Terms
	newSearch += "<div class='input-group col-sm-10'><input type='text' class='form-control' name='lookfor" + group + "[" + groupSearches[group] + "]'  value='" + jsEntityEncode(term) + "' aria-label='Search Term for Group " + group + "' >";

	// Field
	newSearch += "<span class='input-group-addon'>" + searchFieldLabel + " </span>";
	newSearch += "<select class='form-control' name='type" + group + "[" + groupSearches[group] + "]' aria-label='Search Type for group " + group + "' >";
	for (key in searchFields) {
			newSearch += "<option value='" + key + "'";
			if (key == field) {
					newSearch += " selected='selected'";
			}
			newSearch += ">" + searchFields[key] + "</option>";
	}
	newSearch += "</select>";

	newSearch += "</div>";

	// Add to Search Group
	$('#group' + group + 'SearchHolder').append(newSearch) // add new search
			.find('[name^=lookfor]') // looks for group search inputs
			.filter(":last")  // take only the last one (the one we just added)
			.keypress(function(e){ // add event to prevent button clicking for pressing enter
		if ( e.which === 13 ) {
			e.preventDefault()
		}
	});


	// Actual value doesn't matter once it's not zero.
	groupSearches[group]++;
}

function addGroup(firstTerm, firstField, join)
{
	if (firstTerm  === undefined) {firstTerm  = '';}
	if (firstField === undefined) {firstField = '';}
	if (join       === undefined) {join       = '';}

	var newGroup = "";
	newGroup += "<div id='group" + nextGroupNumber + "' class='group group" + (nextGroupNumber % 2) + " well well-sm'>";

	newGroup += "<div class='groupSearchDetails clearfix'>";
	// Delete link
	newGroup += "<a href='javascript:void(0);' class='delete btn btn-sm btn-warning' id='delete_link_" + nextGroupNumber + "' onclick='deleteGroupJS(this);'>" + deleteSearchGroupString + "</a>";

	// Boolean operator drop-down
	newGroup += "<div class='join'><label for='bool" + nextGroupNumber + "[]'>" + searchMatch + "</label>";
	newGroup += "<select name='bool" + nextGroupNumber + "[]' id='bool" + nextGroupNumber + "[]' aria-label='Match Type For Group " + nextGroupNumber + "'>";
	for (key in searchJoins) {
		newGroup += "<option value='" + key + "'";
		if (key === join) {
			newGroup += " selected='selected'";
		}
		newGroup += ">" + searchJoins[key] + "</option>";
	}
	newGroup += "</select>";
	newGroup += "</div>";

	newGroup += "</div>";

	// Holder for all the search fields
	newGroup += "<div id='group" + nextGroupNumber + "SearchHolder' class='groupSearchHolder'></div>";
	// Add search term link
	newGroup += "<div class='addSearch row'><div class='col-sm-4 col-sm-offset-2'><a href='javascript:void(0);' class='add btn btn-sm btn-default' id='add_search_link_" + nextGroupNumber + "' onclick='addSearchJS(this);'>" + addSearchString + "</a></div></div>";

	newGroup += "</div>";

	// Set to 0 so adding searches knows
	// which one is first.
	groupSearches[nextGroupNumber] = 0;

	// Add the new group into the page
	$('#searchHolder').append($(newGroup).hide());

	$('#group'+nextGroupNumber).fadeIn();

	// Add the first search field
	addSearch(nextGroupNumber, firstTerm, firstField);
	// Keep the page in order
	reSortGroups();

	// Pass back the number of this group
	return nextGroupNumber - 1;
}


// Fired by onclick event
function deleteGroupJS(elem)
{
	$(elem).parents('.group').fadeOut(function(){
		$(this).remove();
		reSortGroups();
	});
	return false;
}

// Fired by onclick event
function addSearchJS(group)
{
	var groupNum = group.id.replace("add_search_link_", "");
	addSearch(groupNum);
	return false;
}

function reSortGroups()
{
	// Loop through all groups
	var groups = 0;
	$('#searchHolder').children().each(function(){
		if (this.id != undefined) {
			if (this.id != 'group'+groups) {
				reNumGroup(this, groups);
			}
			groups++;
		}
	});
	nextGroupNumber = groups;

	// Hide Group-related controls if there is only one group:
	if (nextGroupNumber > 1){
		$('#groupJoin').fadeIn();
	}else{
		$('#groupJoin').fadeOut();
	}

	// Hide Delete when only one search group is present
	$('#delete_link_0').css('display', (nextGroupNumber === 1 ? 'none' : 'inline') );

	// If the last group was removed, add an empty group
	if (nextGroupNumber === 0) {
		addGroup();
	}
}

function reNumGroup(oldGroup, newNum)
{
	// Keep the old details for use
	var oldId  = oldGroup.id;
	var oldNum = oldId.substring(5, oldId.length);

	// Make sure the function was called correctly
	if (oldNum !== newNum) {
		// Set the new details
		$(oldGroup).attr('id', "group" + newNum)
				.removeClass('group0 group1').addClass('group'+ newNum % 2);

		// Update the delete link with the new ID
		$('.delete', oldGroup).attr('id', "delete_link_" + newNum);

		// Update the bool[] parameter number
		$('[name="bool' + oldNum + '[]"]', oldGroup).attr('name', 'bool' + newNum + '[]');

		// Update the add term link with the new ID
		$('.add', oldGroup).attr('id', 'add_search_link_' + newNum);

		// Update search holder ID
		var sHolder = $('.groupSearchHolder', oldGroup).attr('id', 'group' + newNum + 'SearchHolder');

		// Update all lookfor[] and type[] parameters
		$('.terms', sHolder).attr('name', 'lookfor' + newNum + '[]');
		$('.field', sHolder).attr('name', 'type' + newNum + '[]');
	}
}


function jsEntityEncode(str)
{
	var new_str = str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
	return new_str;
}
function resetSearch(){
	$('input[type="text"]').val('');
	$("option:selected").removeAttr("selected").parent().change();
	$('.delete').not('#delete_link_0').each(function () {
		deleteGroupJS(this);
	})
}