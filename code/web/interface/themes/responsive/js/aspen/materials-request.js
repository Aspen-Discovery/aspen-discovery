AspenDiscovery.MaterialsRequest = (function(){
	return {
		specialFields: undefined,
		authorLabels: undefined,

		cancelMaterialsRequest: function(id){
			if (confirm("Are you sure you want to cancel this request?")){
				var url = Globals.path + "/MaterialsRequest/AJAX?method=cancelRequest&id=" + id;
				$.getJSON(
						url,
						function(data){
							if (data.success){
								alert("Your request was cancelled successfully.");
								window.location.reload();
							}else{
								alert(data.error);
							}
						}
				);
				return false;
			}else{
				return false;
			}
		},

		showMaterialsRequestDetails: function(id, staffView){
			return AspenDiscovery.Account.ajaxLightbox(Globals.path + "/MaterialsRequest/AJAX?method=MaterialsRequestDetails&id=" +id + "&staffView=" +staffView, true);
		},

		updateMaterialsRequest: function(id){
			return AspenDiscovery.Account.ajaxLightbox(Globals.path + "/MaterialsRequest/AJAX?method=updateMaterialsRequest&id=" +id, true);
		},

		exportSelectedRequests: function(){
			var selectedRequests = this.getSelectedRequests(true);
			if (selectedRequests.length === 0){
				return false;
			}
			$("#updateRequests").submit();
			return true;
		},

		updateSelectedRequests: function(){
			var newStatus = $("#newStatus").val();
			var newAssignee = $("#newAssignee").val();
			if (newAssignee === "unselected" && newStatus === "unselected"){
				alert("Please select a new assignee and/or status to update.");
				return false;
			}
			var selectedRequests = this.getSelectedRequests(false);
			if (selectedRequests.length !== 0){
				$("#updateRequests").submit();
			}
			return false;
		},

		getSelectedRequests: function(promptToSelectAll){
			var selectedRequests = $("input.select:checked").map(function() {
				return $(this).attr('name') + "=" + $(this).val();
			}).get().join("&");
			if (selectedRequests.length === 0){
				if (promptToSelectAll){
					var ret = confirm('You have not selected any requests, process all requests?');
					if (ret === true){
						selectedRequests = $("input.select").map(function() {
							return $(this).attr('name') + "=on";
						}).get().join("&");
						$('.select').attr('checked', 'checked');
					}
				}else{
					alert("Please select one or more requests to update");
				}
			}
			return selectedRequests;
		},

		setIsbnAndOclcNumber: function(title, author, isbn, oclcNumber){
			$("#title").val(title);
			$("#author").val(author);
			$("#isbn").val(isbn);
			$("#oclcNumber").val(oclcNumber);
			$("#suggestedIdentifiers").slideUp();
		},

		setFieldVisibility: function(){
			$(".formatSpecificField").hide();
			//Get the selected format
			var selectedFormat = $("#format").find("option:selected").val(),
					hasSpecialFields = typeof AspenDiscovery.MaterialsRequest.specialFields != 'undefined';

			$(".specialFormatField").hide(); // hide all the special fields
			$(".specialFormatHideField").show(); // show all the special format hide fields
			this.updateHoldOptions();
			if (hasSpecialFields){
				if (AspenDiscovery.MaterialsRequest.specialFields[selectedFormat]) {
					AspenDiscovery.MaterialsRequest.specialFields[selectedFormat].forEach(function (specifiedOption) {
						switch (specifiedOption) {
							case 'Abridged/Unabridged':
								$(".abridgedField").show();
								$(".abridgedHideField").hide();
								break;
							case 'Article Field':
								$(".articleField").show();
								$(".articleHideField").hide();
								break;
							case 'Eaudio format':
								$(".eaudioField").show();
								$(".eaudioHideField").hide();
								break;
							case 'Ebook format':
								$(".ebookField").show();
								$(".ebookHideField").hide();
								break;
							case 'Season':
								$(".seasonField").show();
								$(".seasonHideField").hide();
								break;
						}
					})
				}
			}


			//Update labels as needed
			if (AspenDiscovery.MaterialsRequest.authorLabels){
				if (AspenDiscovery.MaterialsRequest.authorLabels[selectedFormat]) {
					$("#authorFieldLabel").html(AspenDiscovery.MaterialsRequest.authorLabels[selectedFormat]);
				//	TODO: Set when required
				}
			}

			if ((hasSpecialFields && AspenDiscovery.MaterialsRequest.specialFields[selectedFormat] && AspenDiscovery.MaterialsRequest.specialFields[selectedFormat].indexOf('Article Field') > -1)){
				$("#magazineTitle,#acceptCopyrightYes").addClass('required');
				$("#acceptCopyrightYes").addClass('required');
				$("#copyright").show();
				$("#supplementalDetails").hide();
				$("#titleLabel").hide();
				$("#articleTitleLabel").show();
			}else{
				$("#magazineTitle,#acceptCopyrightYes").removeClass('required');
				$("#copyright").hide();
				$("#supplementalDetails").show();
				$("#titleLabel").show();
				$("#articleTitleLabel").hide();
			}

		},

		updateHoldOptions: function(){
			var placeHold = $("input[name=placeHoldWhenAvailable]:checked").val() === "1" || $("input[name=illItem]:checked").val() === "1";
			// comparison needed to change placeHold to a boolean
			if (placeHold){
				$("#pickupLocationField").show();
				if ($("#pickupLocation").find("option:selected").val() === 'bookmobile'){
					$("#bookmobileStopField").show();
				}else{
					$("#bookmobileStopField").hide();
				}
			}else{
				$("#bookmobileStopField").hide();
				$("#pickupLocationField").hide();
			}
		},

		showSelectHoldCandidateForm: function(id){
			var url = Globals.path + '/MaterialsRequest/AJAX?method=showSelectHoldCandidateForm&id=' + id;
			$.getJSON(url, function (data){
					// noinspection JSUnresolvedReference
					AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons);
				}
			);
			return false;
		},

		'selectHoldCandidate': function () {
			var params = {
				'method': 'selectHoldCandidate',
				requestId: document.querySelector('input[name="requestId"]').value,
				holdCandidateId: document.querySelector('input[name="holdCandidateId"]:checked').value
			};
			var url = Globals.path + '/MaterialsRequest/AJAX';
			$.getJSON(url, params, function (data){
					// noinspection JSUnresolvedReference
					AspenDiscovery.showMessage(data.title, data.modalBody, data.success, data.success);
				}
			);
			return false;
		},

		'checkForExistingRecord' : function () {
			var formatControl = $("#format option:selected");
			var titleControl = $("#title");
			var authorControl = $("#author");
			var isbnControl = $("#isbn");
			var issnControl = $("#issn");
			var upcControl = $("#upc");

			var params = {
				'method': 'checkForExistingRecord',
				'format' : formatControl !== undefined ? formatControl.val() : '',
				'title' : titleControl !== undefined ? titleControl.val() : '',
				'author' : authorControl !== undefined ? authorControl.val() : '',
				'isbn' : isbnControl !== undefined ? isbnControl.val() : '',
				'issn' : issnControl !== undefined ? issnControl.val() : '',
				'upc' : upcControl !== undefined ? upcControl.val() : ''
			};

			//Don't bother checking if we don't have a format
			var enoughDataToCheckForExistingRecord = false;
			if (params.format !== '') {
				if (params.isbn && /[0-9X]/.test(params.isbn)) {
					enoughDataToCheckForExistingRecord = true;
				}
				if (params.issn && /[0-9X]/.test(params.issn)) {
					enoughDataToCheckForExistingRecord = true;
				}
				if (params.upc && /\d/.test(params.upc)) {
					enoughDataToCheckForExistingRecord = true;
				}
				if (params.title !== '' && params.author !== undefined) {
					enoughDataToCheckForExistingRecord = true;
				}
			}

			if (enoughDataToCheckForExistingRecord) {
				var url = Globals.path + '/MaterialsRequest/AJAX';
				$.getJSON(url, params, function (data){
					// noinspection JSUnresolvedReference
					if (data.success && data.hasExistingRecord) {
						// noinspection JSUnresolvedReference
						$("#existingTitleForRequestLink a").attr("href", data.existingRecordLink);
						// noinspection JSUnresolvedReference
						$("#existingTitleForRequestCover a").attr("href", data.existingRecordLink);
						// noinspection JSUnresolvedReference
						$("#existingTitleForRequestCover img").attr("src", data.existingRecordCover);
						$("#existingTitleForRequestAlert").show();
					}else{
						$("#existingTitleForRequestAlert").hide();
					}
				});
			}else{
				$("#existingTitleForRequestAlert").hide();
			}

			return false;
		},

		'checkRequestForExistingRecord' : function (id) {
			var params = {
				'method': 'checkRequestForExistingRecord',
				'id' : id
			};

			var url = Globals.path + '/MaterialsRequest/AJAX';
			$.getJSON(url, params, function (data){
				// noinspection JSUnresolvedReference
				if (data.success) {
					// noinspection JSUnresolvedReference
					$("#existingTitleInformation" + id).html(data.existingRecordInformation);
				}
			});
		},

		validateManageRequestFilters: function () {
			if ($('.statusFilter:checked').length === 0) {
				alert("You must select at least one status to view.");
				return false;
			}
			if ($('.formatFilter:checked').length === 0) {
				alert("You must select at least one format to view.");
				return false;
			}
			if ($('.assigneesFilter:checked').length === 0 && $('#showUnassigned:checked').length === 0) {
				alert("You must select at least one assignee to view.");
				return false;
			}
			return true;
		},

		showRedirectToMaterialsRequestForm: function(title, message, buttonText, url){
			var buttons = "<button type='button' class='btn btn-primary' onclick='window.location.href=\"" + url + "\"'>" + buttonText + "</button>";

			AspenDiscovery.showMessageWithButtons(title, message, buttons);
			return false;
		}
	};
}(AspenDiscovery.MaterialsRequest || {}));