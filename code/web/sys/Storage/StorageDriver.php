<?php

interface StorageDriver {
	/**
	 * Returns a public-facing URL the caller can redirect a browser to, or an
	 * empty string if this driver cannot serve the key directly and the
	 * caller must proxy the bytes itself via read() instead.
	 * LocalStorageDriver always returns ''. Uploaded files live outside the
	 * Apache docroot and there is no alias serving them directly.
	 * Remote drivers return an absolute URL when configured with a public
	 * base URL, enabling the browser to fetch bytes directly from the
	 * backend instead of round-tripping through the app server.
	 *
	 * @param array $transforms  e.g. ['width' => 400, 'fit' => 'cover']
	 *                           Applied as URL params by drivers that support it.
	 */
	public function url(string $key, array $transforms = []): string;

	/**
	 * Returns the file contents for the given key, or false if not found.
	 * Callers that need a local path for processing (e.g. image resize) should
	 * write the result to a temp file themselves.
	 */
	public function read(string $key): string|false;

	/**
	 * Returns a readable stream resource for the given key, or false if not
	 * found. Callers serving the bytes directly to an HTTP response (e.g.
	 * proxying an image) should use this instead of read() to avoid buffering
	 * the entire file in memory.
	 */
	public function readStream(string $key);

	/**
	 * Stores the file at $tmpPath under $key.
	 * $mimeType is required for remote backends (e.g. S3 Content-Type header).
	 * Returns true on success.
	 */
	public function write(string $key, string $tmpPath, string $mimeType = ''): bool;

	/**
	 * Deletes the file identified by key.
	 * Returns true if the file was deleted, false if it did not exist or
	 * deletion failed.
	 */
	public function delete(string $key): bool;

	/**
	 * Returns true if a file exists for the given key.
	 */
	public function exists(string $key): bool;
}
