function SendEmail(id, to, from, message, strings) {
	var url = path + "/Record/" + encodeURIComponent(id) + "/AJAX";
	var params = "method=SendEmail&" + "from=" + encodeURIComponent(from) + "&" + "to=" + encodeURIComponent(to) + "&" + "message=" + encodeURIComponent(message);
	sendAJAXEmail(url, params, strings);
}

function SendSMS(id, to, provider, strings) {
	var url = path + "/Record/" + encodeURIComponent(id) + "/AJAX";
	var params = "method=SendSMS&" + "to=" + encodeURIComponent(to) + "&" + "provider=" + encodeURIComponent(provider);
	sendAJAXSMS(url, params, strings);
}

function SaveComment(id, shortId, strings) {
	if (loggedIn){
		if (shortId == null || shortId == ''){
			shortId = id;
		}
		$('#userreview' + shortId).slideUp();
		var comment = $('#comment' + shortId).val();

		var url = path + "/Record/" + encodeURIComponent(id) + "/AJAX";
		var params = "method=SaveComment&comment=" + encodeURIComponent(comment);
		$.ajax({
			url: url + '?' + params,
			dataType: 'json',
			success : function(data) {
				var result = false;
				if (data) {
					result = data.result;
				}
				if (result && result.length > 0) {
					if (result == "Done") {
						$('#comment' + shortId).val('');
						if ($('#commentList').length > 0) {
							LoadComments(id);
						} else {
							alert('Thank you for your review.');
						}
					}else{
						alert("Error: Your review was not saved successfully");
					}
				} else {
					alert("Error: Your review was not saved successfully");
				}
			},
			error : function() {
				if (strings.save_error.length == 0){
					alert("Unable to save your comment.");
				}else{
					alert(strings.save_error);
				}
			}
		});
	}else{
		ajaxLogin(function(){
			SaveComment(id, shortId, strings);
		});
	}
}

function deleteComment(id, commentId, strings) {
	var url = path + "/Record/" + encodeURIComponent(id) + "/AJAX?method=DeleteComment&commentId=" + encodeURIComponent(commentId);
	$.ajax( {
		url : url,
		success : function() {
			alert("Your review was deleted.");
			LoadComments(id, strings);
		},
		error: function(){
			alert("There was an error deleting the comment");
		}
	});
}

function LoadComments(id) {
	var url = path + "/Record/" + encodeURIComponent(id) + "/AJAX";
	var params = "method=GetComments";
	var output = '';

	$.getJSON(url + "?" + params, function(data) {
		var result = data.userComments;
		if (result && result.length > 0) {
			$("#commentList").html(result);
		}else{
			$("#commentList").html("");
		}

		var staffComments = data.staffComments;
		if (staffComments && staffComments.length > 0) {
			$("#staffCommentList").html(staffComments);
		} else {
			$("#staffCommentList").html("");
		}
	});
}

/**
 * @return {boolean}
 */
function GetPreferredBranches() {
	var username = document.forms['placeHoldForm'].elements['username'].value;
	var barcode = document.forms['placeHoldForm'].elements['password'].value;
	var holdCount = document.forms['placeHoldForm'].elements['holdCount'].value;
	if (username.length == 0 || barcode.length == 0) {
		return false;
	}

	var url = path + "/MyAccount/AJAX";
	var params = "method=GetPreferredBranches&username="
	    + encodeURIComponent(username) + "&barcode="
	    + encodeURIComponent(barcode) + "&holdCount="
	    + encodeURIComponent(holdCount);
	$('#holdError').hide();
	$.getJSON(url + "?" + params, function(data) {
			if (data.loginFailed == false) {
				var locations = data.PickupLocations;
				$('#loginButton').hide();
				$('#holdOptions').show();
				// Remove the old options
				var campus = document.placeHoldForm.campus;
				campus.options.length = 0;
				for (i = 0; i < locations.length; i++) {
					campus.options[campus.options.length] = new Option(locations[i].displayName,
							locations[i].id,
							locations[i].selected);
				}
				if (data.showOverHoldLimit == true){
					$(".maxHolds").html(data.maxHolds);
					$(".currentHolds").html(data.currentHolds);
					$("#overHoldCountWarning").show();
				}
				// Enable the place hold button
				$("#requestTitleButton").removeAttr('disabled');
			} else {
				$('#loginButton').show();
				// document.getElementById('holdOptions').style.display = 'none';
				$('#holdError').html('Invalid Login, please try again.').show();
			}

	  }
	);

	return false;
}

