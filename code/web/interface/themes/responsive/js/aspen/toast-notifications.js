AspenDiscovery.ToastNotifications = (function () {
  const debug = false;
  const POLL_INTERVAL = 10000; // 10 seconds
  let pollTimer = null;

  return {
    /**
     * Start polling for toast notifications
     */
    startPolling: function (args) {
      const fetchNotifications = () => {
        fetch(args.endpoint)
          .then((response) => response.json())
          .then((data) => {
			if (data.status === "stop") {
				clearInterval(pollTimer);
				return;
			}
            if (data.status === "success" && data.notifications) {
              data.notifications.forEach((notification) => {
                this.processNotification(notification);
              });
            }
          })
          .catch((err) => console.error("Polling error:", err));
      };
      fetchNotifications();
      pollTimer = setInterval(fetchNotifications, POLL_INTERVAL);
    },

    processNotification: function (notification) {
      const toastDataArray =
        JSON.parse(sessionStorage.getItem("toastDataArray")) || [];

      const alreadyShown = toastDataArray.some((n) => n.id === notification.id);
      if (!alreadyShown || debug) {
        toastDataArray.push(notification);
        if (toastDataArray.length > 20) toastDataArray.shift();

        sessionStorage.setItem(
          "toastDataArray",
          JSON.stringify(toastDataArray)
        );
        this.showToast(notification);
      }
    },

	showToast: function(toastData) {
		const toast = document.createElement('div');
		toast.id = toastData.id;
		toast.className = 'toast-notification';
		toast.innerHTML = `
			<div class="toast-column-left">
				<i class="fas ${toastData.icon} toast-notification-icon"></i>
			</div>
			<div class="toast-column-right">
				<p class="toast-message-title">${toastData.title}</p>
				<p class="toast-message-subtitle">${toastData.body}</p>
				${toastData.link ? `<a class="toast-message-link" href="${toastData.link.href}">${toastData.link.text}</a>` : ''}
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
  };
})();