=== Stricker WooCommerce Sync ===
Conecta o catálogo da Stricker ao WooCommerce.

Contributors: openai
Stable tag: 0.3.0
Requires at least: 6.0
Requires PHP: 7.4
License: GPLv2 or later

== Changelog ==
= 0.3.0 =
* Added an explicit ProductTypes consultation action instead of querying the API automatically on page load.
* Added a success/error notice after the ProductTypes request.
* Added ProductType ID detection and a structured ID/name/subtype table.
* Added raw API response in a collapsible diagnostic section for mapping the real Stricker response safely.
* Added short-lived server-side caching of the last ProductTypes response per administrator session.
* Version bumped from 0.2.2 to 0.3.0.

= 0.2.2 =
* Fixed connection test feedback: success and error messages are now stored server-side and displayed reliably after redirect.
* Connection test now reports transport, HTTP, JSON and authentication failures with actionable messages.
* Success is shown only when a valid session token is returned by the Stricker API.
* Version bumped from 0.2.1 to 0.2.2.

= 0.2.1 =
* Fixed Access Key persistence/encryption flow.
* Added Categories menu and product type consultation.

= 0.2.0 =
* REST/HTTPS aligned with the Stricker manual.
* Configured the default HTTPS REST endpoint.
* Added initial ProductTypes/Categories consultation.
