# Changelog

## 0.3.1
- Fixed Access Key persistence when the setting does not yet exist in the WordPress options table.
- Access Key is now encrypted during Settings API sanitisation, avoiding the `update_option` hook edge case on first save.
- ProductTypes requests now use the same persisted encrypted credential used by the connection test.
- Version bumped from 0.3.0 to 0.3.1.

## 0.3.0
- Added an explicit ProductTypes consultation action instead of querying the API automatically on page load.
- Added success/error notice after the ProductTypes request.
- Added ProductType ID detection and a structured ID/name/subtype table.
- Added raw API response in a collapsible diagnostic section.

## 0.2.2
- Fixed connection test feedback.

## 0.2.1
- Fixed Access Key persistence/encryption flow.
- Added Categories menu and ProductType consultation.

## 0.2.0
- REST/HTTPS aligned with the Stricker manual.
- Configured the default HTTPS REST endpoint.
- Added initial ProductTypes/Categories consultation.
