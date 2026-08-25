/*
 * Reusable inline date-range picker on the Cally web components. The shell is
 * authored in Record/date-range-picker.tpl; this module lazy-loads Cally and
 * drives the per-selection state. Cally was chosen over flatpickr for accessibility:
 * an ARIA grid with roving tabindex, fully keyboard operable, screen-reader friendly
 *
 * Usage:
 *   const picker = await AspenDiscovery.DateRangePicker.init(rangeEl, config);
 *   AspenDiscovery.DateRangePicker.update(picker, window);   // per selection
 * init() wires the element once and reads its `locale` attribute; update()
 * mutates the selectable window in place — no teardown/re-render.
 *
 * init(config):   startInput / endInput          submitted, always ISO (Y-m-d)
 *                 start/endDisplayInput          visible, locale-formatted, never sent
 *                 minDate                        earliest selectable (Date|ISO); default today
 * update(window): maxDate                        latest selectable (Date|ISO)
 *                 disabledRanges                 [{start, end}] (Y-m-d), unavailable
 *                 maxRangeDays                   cap on a single selection's length
 *
 * disabledRanges / maxRangeDays are client-side UX guardrails only — the server
 * must re-validate. maxRangeDays is a commit-time backstop, not a live cap.
 * init() rejects if the Cally CDN is unreachable; callers surface that.
 */
AspenDiscovery.DateRangePicker = {
	CALLY_JS_URL: 'https://unpkg.com/cally',

	_libraryPromise: null,

	loadScript: function (url) {
		return new Promise(function (resolve, reject) {
			const script = document.createElement('script');
			script.src = url;
			script.type = 'module';
			script.onload = resolve;
			script.onerror = reject;
			document.head.appendChild(script);
		});
	},

	loadLibrary: async function () {
		if (!customElements.get('calendar-range')) {
			if (!this._libraryPromise) {
				this._libraryPromise = this.loadScript(this.CALLY_JS_URL);
			}
			await this._libraryPromise;
		}
		await customElements.whenDefined('calendar-range');
		await customElements.whenDefined('calendar-month');
	},

	toIsoDate: function (value) {
		if (!value) {
			return '';
		}
		const date = value instanceof Date ? value : new Date(value);
		if (Number.isNaN(date.getTime())) {
			return '';
		}
		const month = String(date.getMonth() + 1).padStart(2, '0');
		const day = String(date.getDate()).padStart(2, '0');
		return date.getFullYear() + '-' + month + '-' + day;
	},

	parseIsoDate: function (iso) {
		return new Date(iso + 'T00:00:00');
	},

	readRangeFromInputs: function (startInput, endInput) {
		return [startInput, endInput].filter(input => input && input.value).map(input => input.value);
	},

	displayFormatterFor: function (displayLocale) {
		if (!displayLocale) {
			return date => this.toIsoDate(date);
		}
		const formatter = new Intl.DateTimeFormat(displayLocale, { dateStyle: 'medium' });
		return date => formatter.format(date);
	},

	writeSelectionToInputs: function (config, startIso, endIso, formatDisplay) {
		const write = (input, value) => {
			if (input) {
				input.value = value;
			}
		};
		// Submitted fields always carry ISO — the wire contract. Display fields carry
		// the locale-facing rendering and are never sent to the server.
		write(config.startInput, startIso || '');
		write(config.endInput, endIso || '');
		write(config.startDisplayInput, startIso ? formatDisplay(this.parseIsoDate(startIso)) : '');
		write(config.endDisplayInput, endIso ? formatDisplay(this.parseIsoDate(endIso)) : '');
	},

	clampRangeEnd: function (startIso, endIso, maxRangeDays) {
		if (maxRangeDays <= 0 || !startIso || !endIso) {
			return endIso;
		}
		const start = this.parseIsoDate(startIso);
		const spanDays = Math.round((this.parseIsoDate(endIso) - start) / 86400000);
		if (spanDays <= maxRangeDays) {
			return endIso;
		}
		const capped = new Date(start.getTime());
		capped.setDate(capped.getDate() + maxRangeDays);
		return this.toIsoDate(capped);
	},

	init: async function (range, config) {
		await this.loadLibrary();

		const root = range.closest('.date-range-picker');
		const startInput = root.querySelector('[data-date-role="start-value"]');
		const endInput = root.querySelector('[data-date-role="end-value"]');
		range._dateRangeConfig = {
			startInput: startInput,
			endInput: endInput,
			startDisplayInput: root.querySelector('[data-date-role="start"]'),
			endDisplayInput: root.querySelector('[data-date-role="end"]'),
			formatDisplay: this.displayFormatterFor(range.getAttribute('locale')),
			disabledRanges: [],
			maxRangeDays: 0,
		};

		range.setAttribute('min', this.toIsoDate(config.minDate) || this.toIsoDate(new Date()));
		range.isDateDisallowed = () => false;

		const initialRange = this.readRangeFromInputs(startInput, endInput);
		if (initialRange.length === 2) {
			range.value = initialRange.join('/');
		}

		range.addEventListener('change', () => this.onChange(range));
		return range;
	},

	clear: function (range) {
		const config = range._dateRangeConfig;
		if (!config) {
			return;
		}
		range.value = '';
		[config.startInput, config.endInput, config.startDisplayInput, config.endDisplayInput]
			.forEach(input => input && (input.value = ''));
	},

	update: function (range, availability) {
		const config = range._dateRangeConfig;
		if (!config) {
			return;
		}
		const disabledRanges = availability.disabledRanges || [];
		config.disabledRanges = disabledRanges;
		config.maxRangeDays = availability.maxRangeDays || 0;

		// Reassigning the property re-runs Cally's disable check and re-renders.
		range.isDateDisallowed = (date) => {
			const iso = this.toIsoDate(date);
			return disabledRanges.some(disabled => iso >= disabled.start && iso <= disabled.end);
		};

		if (availability.maxDate) {
			range.setAttribute('max', this.toIsoDate(availability.maxDate));
		} else {
			range.removeAttribute('max');
		}
	},

	onChange: function (range) {
		const config = range._dateRangeConfig;
		const [startIso, rawEndIso] = range.value ? range.value.split('/') : [];
		const endIso = this.clampRangeEnd(startIso, rawEndIso, config.maxRangeDays);
		if (endIso !== rawEndIso) {
			range.value = startIso + '/' + endIso;
		}
		this.writeSelectionToInputs(config, startIso, endIso, config.formatDisplay);
	},
};