function getGoDeeperData(dataType, recordType, id, isbn, upc) {
	var url = path + "/Record/" + encodeURIComponent(id) + "/AJAX";

	var params = "method=GetGoDeeperData&dataType=" + encodeURIComponent(dataType) + "&isbn=" + encodeURIComponent(isbn) + "&upc=" + encodeURIComponent(upc);
	var fullUrl = url + "?" + params;
	$.ajax( {
	  url : fullUrl,
	  success : function(data) {
		  $('#goDeeperOutput').html(data);
	  }
	});
}

function getProspectorInfo(id) {
	var url = path + "/Record/" + encodeURIComponent(id) + "/AJAX";
	var params = "method=getProspectorInfo";
	var fullUrl = url + "?" + params;
	$.ajax( {
		url : fullUrl,
		success : function(data) {
			var inProspectorData = $(data).find("InProspector").text();
			if (inProspectorData) {
				if (inProspectorData.length > 0) {
					$("#inProspectorPlaceholder").html(inProspectorData);
				}
				var prospectorCopies = $(data).find("OwningLibrariesFormatted").text();
				if (prospectorCopies && prospectorCopies.length > 0) {
					$("#prospectorHoldingsPlaceholder").html(prospectorCopies);
				}
				$("#inProspectorSidegroup").show();
			}else{
				if ($("#prospectortab_label")){
					$("#prospectortab_label").hide();
					if ($("#holdingstab_label").is(":visible")){
						$("#moredetails-tabs").tabs("option", "active", 0);
					}else{
						$("#moredetails-tabs").tabs("option", "active", 2);
					}
				}
			}
		}
	});
}

function GetReviewInfo(id, isbn) {
	var url = path + "/Record/" + encodeURIComponent(id) + "/AJAX";
	var params = "method=GetReviewInfo&isbn=" + encodeURIComponent(isbn);
	var fullUrl = url + "?" + params;
	$.ajax( {
		url : fullUrl,
		success : function(data) {
			var reviewsData = $(data).find("Reviews").text();
			if (reviewsData) {
				if (reviewsData.length > 0) {
					$("#reviewPlaceholder").html(reviewsData);
				}else{
					//$("#reviewPlaceholder").html("There are no reviews for this title.");
				}
			}else{
				//$("#reviewPlaceholder").html("There are no reviews for this title.");
			}
		}
	});
}

function GetDescription(id) {
	var url = path + "/Record/" + encodeURIComponent(id) + "/AJAX/";
	var params = "method=getDescription";
	var fullUrl = url + "?" + params;
	var placeholder = "#descriptionPlaceholder" + id.replace(".", "");
	$.ajax( {
		url : fullUrl,
		success : function(data) {
			var descriptionData = $(data).find("description").text();
			if (descriptionData) {
				if (descriptionData.length > 0) {
					// TODO: this will need to have the id attached to it so that the id
					// is unique.
					$(placeholder).html(descriptionData);
				}
			}
		}
	});
}

libraryThingWidgetsLoaded = function(){
	var ltfl_tagbrowse_content = $('#ltfl_tagbrowse').html();
	if (!ltfl_tagbrowse_content.match(/loading_small\.gif/)){
		$("#ltfl_tagbrowse_button").show();
	}
	var ltfl_series_content = $('#ltfl_series').html();
	if (!ltfl_series_content.match(/loading_small\.gif/)){
		$("#ltfl_series_button").show();
	}
	var ltfl_awards_content = $('#ltfl_awards').html();
	if (!ltfl_awards_content.match(/loading_small\.gif/)){
		$("#ltfl_awards_button").show();
	}
	var ltfl_similars_content = $('#ltfl_similars').html();
	if (!ltfl_similars_content.match(/loading_small\.gif/)){
		$("#ltfl_similars_button").show();
	}
	var ltfl_related_content = $('#ltfl_related').html();
	if (!ltfl_related_content.match(/loading_small\.gif/)){
		$("#ltfl_related_button").show();
	}
};
