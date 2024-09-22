function registerServiceWorker(aweber_worker) {
	navigator.serviceWorker.register(aweber_worker, {scope: "/"}).then(function(registration) {
		console.log('AWeber ServiceWorker registration successful with scope: ', registration.scope);
	}, function(err) {
		console.log('AWeber ServiceWorker registration failed: ', err);
	});
}

function unregisterServiceWorker(aweber_worker) {
	navigator.serviceWorker.getRegistrations().then(function(registrations){
		for (let worker in registrations) {
			if (registrations[worker].active.scriptURL == aweber_worker) {
				console.log('Found AWeber Service Worker. ' + aweber_worker);
				registrations[worker].unregister().then(function(status){
					if (status) {
						console.log('AWeber Service Worker unregister successfully!.');
					} else {
						console.log('Failed to unregister the AWeber Service Worker');
					}
				});
			}
		}
	});
}

function updateServiceWorker() {
	if ('serviceWorker' in navigator) {
		let aweber_worker = aweber_wpn_vars.plugin_base_path + 'sdk/aweber-service-worker.js.php';
		if (aweber_wpn_vars.register_aweber_service_worker == 1) {
			// Lets do regsiter and unregsiter the WPN here.
			registerServiceWorker(aweber_worker);
		} else {
			// Unregister the AWeber Service Worker.
			unregisterServiceWorker(aweber_worker);
		}
	} else {
		console.log('Service Worker not found in the navigator!. Failed to register AWeber Service Worker');
	}
}

// Update the aweber service worker.
updateServiceWorker();