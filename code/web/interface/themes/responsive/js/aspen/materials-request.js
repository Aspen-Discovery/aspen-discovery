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
			if (newStatus === "unselected"){
				alert("Please select a status to update the requests to.");
				return false;
			}
			var selectedRequests = this.getSelectedRequests(false);
			if (selectedRequests.length !== 0){
				$("#updateRequests").submit();
			}
			return false;
		},

		assignSelectedRequests: function(){
			var newAssignee = $("#newAssignee").val();
			if (newAssignee === "unselected"){
				alert("Please select a user to assign the requests to.");
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
			var placeHold = $("input[name=placeHoldWhenAvailable]:checked").val() === 1 || $("input[name=illItem]:checked").val() === 1;
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
				holdCandidateId: document.querySelector('input[name="holdCandidateId"]:checked').value,
			};
			var url = Globals.path + '/MaterialsRequest/AJAX';
			$.getJSON(url, params, function (data){
					// noinspection JSUnresolvedReference
					AspenDiscovery.showMessage(data.title, data.modalBody, data.success, data.success);
				}
			);
			return false;
		}
	};
}(AspenDiscovery.MaterialsRequest || {}));