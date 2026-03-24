AspenDiscovery.ToastNotifications = function() {
	const debug = false; // Set to true to enable debug mode. true for dev only.
	return {

		/**
		 * Listen to SSE (Server sent events)
		 *
		 * @param {String} args.eventSource
		 * @param {String} args.eventName
		 * @returns {boolean}
		 */

		listenToSSE: function(args) {
			const eventSource = new EventSource(args.eventSource);

			const closeEventSource = () => {
				eventSource.close();
			};
			window.addEventListener('beforeunload', closeEventSource);

			eventSource.addEventListener(args.eventName, e => {
				const toastDataArray = JSON.parse(sessionStorage.getItem('toastDataArray')) || [];
				const notificationAlreadyShown = toastDataArray.some(notification => notification.id === JSON.parse(e.data).id && notification.type === JSON.parse(e.data).type);
				if (!notificationAlreadyShown || debug) {
					toastDataArray.push(JSON.parse(e.data));
					sessionStorage.setItem('toastDataArray', JSON.stringify(toastDataArray));
					AspenDiscovery.ToastNotifications.showToast(JSON.parse(e.data));
				}
			})

			eventSource.onclose = () => {
				window.removeEventListener('beforeunload', closeEventSource);
			};
		},

		/**
		 * Show the toast notification
		 *
		 * Parameter SSEData must adhere to the following structure:
		 * 
		 * {
		 *      id:    'my html id',
		 *      title: 'toast title',
		 *      body:  'toast body',
		 *      link:  { href: 'url', text: 'link text' },
		 *      icon:  'fa-icon'
		 * }
		 * 
		 * @param {Object} SSEData
		 */

		showToast: function(SSEData) {
			const toast = document.createElement('div');
			toast.id = SSEData.id;
			toast.className = 'toast-notification';
			toast.innerHTML = `
				<div class="toast-column-left">
					<i class="fas ${SSEData.icon} toast-notification-icon"></i>
				</div>
				<div class="toast-column-right">
					<p class="toast-message-title">${SSEData.title}</p>
					<p class="toast-message-subtitle">${SSEData.body}</p>
					${SSEData.link ? `<a class="toast-message-link" href="${SSEData.link.href}">${SSEData.link.text}</a>` : ''}
					<button class="toast-close">×</button>
				</div>
			`;
			const margin_spacing = 8;
			const toast_height = 95;

			let toast_bottom = margin_spacing;
			
			let adjustPosition = false;
			if ($(document).find('.toast-notification').length == 3) {
				$('.toast-notification').first().remove();
				toast_bottom = (toast_height * 2) + (margin_spacing * 3);
				adjustPosition = true;
			}else if ($(document).find('.toast-notification').length == 1) {
				toast_bottom = toast_height + (margin_spacing * 2);
			}else if ($(document).find('.toast-notification').length == 2) {
				toast_bottom = (toast_height * 2) + (margin_spacing * 3);
			}

			if( adjustPosition ){
				let firstToast = $('.toast-notification').first()[0];
				if(firstToast.style){
					firstToast.style.bottom = parseInt(firstToast.style.bottom) - (toast_height + (margin_spacing)) + 'px';
				}
				let secondToast = $('.toast-notification').eq(1)[0];
				if(secondToast.style){
					secondToast.style.bottom = parseInt(secondToast.style.bottom) - (toast_height + (margin_spacing)) + 'px';
				}
			}

			Object.assign(toast.style, {
				bottom: toast_bottom + 'px',
				right: margin_spacing+'px',
				height: toast_height + 'px'
			});
			document.body.appendChild(toast);
			const closeButton = toast.querySelector('.toast-close');
			closeButton.addEventListener('click', () => {
				toast.style.opacity = 0;
				setTimeout(() => {
				toast.remove();
				}, 300);
			});
			setTimeout(() => {
				toast.style.opacity = 1;
			}, 10);
		},
	}
	
}(AspenDiscovery.ToastNotifications || {});
