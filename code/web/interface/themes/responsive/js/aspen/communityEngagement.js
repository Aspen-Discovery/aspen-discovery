AspenDiscovery.CommunityEngagement = function() {
	return {
		campaignRewardGiven: function(userId, campaignId) {
			var url = Globals.path + "/CommunityEngagement/AJAX?method=campaignRewardGivenUpdate";
			var params = {
				userId: userId, 
				campaignId: campaignId,
			};
			$.getJSON(url, params, 
				function(data) {
					if (data.success) {
						var button = $('.set-reward-btn[data-user-id="' + userId + '"][data-campaign-id="' + campaignId + '"]');
						button.replaceWith('<span>Reward Given</span>');
					} else {
						alert("Error: " + data.message);
					}
				})
				.fail(function(jqXHR, textStatus, errorThrown){
			   
				alert('An error occurred while updating the reward status.' + textStatus + ', ' + errorThrown);
				});
		},
		milestoneRewardGiven: function(userId, campaignId, milestoneId) {
			var url = Globals.path + "/CommunityEngagement/AJAX?method=milestoneRewardGivenUpdate";
			var params = {
				userId: userId,
				campaignId: campaignId,
				milestoneId: milestoneId,
			};
			$.getJSON(url, params,
				function(data) {
					if (data.success) {
						var button = $('.set-reward-btn-milestone[data-user-id="' + userId + '"][data-campaign-id="' + campaignId + '"][data-milestone-id="' + milestoneId + '"]');
						button.replaceWith('<span>Milestone Reward Given</span>');
					} else {
						alert("Error: " + data.message);
					}
				})
				.fail(function(jqXHR, textStatus, errorThrown) {
					alert('An error occurred while updating the reward status for this milestone.' + textStatus + ', ' + errorThrown);
				});
		},
		filterDropdownOptions: function(filterType) {

			var selectedId = (filterType === 'campaign') ? document.getElementById("campaign_id").value : document.getElementById("user_id").value;
			var url = Globals.path + "/CommunityEngagement/AJAX?method=filterCampaigns";
			var params = {
				campaignId: filterType === "campaign" ? selectedId : null,
				userId: filterType === "user" ? selectedId : null,
				filterType: filterType
			}
			
			//Show/hide campaigns list and filtered campaigns divs
			var campaignsList = document.getElementById("campaignsList");
			var filteredCampaign = document.getElementById("filteredCampaign");

	 
			$.getJSON(url, params, 
				function(data) {
					if (data.success) {
						$('#filteredCampaign').html(data.html);
						filteredCampaign.style.display = "block"; 
						campaignsList.style.display = "none";
					} else {
						alert("Error:" +  data.message);
					}
				})
				.fail(function() {
					console.error('Error retrieving campaign data.');
				});
		
		},
		filterLeaderboard: function() {
			var selectedCampaignId = document.getElementById("campaign_id").value;
			var url = Globals.path + "/CommunityEngagement/AJAX?method=filterLeaderboardCampaigns";
			var params = {
				campaignId: selectedCampaignId
			}

			$.getJSON(url, params, function(data) {
				if (data.success) {
					$('#leaderboard-table').html(data.html);
					$('#campaign-name').html(data.campaignName);
				} else {
					$('#leaderboard-table').html(data.html);
					$('#campaign-name').html(data.campaignName);
				}
			})
			.fail(function(jqXHR, textStatus, errorThrown) {
				console.error("AJAX Error: ", textStatus, errorThrown);
			});
		},

		filterBranchLeaderboard: function() {
			var selectedCampaignId = document.getElementById("campaign_id").value;
			var url = Globals.path + "/CommunityEngagement/AJAX?method=filterBranchLeaderboardCampaigns";
			var params = {
				campaignId: selectedCampaignId
			}

			$.getJSON(url, params, function (data) {
				if (data.success) {
					$('#leaderboard-table').html(data.html);
					$('#campaign-name').html(data.campaignName);
				} else {
					$('#leaderboard-table').html(data.html);
					$('#campaign-name').html(data.campaignName);
				}
			})
			.fail(function(jqXHR, textStatus, errorThrown) {
				console.error("AJAX Error: ", textStatus, errorThrown);
			});
		},

		filterLeaderboardType: function () {
			let leaderboardType = document.getElementById("main-content").dataset.leaderboardType;

			if (leaderboardType === "displayUser") {
				AspenDiscovery.CommunityEngagement.filterLeaderboard();
			} else {
				AspenDiscovery.CommunityEngagement.filterBranchLeaderboard();
			}
		},

		updateManualMilestoneFields: function () {
			let milestoneType = document.querySelector('[name="milestoneType"]').value;
			let allowPatronProgressInput = document.getElementById('propertyRowallowPatronProgressInput');
			let conditionalField = document.getElementById('propertyRowconditionalField');
			let conditionalOperator = document.getElementById('propertyRowconditionalOperator');
			let conditionalValue = document.getElementById('propertyRowconditionalValue');
			let description = document.getElementById('propertyRowdescription');



			if (milestoneType !== 'manual') {
				allowPatronProgressInput.style.display = 'none';
				description.style.display = 'none';
				conditionalField.style.display = '';
				conditionalOperator.style.display = '';
				conditionalValue.style.display = '';

			} else {
				allowPatronProgressInput.style.display = '';
				description.style.display = '';
				conditionalField.style.display = 'none';
				conditionalOperator.style.display = 'none';
				conditionalValue.style.display = 'none';

			}
		},
		manuallyProgressMilestone: function (milestoneId, userId, campaignId) {
			var url = Globals.path + "/CommunityEngagement/AJAX?method=manuallyProgressUserMilestone";
			var params = {
				milestoneId : milestoneId,
				userId: userId,
				campaignId: campaignId,
			};

			$.getJSON(url, params, function(data) {
				if (data.success) {
					AspenDiscovery.showMessage("Progress Added", data.message, false, true, false, false);
				} else {
					AspenDiscovery.showMessage("An Error Has Occurred", data.message);
				}
			})
			.fail(function(jqXHR, textStatus, errorThrown) {
				console.error("AJAX Error: ", textStatus, errorThrown);
			});
		},
		optInToCampaignLeaderboard: function (campaignId, userId) {
			var url = Globals.path + "/CommunityEngagement/AJAX?method=campaignLeaderboardOptIn";
			var params = {
				campaignId: campaignId,
				userId: userId,
			}
			$.getJSON(url, params, function(data) {
				if (data.success) {
					AspenDiscovery.showMessage("Joined Leaderboard", data.message, false, true, false, false);
				} else {
					AspenDiscovery.showMessage("An Error Has Occurred", data.message);
				}
			})
			.fail(function(jqXHR, textStatus, errorThrown) {
				console.error("AJAX Error: ", textStatus, errorThrown);
			});
		},
		optOutOfCampaignLeaderboard: function (campaignId, userId) {
			var url = Globals.path + "/CommunityEngagement/AJAX?method=campaignLeaderboardOptOut";
			var params = {
				campaignId: campaignId,
				userId: userId,
			}
			$.getJSON(url, params, function(data) {
				if (data.success) {
					AspenDiscovery.showMessage("Opted Out of Leaderboard", data.message, false, true, false, false);
				} else {
					AspenDiscovery.showMessage("An Error Has Occurred", data.message);
				}
			})
			.fail(function(jqXHR, textStatus, errorThrown) {
				console.error("AJAX Error: ", textStatus, errorThrown);
			});
		},
		toggleCampaignEmailOptIn: function (campaignId, userId, optIn) {
			var url = Globals.path + "/CommunityEngagement/AJAX?method=saveCampaignEmailOptInToggle";
			var params = {
				campaignId: campaignId, 
				userId: userId, 
				optIn: optIn,
			};

			$.getJSON(url, params, function(data) {
				if (data.success) {
					AspenDiscovery.showMessage(data.title, data.message, false, true, false, false);
				} else {
					AspenDiscovery.showMessage(data.title, data.message);
				}
			})
			.fail(function(jqXHR, textStatus, errorThrown) {
				console.error("AJAX Error: ", textStatus, errorThrown);
			});
		},

		openLeaderboardEditor: function() {
			document.getElementById("gjs").style.display = 'block';
			document.getElementById("saveLeaderboardBtn").style.display = 'inline-block';

			const editor = grapesjs.init({
				container: '#gjs',
				height: '90vh',
				StorageManager: { type: 'none'},
				panels: { defaults: [] },
				fromElement: false
			});

			const leaderboardHTML = document.getElementById("main-content").innerHTML;
			editor.setComponents(leaderboardHTML);

			const leaderboardComponents = editor.getComponents();
			leaderboardComponents.each(component => {
				if (component.is('div') || component.is('h1') || component.is('select')) {
					component.set({ draggable: false, removable: false, copyable: false });
				}
			});

		},

		saveLeaderboardChanges: function() {
			const editor = grapesjs.editors[0];
			const updatedHTML = editor.getHtml();
			var url = Globals.path + "/CommunityEngagement/AJAX?method=saveLeaderboardChanges";

			var params = {
				html: updatedHTML
			};

			$.ajax({
				url: url,
				type: "POST",
				data: JSON.stringify(params),
				dataType: 'json',
				success: function(data) {
					if (data.success) {
						AspenDiscovery.showMessage(data.title, data.message, false, true, false, false);
						editor.destroy();
						$("#gjs").hide();
						$("#main-content").show();
					} else {
						AspenDiscovery.showMessage(data.title, data.message);
					}
				},
				error: function(jqXHR, textStatus, errorThrown) {
					console.error("AJAX Error: ", textStatus, errorThrown);
				}
			});
		}

	}
	
}(AspenDiscovery.CommunityEngagement || {});