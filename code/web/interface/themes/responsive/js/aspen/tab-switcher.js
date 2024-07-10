/*
 *   This content is licensed according to the W3C Software License at
 *   https://www.w3.org/Consortium/Legal/2015/copyright-software-and-document
 *
 *   Desc:   Implements ARIA Authoring Practices for switching tabs
 */
'use strict';
function TabsSwitcher (groupNode) {
	this.tablistNode = groupNode;
	this.tabs = [];
	this.firstTab = null;
	this.lastTab = null;
	this.tabs = Array.from(this.tablistNode.querySelectorAll('[role=tab]'));
	this.tabpanels = [];

	for (var i = 0; i < this.tabs.length; i++) {
		var tab = this.tabs[i];
		var tabPanel = document.getElementById(tab.getAttribute('aria-controls'));

		tab.setAttribute('aria-selected', 'false');
		this.tabpanels.push(tabPanel);

		tab.addEventListener('keydown', this.onKeydown.bind(this));
		tab.addEventListener('click', this.onClick.bind(this));

		if (!this.firstTab) {
			this.firstTab = tab;
		}
		this.lastTab = tab;
	}

	//this.setSelectedTab(this.firstTab, false);
}

TabsSwitcher.prototype.setSelectedTab = function (currentTab, setFocus) {
	if (typeof setFocus !== 'boolean') {
		setFocus = true;
	}
	for (var i = 0; i < this.tabs.length; i++) {
		var tab = this.tabs[i];
		if (currentTab === tab) {
			//$('#collectionSpotlightCarousel' + tab.dataset.carouselid).jcarousel('reload');
			$(tab).attr('aria-selected', 'true');
			$(tab).addClass('active');
			//this.tabs[i].classList.add('active');
			if (setFocus) {
				tab.focus();
			}
		} else {
			//$('#collectionSpotlightCarousel' + tab.dataset.carouselid).jcarousel('reload');
			$(tab).attr('aria-selected', 'false');
			$(tab).removeClass('active');
			//this.tabs[i].classList.remove('active');
		}
	}
}

TabsSwitcher.prototype.setSelectedToPreviousTab = function (currentTab) {
	var index;

	if (currentTab === this.tabs.firstTab) {
		this.setSelectedTab(this.tabs.lastTab);
	} else {
		index = this.tabs.indexOf(currentTab);
		this.setSelectedTab(this.tabs[index - 1]);
	}
}

TabsSwitcher.prototype.setSelectedToNextTab = function (currentTab) {
	var index;

	if (currentTab === this.tabs.lastTab) {
		this.setSelectedTab(this.tabs.firstTab);
	} else {
		index = this.tabs.indexOf(currentTab);
		this.setSelectedTab(this.tabs[index + 1]);
	}
}

TabsSwitcher.prototype.onKeydown = function(event) {
	var tgt = event.currentTarget;
	var flag = false;

	switch (event.key) {
		case 'ArrowLeft':
			this.setSelectedToPreviousTab(tgt);
			flag = true;
			break;

		case 'ArrowRight':
			this.setSelectedToNextTab(tgt);
			flag = true;
			break;

		case 'Home':
			this.setSelectedTab(this.tabs.firstTab);
			flag = true;
			break;

		case 'End':
			this.setSelectedTab(this.tabs.lastTab);
			flag = true;
			break;

		default:
			break;
	}

	if (flag) {
		event.stopPropagation();
		event.preventDefault();
	}
}

TabsSwitcher.prototype.onClick = function(event) {
	this.setSelectedTab(event.currentTarget);
}
