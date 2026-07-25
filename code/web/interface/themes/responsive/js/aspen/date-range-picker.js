AspenDiscovery.DateRangePicker = {
	COMPONENT_CSS_URL: '/interface/themes/responsive/css/date-range-picker.css',
	FLATPICKR_CSS_URL: 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css',
	FLATPICKR_JS_URL: 'https://cdn.jsdelivr.net/npm/flatpickr',

	_libraryPromise: null,

	ensureStylesheet: function (url, marker) {
		if (document.querySelector('link[' + marker + ']')) {
			return;
		}
		const link = document.createElement('link');
		link.rel = 'stylesheet';
		link.href = url;
		link.setAttribute(marker, 'true');
		document.head.appendChild(link);
	},

	loadScript: function (url) {
		return new Promise(function (resolve, reject) {
			const script = document.createElement('script');
			script.src = url;
			script.onload = resolve;
			script.onerror = reject;
			document.head.appendChild(script);
		});
	},

	loadLibrary: async function () {
		const D = AspenDiscovery.DateRangePicker;
		// flatpickr first so the component stylesheet wins on any equal-specificity tie.
		D.ensureStylesheet(D.FLATPICKR_CSS_URL, 'data-flatpickr-css');
		D.ensureStylesheet(D.COMPONENT_CSS_URL, 'data-date-range-picker-css');
		if (typeof flatpickr !== 'undefined') {
			return;
		}
		if (!D._libraryPromise) {
			D._libraryPromise = D.loadScript(D.FLATPICKR_JS_URL);
		}
		await D._libraryPromise;
	},

	readRangeFromInputs: function (startInput, endInput) {
		const range = [];
		if (startInput && startInput.value) range.push(startInput.value);
		if (endInput && endInput.value) range.push(endInput.value);
		return range;
	},

	writeSelectionToInputs: function (config, selectedDates) {
		if (config.startInput) {
			config.startInput.value = selectedDates[0] ? flatpickr.formatDate(selectedDates[0], 'Y-m-d') : '';
		}
		if (config.endInput) {
			config.endInput.value = selectedDates[1] ? flatpickr.formatDate(selectedDates[1], 'Y-m-d') : '';
		}
	},

	capEndDateWhileSelecting: function (instance, selectedDates, maxRangeDays, absoluteMax) {
		// While only the start is chosen, cap the selectable end at min(start + maxRangeDays,
		// absoluteMax); restore the ceiling once the range completes so the next start isn't stuck.
		if (selectedDates.length === 1 && maxRangeDays > 0) {
			const limit = new Date(selectedDates[0].getTime());
			limit.setDate(limit.getDate() + maxRangeDays);
			instance.set('maxDate', absoluteMax && absoluteMax < limit ? absoluteMax : limit);
		} else if (selectedDates.length !== 1) {
			instance.set('maxDate', absoluteMax || undefined);
		}
	},

	create: function (config) {
		const disabledRanges = config.disabledRanges || [];
		const maxRangeDays = config.maxRangeDays || 0;
		const absoluteMax = config.maxDate || null;
		const initialRange = config.initialRange || D.readRangeFromInputs(config.startInput, config.endInput);

		function isWithinDisabledRange(date) {
			const iso = flatpickr.formatDate(date, 'Y-m-d');
			return disabledRanges.some(function (range) {
				return iso >= range.start && iso <= range.end;
			});
		}

		config.container.classList.add('date-range-picker');
		const anchor = document.createElement('input');
		anchor.type = 'hidden';
		config.container.appendChild(anchor);

		return flatpickr(anchor, {
			mode: 'range',
			inline: true,
			dateFormat: 'Y-m-d',
			minDate: config.minDate || 'today',
			maxDate: absoluteMax || undefined,
			defaultDate: initialRange.length === 2 ? initialRange : undefined,
			disable: [isWithinDisabledRange],
			onChange: function (selectedDates, dateStr, instance) {
				D.writeSelectionToInputs(config, selectedDates);
				D.capEndDateWhileSelecting(instance, selectedDates, maxRangeDays, absoluteMax);
			},
		});
	},

	render: async function (container, config) {
		const D = AspenDiscovery.DateRangePicker;
		await D.loadLibrary();
		if (container._dateRangePicker) {
			container._dateRangePicker.destroy();
		}
		container.replaceChildren();
		config.container = container;
		container._dateRangePicker = D.create(config);
		return container._dateRangePicker;
	},
};
