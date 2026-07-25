/*
 * Reusable inline date-range picker, wrapping flatpickr in "range" mode.
 *
 * Usage:
 *   AspenDiscovery.DateRangePicker.render(wrapper, config)
 * renders a monthly calendar inside `wrapper` (any block-level element) and
 * lazy-loads the flatpickr library + stylesheets from a CDN on first use.
 *
 * config (all optional):
 *   startInput / endInput                the SUBMITTED fields. Always written in
 *                                        ISO (Y-m-d) — this is the wire format the
 *                                        server/Koha parses. Also the source the
 *                                        initial range is read back from.
 *   startDisplayInput / endDisplayInput  the VISIBLE, human-facing fields. Rendered
 *                                        per displayLocale and never submitted.
 *                                        Omit them for a headless / ISO-only setup.
 *   displayLocale                        BCP-47 locale (e.g. 'en-GB') the display
 *                                        fields are rendered with, via Intl (medium
 *                                        style). Omit for ISO. Only touches the
 *                                        display fields, so it can never reach the
 *                                        submitted value.
 *   initialRange                         [start, end] (ISO) to preselect; falls
 *                                        back to the values in startInput/endInput.
 *   minDate / maxDate                    selectable window; minDate defaults to today.
 *   disabledRanges                       [{start, end}, ...] (Y-m-d) that cannot be selected.
 *   maxRangeDays                         cap on the length of a single selection.
 *
 * Limitations:
 *   - Client-side only. disabledRanges and maxRangeDays are UX guardrails, not
 *     access control: anyone can bypass them by editing the page or calling the
 *     endpoint directly. The PHP handling the booking request is the real
 *     authority and must re-validate availability and range limits server-side.
 *   - Depends on the flatpickr CDN being reachable; render() rejects if it fails
 *     to load, and callers are expected to surface that to the user.
 *   - One picker instance per wrapper; re-rendering destroys the previous one.
 */
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

	writeSelectionToInputs: function (config, selectedDates, formatDisplay) {
		const write = (input, value) => {
			if (input) {
				input.value = value;
			}
		};
		// Submitted fields always carry ISO — the wire contract. Display fields carry
		// the locale-facing rendering and are never sent to the server.
		[config.startInput, config.endInput].forEach((input, i) =>
			write(input, selectedDates[i] ? flatpickr.formatDate(selectedDates[i], 'Y-m-d') : ''));
		[config.startDisplayInput, config.endDisplayInput].forEach((input, i) =>
			write(input, selectedDates[i] ? formatDisplay(selectedDates[i]) : ''));
	},

	displayFormatterFor: function (displayLocale) {
		if (!displayLocale) {
			return date => flatpickr.formatDate(date, 'Y-m-d');
		}
		const formatter = new Intl.DateTimeFormat(displayLocale, { dateStyle: 'medium' });
		return date => formatter.format(date);
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

	create: function (wrapper, config) {
		const disabledRanges = config.disabledRanges || [];
		const maxRangeDays = config.maxRangeDays || 0;
		const absoluteMax = config.maxDate || null;
		const formatDisplay = this.displayFormatterFor(config.displayLocale);
		const initialRange = config.initialRange || this.readRangeFromInputs(config.startInput, config.endInput);

		function isWithinDisabledRange(date) {
			const iso = flatpickr.formatDate(date, 'Y-m-d');
			return disabledRanges.some(function (range) {
				return iso >= range.start && iso <= range.end;
			});
		}

		wrapper.classList.add('date-range-picker');
		const anchor = document.createElement('input');
		anchor.type = 'hidden';
		wrapper.appendChild(anchor);

		return flatpickr(anchor, {
			mode: 'range',
			inline: true,
			dateFormat: 'Y-m-d',
			minDate: config.minDate || 'today',
			maxDate: absoluteMax || undefined,
			defaultDate: initialRange.length === 2 ? initialRange : undefined,
			disable: [isWithinDisabledRange],
			onDayCreate: (selectedDates, dateStr, instance, dayElem) => {
				if (isWithinDisabledRange(dayElem.dateObj)) {
					dayElem.classList.add('unavailable');
				}
			},
			onChange: (selectedDates, dateStr, instance) => {
				this.writeSelectionToInputs(config, selectedDates, formatDisplay);
				this.capEndDateWhileSelecting(instance, selectedDates, maxRangeDays, absoluteMax);
			},
		});
	},

	render: async function (wrapper, config) {
		await this.loadLibrary();
		if (wrapper._dateRangePicker) {
			wrapper._dateRangePicker.destroy();
		}
		wrapper.replaceChildren();
		wrapper._dateRangePicker = this.create(wrapper, config);
		return wrapper._dateRangePicker;
	},
};
