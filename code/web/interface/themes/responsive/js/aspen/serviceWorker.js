// TODO currently not accessed but leaving for reference for next phase
//not bundled because we only want to include this if Aspen Progressive Web Application(PWA) is turned on
//importScripts("https://www.gstatic.com/firebasejs/12.1.0/firebase-messaging-sw.js");
importScripts('https://www.gstatic.com/firebasejs/12.1.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/12.1.0/firebase-messaging-compat.js');
const CACHE_NAME = 'aspen-mobile';

const PRECACHE_ASSETS = [

];

self.addEventListener('install', event => {
	event.waitUntil((async () => {
		const cache = await caches.open(CACHE_NAME);
		cache.addAll(PRECACHE_ASSETS);
	})());
});

self.addEventListener('activate', event => {
	event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', event => {
	event.respondWith(async () => {
		const cache = await caches.open(CACHE_NAME);

		// match the request to our cache
		const cachedResponse = await cache.match(event.request);

		// check if we got a valid response
		if (cachedResponse !== undefined) {
			// Cache hit, return the resource
			return cachedResponse;
		}
		// Otherwise, go to the network
		return fetch(event.request);
	});
});

self.addEventListener('push', (event) => {
	event_json = event.data.json();
	notification = event_json.notification;
	path = event_json.data?.path;
	event.waitUntil(
		self.registration.showNotification(notification.title, {
			body: notification.body,
			data: {
				"path": path
			},
			icon: notification.image,
		})
	);
});

self.addEventListener('notificationclick', (event) => {
	event.notification.close();
	var fullPath = self.location.origin;
	if(event.notification.data.path)
	{
		fullPath += event.notification.data.path;
	} 
	clients.openWindow(fullPath);
});
