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
		//TODO:: REMOVE VAR DUMPS AND ERROR LOGS ETC AND ADD BLOCKS FOR GRAPES
		getSavedLeaderboardCss: function() {
			var url = Globals.path + "/CommunityEngagement/AJAX?method=getLeaderboardData";
			var cssData = '';

			$.ajax({
				url: url, 
				method: 'GET',
				dataType: 'json',
				async: false,
				success: function (data) {
					if (data.css) {
						cssData = data.css;
					}
				},
				error: function(jqXHR, textStatus, errorThrown) {
					console.error("AJAX Error: ", textStatus, errorThrown);
				}
			})
			return cssData;
		},
		
		

		openLeaderboardEditor: function() {
			// Show the GrapesJS editor and hide the main content
			document.getElementById("gjs").style.display = 'block';
			document.getElementById("main-content").style.display = 'none';
			const mainContent = document.getElementById("main-content");
			const leaderboardHTML = mainContent.innerHTML;
			let leaderboardCSS = '';

			// const styleTags = document.getElementsByTagName('style');
			// for (let i = 0; i < styleTags.length; i++) {
			// 	leaderboardCSS += styleTags[i].innerHTML;
			// }
			leaderboardCSS = AspenDiscovery.CommunityEngagement.getSavedLeaderboardCss();

			const editor = grapesjs.init({
				container: '#gjs',
				storageManager: {
					type: 'none'
				},
				plugins: [
					'gjs-blocks-basic', 
				],
				pluginOpts: {
					'gjs-blocks-basic': {},
				},
				fromElement: false,
			});
			editor.Panels.addButton('options', [{
				id: 'save-as-page',
				className: 'fas fa-save',
				command: 'saveLeaderboardChanges',
				attributes: { title: 'Save as Page' }
			}]);
			editor.Commands.add('saveLeaderboardChanges', {
				run: function (){
					const updatedHTML = editor.getHtml();

					document.getElementById("main-content").innerHTML = updatedHTML;

					const updatedCSS = editor.getCss();

					document.getElementById("gjs").style.display = 'none';
					document.getElementById("main-content").style.display = 'block';

					AspenDiscovery.CommunityEngagement.saveTemplateToDatabase(updatedHTML, updatedCSS);

				}
			});
			editor.on('load', () => {
				editor.setComponents(leaderboardHTML);
				editor.setStyle(leaderboardCSS);
			})
		},

		saveTemplateToDatabase: function(updatedHTML, updatedCSS) {
			var url = Globals.path + "/CommunityEngagement/AJAX?method=saveLeaderboardChanges";
			const params = {
				html: updatedHTML,
				css: updatedCSS, 
				templateName: 'leaderboard_template'
			};
			$.ajax({
				url: url,
				type: "POST",
				contentType: "application/json",
				data: JSON.stringify(params),
				dataType: "json",
				success: function (data) {
					if (data.success) {
						AspenDiscovery.showMessage(data.title, data.message, false, true, false, false);
					} else {
						AspenDiscovery.showMessage(data.title, data.message);
					}
				},
				error: function (jqXHR, textStatus, errorThrown) {
					console.error("AJAX Error: ", textStatus, errorThrown);
					console.error("Response Text:", jqXHR.responseText); // Log raw response
				}
			})
		},
		resetLeaderboard: function() {
			var url = Globals.path + "/CommunityEngagement/AJAX?method=resetLeaderboardDisplay";
			$.getJSON(url, function(data) {
				if (data.success) {
					AspenDiscovery.showMessage(data.title, data.message, false, true, false, false);
				} else {
					AspenDiscovery.showMessage(data.title, data.message);
				}
			})
			
		},
		
		optInToCampaignEmailNotifications: function (campaignId, userId) {
			var url = Globals.path + "/CommunityEngagement/AJAX?method=campaignEmailOptIn";
			var params = {
				campaignId: campaignId,
				userId: userId,
			}
			$.getJSON(url, params, function(data) {
				if (data.success) {
					AspenDiscovery.showMessage(data.title, data.message, false, true, false, false);
				} else {
					AspenDiscovery.showMessage("An Error Has Occurred", data.message);
				}
			})
			.fail(function(jqXHR, textStatus, errorThrown) {
				console.error("AJAX Error: ", textStatus, errorThrown);
			});
		},
		optOutOfCampaignEmailNotifications: function (campaignId, userId) {
			var url = Globals.path + "/CommunityEngagement/AJAX?method=campaignEmailOptOut";
			var params = {
				campaignId: campaignId,
				userId: userId,
			}
			$.getJSON(url, params, function(data) {
				if (data.success) {
					AspenDiscovery.showMessage(data.title, data.message, false, true, false, false);
				} else {
					AspenDiscovery.showMessage("An Error Has Occurred", data.message);
				}
			})
			.fail(function(jqXHR, textStatus, errorThrown) {
				console.error("AJAX Error: ", textStatus, errorThrown);
			});
		},
		handleCampaignEnrollment: function (campaignId, userId, emailOptIn) {

			var url = Globals.path + "/MyAccount/AJAX";
			var params = {
				method: 'enrollCampaign',
				campaignId: campaignId,
				userId: userId,
				emailOptIn: emailOptIn
			};

			$.getJSON(url, params, function (data) {
				if (data.success) {
					AspenDiscovery.CommunityEngagement.toggleCampaignEmailOptIn(campaignId, userId, emailOptIn);
				} else {
					AspenDiscovery.showMessage(data.title, data.message);
				}
			}).fail (function (jqXHR, textStatus, errorThrown) {
				AspenDiscovery.ajaxFail(jqXHR, textStatus, errorThrown);
			})
		},
		updateRewardFields: function () {
			console.log("update reward");
			let rewardType = document.querySelector('[name="rewardType"]').value;
			let displayRewardNameControl = document.getElementById('propertyRowdisplayName');



			if (rewardType == 0) {
				displayRewardNameControl.style.display = 'none';
			} else {
				displayRewardNameControl.style.display = '';
			}
		},

	}
	
}(AspenDiscovery.CommunityEngagement || {});