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
			let selectedId = null;

			if (filterType === 'campaign') {
				selectedId = document.getElementById("campaign_id").value
			} else {
				const userSelect = document.getElementById("user_id");
				const userInput = document.getElementById("selected_user_id");

				if (userSelect) {
					selectedId = userSelect.value;
				} else {
					selectedId = userInput.value;
				}
			}

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
					AspenDiscovery.showMessage(data.title, data.message, false, true, false, false);
				} else {
					AspenDiscovery.showMessage(data.title, data.message);
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
					AspenDiscovery.showMessage(data.title, data.message, false, true, false, false);
				} else {
					AspenDiscovery.showMessage(data.title, data.message);
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
					AspenDiscovery.showMessage(data.title, data.message, false, true, false, false);
				} else {
					AspenDiscovery.showMessage(data.title, data.message);
				}
			})
			.fail(function(jqXHR, textStatus, errorThrown) {
				console.error("AJAX Error: ", textStatus, errorThrown);
			});
		},
		toggleCampaignEmailOptIn: function (campaignId, userId, optIn, showMessage = true) {
			var url = Globals.path + "/CommunityEngagement/AJAX?method=saveCampaignEmailOptInToggle";
			var params = {
				campaignId: campaignId, 
				userId: userId, 
				optIn: optIn,
			};

			$.getJSON(url, params, function(data) {
				if (data.success) {
					if (showMessage) {
						AspenDiscovery.showMessage(data.title, data.message, false, true, false, false);
					}
				} else {
					AspenDiscovery.showMessage(data.title, data.message);
				}
			})
			.fail(function(jqXHR, textStatus, errorThrown) {
				console.error("AJAX Error: ", textStatus, errorThrown);
			});
		},
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

				const topPanel = editor.Panels.getPanel('options');
				if (topPanel) {
					topPanel.view.$el.css({
						'flex-wrap': 'nowrap',
						'overflow': 'visible',
						'min-height': '40px',
						'justify-content': 'flex-start'
					});
				}

				editor.Panels.getPanels().forEach(panel => {
					panel.get('buttons').forEach(button => {
						button.set({
							attributes: { 
								style: 'width: auto; min-width: 10px; min-height: 10px; display: flex; align-items: center; justify-content: center;'
							}
						});
					});
				});
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
					AspenDiscovery.showMessage(data.title, data.message);
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
					AspenDiscovery.showMessage(data.title, data.message);
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
			let rewardType = document.querySelector('[name="rewardType"]').value;
			let displayRewardNameControl = document.getElementById('propertyRowdisplayName');
			let automaticRewardControl = document.getElementById('propertyRowawardAutomatically');

			if (rewardType == 0) {
				displayRewardNameControl.style.display = 'none';
				automaticRewardControl.style.display = 'none';
			} else {
				displayRewardNameControl.style.display = '';
				automaticRewardControl.style.display = '';
			}
		},
		updateConditionalOperator: function () {
			let conditionalField = document.querySelector('[name="conditionalField"]');
			let conditionalOperator = document.querySelector('[name="conditionalOperator"]');

			if (!conditionalField || !conditionalOperator) return;

			let isLikeOption = conditionalOperator.querySelector('option[value="like"]');

			if(isLikeOption) {
				isLikeOption.style.display = (conditionalField.value === 'user_list') ? 'none' : '';
			}
			
		},
		getLibraryUsers: function (callback) {
			var url = Globals.path + "/CommunityEngagement/AJAX";
			var params = {
				method: 'getLibraryUsers',
			};

			$.getJSON(url, params, function (data) {
				if (data.success && data.users) {
					callback(data.users);
				} else{
					callback([]);
				}
			}).fail (function(jqXHR, textStatus, errorThrown) {
				AspenDiscovery.ajaxFail(jqXHR, textStatus, errorThrown);
				callback([]);
			});
		},
		displaySearchResults: function (users) {
			const resultsDiv = document.getElementById('user_search_results');

			if (users.length === 0) {
				resultsDiv.innerHTML = '<div class="search-result-item">No users found</div>';
			} else {
				resultsDiv.innerHTML = users.map(user =>
					`<div class="search-result-item" onclick="AspenDiscovery.CommunityEngagement.selectUser('${user.id}', '${user.displayName.replace(/'/g, "\\'")}')">
						${user.displayName}
					</div>`
				).join('');
			}
			resultsDiv.style.display = 'block';
		},
		selectUser: function (userId, userName) {
			document.getElementById('user_search').value = userName;
			document.getElementById('selected_user_id').value = userId;
			document.getElementById('user_search_results').style.display = 'none';

			AspenDiscovery.CommunityEngagement.filterDropdownOptions('user');

		},
		searchUsers: function (query) {
			const resultsDiv = document.getElementById('user_search_results');
			const hiddenInput = document.getElementById('selected_user_id');

			if (query.length < 2) {
				resultsDiv.style.display = 'none';
				hiddenInput.value = '';
				return;
			}

			hiddenInput.value = '';
			AspenDiscovery.CommunityEngagement.getLibraryUsers(function(users) {
				const filteredUsers = users.filter(user =>
					user.displayName.toLowerCase().includes(query.toLowerCase())
				);
				AspenDiscovery.CommunityEngagement.displaySearchResults(filteredUsers);
			});
		},
		loadCheckoutsForUser: function(userId, callback) {
			let url = Globals.path + "/MyAccount/AJAX";
			var params = {
				method: 'getUserCheckouts',
				userId: userId, 
			}

			$.getJSON(url, params, function(data) {
				if (!data.success) {
					AspenDiscovery.showMessage(data.title, data.message);
				}
				if (callback) callback();
			}).fail(function(jqXHR, textStatus, errorThrown) {
				AspenDiscovery.ajaxFail(jqXHR, textStatus, errorThrown);
				if (callback) callback();
			});
		},
		loadHoldsForUser: function(userId, callback) {
			let url = Globals.path + "/MyAccount/AJAX";
			var params = {
				method: 'getUserHolds',
				userId: userId, 
			}

			$.getJSON(url, params, function(data) {
				if (!data.success) {
					AspenDiscovery.showMessage(data.title, data.message);
				}
				if (callback) callback();
			}).fail(function(jqXHR, textStatus, errorThrown) {
				AspenDiscovery.ajaxFail(jqXHR, textStatus, errorThrown);
				if (callback) callback();
			});
		},
		adminEnrollPatron: function(campaignId, userId, userEmailOptInSetting) {

			Promise.all([
				new Promise(resolve => AspenDiscovery.CommunityEngagement.loadCheckoutsForUser(userId, resolve)),
				new Promise(resolve => AspenDiscovery.CommunityEngagement.loadHoldsForUser(userId, resolve))
			]).then(() => {
				const url = Globals.path + "/MyAccount/AJAX";
				const params = {
					method: 'enrollCampaign',
					campaignId: campaignId,
					userId: userId, 
				};

				$.getJSON(url, params).done(function (data) {
					if (data.success) {
						const emailOptIn = userEmailOptInSetting === 1 ? 1 : 0;

						AspenDiscovery.CommunityEngagement.toggleCampaignEmailOptIn(
							campaignId,
							userId,
							emailOptIn,
							false
						);

						const refreshurl = Globals.path + "/CommunityEngagement/AJAX";
						const refreshParams = {
							method: 'filterCampaigns',
							filterType: 'user',
							userId: userId
						};

						setTimeout(() => {
							$.getJSON(refreshurl, refreshParams, function(refreshData) {
								if (refreshData.success && refreshData.html) {
									$("#filteredCampaign").html(refreshData.html);
								}
							});
						}, 300);
					} else {
						AspenDiscovery.showMessage(data.title, data.message);
					}
				}).fail(function (jqXHR, textStatus, errorThrown) {
					AspenDiscovery.ajaxFail(jqXHR, textStatus, errorThrown);
				});
			});
		},
		adminUnenroll: function (campaignId, userId) {
				var url = Globals.path + "/MyAccount/AJAX";
				var params = {
					method: 'unenrollCampaign',
					campaignId: campaignId,
					userId: userId,
				};

				$.getJSON(url, params, function(data) {
					if (data.success) {
						const refreshUrl = Globals.path + "/CommunityEngagement/AJAX";
						const refreshParams = {
							method: 'filterCampaigns',
							filterType: 'user',
							userId: userId
						};

						$.getJSON(refreshUrl, refreshParams, function(refreshData) {
							if (refreshData.success && refreshData.html) {
								$("#filteredCampaign").html(refreshData.html);
							}
						});
					} else {
						AspenDiscovery.showMessage(data.title, data.message);
					}
				}).fail(function(jqXHR, textStatus, errorThrown) {
					AspenDiscovery.ajaxFail(jqXHR, textStatus, errorThrown);
				});
		},
		adminManuallyProgressMilestone: function (milestoneId, userId, campaignId) {
			var url = Globals.path + "/CommunityEngagement/AJAX?method=manuallyProgressUserMilestone";
			const params = {
				milestoneId: milestoneId,
				userId: userId,
				campaignId: campaignId
			};
			
			$.getJSON(url, params, function (data) {
				if (data.success) {
					const refreshUrl = Globals.path + "/CommunityEngagement/AJAX";
					const refreshParams = {
						method: 'filterCampaigns',
						filterType: 'user',
						userId: userId
					};

					$.getJSON(refreshUrl, refreshParams, function (refreshData) {
						if (refreshData.success && refreshData.html) {
							$("#filteredCampaign").html(refreshData.html);
						}
					});
				} else {
					AspenDiscovery.showMessage(data.title, data.message);
				}
			}).fail (function(jqXHR, textStatus, errorThrown) {
				AspenDiscovery.ajaxFail(jqXHR, textStatus, errorThrown);
			})
		},
		adminCampaignRewardGiven: function (userId, campaignId) {
				var url = Globals.path + "/CommunityEngagement/AJAX?method=campaignRewardGivenUpdate";
				const params = {
					userId: userId,
					campaignId: campaignId
				};

				$.getJSON(url, params, function (data) {
				if (data.success) {
					const refreshUrl = Globals.path + "/CommunityEngagement/AJAX";
					const refreshParams = {
						method: 'filterCampaigns',
						filterType: 'user',
						userId: userId
					};

					$.getJSON(refreshUrl, refreshParams, function (refreshData) {
						if (refreshData.success && refreshData.html) {
							$("#filteredCampaign").html(refreshData.html);
						}
					});
				} else {
					AspenDiscovery.showMessage(data.title, data.message);
				}
			}).fail(function (jqXHR, textStatus, errorThrown) {
				AspenDiscovery.ajaxFail(jqXHR, textStatus, errorThrown);
			});
		},
		adminMilestoneRewardGiven: function (userId, campaignId, milestoneId) {
			var url = Globals.path + "/CommunityEngagement/AJAX?method=milestoneRewardGivenUpdate";
			const params = {
				userId: userId,
				campaignId: campaignId,
				milestoneId: milestoneId
			};

				$.getJSON(url, params, function (data) {
				if (data.success) {
					const refreshUrl = Globals.path + "/CommunityEngagement/AJAX";
					const refreshParams = {
						method: 'filterCampaigns',
						filterType: 'user',
						userId: userId
					};

					$.getJSON(refreshUrl, refreshParams, function (refreshData) {
						if (refreshData.success && refreshData.html) {
							$("#filteredCampaign").html(refreshData.html);
						}
					});
				} else {
					AspenDiscovery.showMessage(data.title, data.message);
				}
			}).fail(function (jqXHR, textStatus, errorThrown) {
				AspenDiscovery.ajaxFail(jqXHR, textStatus, errorThrown);
			});
		},
		addUserByBarcode: function () {
			const barcode = $('#newUserBarcode').val();
			var url = Globals.path + "/CommunityEngagement/AJAX?method=addUserByBarcode";

			$.ajax({
				method: 'POST',
				url: url,
				data: {
					barcode: barcode
				},
				success: function (data) {
					if (data.success) {
						AspenDiscovery.showMessage(data.title, data.message);
						AspenDiscovery.CommunityEngagement.getLibraryUsers(function(users) {
							if ($('#user_id').length > 0) {
								const $dropdown = $('#user_id');
								const currentValue = $dropdown.val();
								$dropdown.empty().append('<option value="">-</option>');
								users.forEach(function(user) {
									const selected = user.id == currentValue ? 'selected' : '';
									$dropdown.append(`<option value="${user.id}" ${selected}>${user.displayName}</option>`);
								});
							}
						});
						$('#addUserByBarcodeModal').modal('hide');
						$('#newUserBarcode').val('');
					} else {
						AspenDiscovery.showMessage(data.title, data.message);
						$('#addUserByBarcodeModal').modal('hide');
						$('#newUserBarcode').val('');
					}
				},
				error: function () {
					alert('Error communicating with server.');
				}
			});
		},
		refreshCurrentUserStats: function(userId) {
			Promise.all([
				new Promise(resolve => AspenDiscovery.CommunityEngagement.loadCheckoutsForUser(userId, resolve)),
				new Promise(resolve => AspenDiscovery.CommunityEngagement.loadHoldsForUser(userId, resolve))
			]).then(() => {
				const refreshUrl = Globals.path + "/CommunityEngagement/AJAX";
				const refreshParams = {
					method: 'filterCampaigns',
					filterType: 'user',
					userId: userId
				};

				$.getJSON(refreshUrl, refreshParams, function(refreshData) {
					if (refreshData.success && refreshData.html) {
						$("#filteredCampaign").html(refreshData.html);
					} else {
						AspenDiscovery.showMessage('Error', 'Failed to refresh campaign data.');
					}
				});
			}).catch(() => {
				AspenDiscovery.showMessage('Error', 'Failed to load user data.');
			});
		},
		displayExtraCreditBentoBox: function () {
			let addExtraCreditActivities = document.getElementById('addExtraCreditActivities');
			let extraCreditBentoBox = document.getElementById('propertyRowavailableExtraCreditActivities');



			if (addExtraCreditActivities && addExtraCreditActivities.checked) {
				extraCreditBentoBox.style.display = '';

			} else {
				extraCreditBentoBox.style.display = 'none';
			}
		},
		addProgressToExtraCreditActivity: function (extraCreditActivityId, userId, campaignId) {
			var url = Globals.path + "/CommunityEngagement/AJAX?method=addProgressToExtraCreditActivities";
			var params = {
				extraCreditActivityId : extraCreditActivityId,
				userId: userId,
				campaignId: campaignId,
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
		extraCreditRewardGiven: function(userId, campaignId, extraCreditActivityId) {
			var url = Globals.path + "/CommunityEngagement/AJAX?method=extraCreditRewardGivenUpdate";
			var params = {
				userId: userId,
				campaignId: campaignId,
				extraCreditActivityId: extraCreditActivityId,
			};
			$.getJSON(url, params,
				function(data) {
					if (data.success) {
						AspenDiscovery.showMessage(data.title, data.message, false, true, false, false);
					} else {
						AspenDiscovery.showMessage(data.title, data.message);
					}
				})
				.fail(function(jqXHR, textStatus, errorThrown) {
					alert('An error occurred while updating the reward status for this milestone.' + textStatus + ', ' + errorThrown);
				});
		},
		adminManuallyProgressExtraCredit: function(extraCreditActivityId, userId, campaignId) {
			var url = Globals.path + "/CommunityEngagement/AJAX?method=addProgressToExtraCreditActivities";

			const params = {
				extraCreditActivityId: extraCreditActivityId,
				userId: userId,
				campaignId: campaignId
			};

			$.getJSON(url, params, function (data) {
				if (data.success) {
					const refreshUrl = Globals.path + "/CommunityEngagement/AJAX";
					const refreshParams = {
						method: 'filterCampaigns',
						filterType: 'user',
						userId: userId
					};

					$.getJSON(refreshUrl, refreshParams, function (refreshData) {
						if (refreshData.success && refreshData.html) {
							$("#filteredCampaign").html(refreshData.html);
						}
					});
				} else {
					AspenDiscovery.showMessage(data.title, data.message);
				}
			}).fail(function (jqXHR, textStatus, errorThrown) {
				AspenDiscovery.ajaxFail(jqXHR, textStatus, errorThrown);
			})
		}
	}
	
}(AspenDiscovery.CommunityEngagement || {});