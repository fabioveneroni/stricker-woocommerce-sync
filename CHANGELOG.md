# Changelog

## 0.4.1
- Added the official Stricker SOAP `ProductsTree` method.
- Added a ProductsTree consultation action in the Products screen.
- ProductsTree response is displayed for analysing the complete product structure before implementing Simple vs Variable classification.
- No WooCommerce products are created or modified by this step.

## 0.4.0
- Added Products catalogue consultation through the official Stricker SOAP `Products` method.
- Added product response inspection before WooCommerce import.

## 0.3.6
- Fixed the ProductTypes parser to use the real Stricker structure: Types > Type > SubTypes > SubType.
- ProductType and SubType codes and descriptions are displayed in a four-column table.

## 0.3.5
- Fixed the `SWS_PLUGIN_DIR` constant used by the SOAP integration.
- Kept `SWS_PLUGIN_DIR` as an alias of `SWS_DIR` for compatibility.

## 0.3.4
- Added the official Stricker SOAP client wrapper for ProductTypes.
- ProductTypes now uses AuthenticateClient, ValidateSession and ProductTypes through SOAP.

## 0.3.3
- ProductTypes uses the documented REST endpoint `/api/v1SSL/productTypes` with `token` and `lang`.
- ProductTypes forces a fresh authentication.
- Improved transport diagnostics.

## 0.3.2
- Fixed ProductTypes catalog retrieval to use Stricker's documented direct JSON download endpoint.

## 0.3.1
- Fixed first-time Access Key persistence and encrypted storage.

## 0.3.0
- Added explicit ProductTypes consultation, success/error notices and raw API diagnostics.

## 0.2.2
- Fixed connection test feedback.

## 0.2.1
- Fixed Access Key persistence/encryption flow.
- Added Categories menu and ProductType consultation.

## 0.2.0
- REST/HTTPS aligned with the Stricker manual.
- Added initial ProductTypes/Categories consultation.
