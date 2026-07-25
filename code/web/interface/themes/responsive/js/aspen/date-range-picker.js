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
		// flatpickr first so the component stylesheet wins on any equal-specificity tie.
		this.ensureStylesheet(this.FLATPICKR_CSS_URL, 'data-flatpickr-css');
		this.ensureStylesheet(this.COMPONENT_CSS_URL, 'data-date-range-picker-css');
		if (typeof flatpickr !== 'undefined') {
			return;
		}
		if (!this._libraryPromise) {
			this._libraryPromise = this.loadScript(this.FLATPICKR_JS_URL);
		}
		await this._libraryPromise;
	},

	readRangeFromInputs: function (startInput, endInput) {
		return [startInput, endInput].filter(input => input && input.value).map(input => input.value);
	},

	writeSelectionToInputs: function (config, selectedDates) {
		[config.startInput, config.endInput].forEach((input, i) => {
			if (input) {
				input.value = selectedDates[i] ? flatpickr.formatDate(selectedDates[i], 'Y-m-d') : '';
			}
		});
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
		const initialRange = config.initialRange || this.readRangeFromInputs(config.startInput, config.endInput);

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
			onChange: (selectedDates, dateStr, instance) => {
				this.writeSelectionToInputs(config, selectedDates);
				this.capEndDateWhileSelecting(instance, selectedDates, maxRangeDays, absoluteMax);
			},
		});
	},

	render: async function (container, config) {
		await this.loadLibrary();
		if (container._dateRangePicker) {
			container._dateRangePicker.destroy();
		}
		container.replaceChildren();
		config.container = container;
		container._dateRangePicker = this.create(config);
		return container._dateRangePicker;
	},
};
