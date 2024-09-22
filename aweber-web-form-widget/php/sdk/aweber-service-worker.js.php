<?php
	// Set the Response Header, so the scope can be initialized to the root. 
    header("Service-Worker-Allowed: /");
    // Says the content is javascript to the browser
    header("Content-Type: application/javascript");
    // Prevents this file from indexing
    header("X-Robots-Tag: none");
    // Fetch or Import the aweber service-worker.js and display it 
    echo "importScripts('https://assets.aweber-static.com/wpn/service-worker.js');";
