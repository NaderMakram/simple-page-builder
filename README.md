# Simple Page Builder

**Contributors:** (your-wordpress-org-username)  
**Tags:** rest-api, pages, bulk create, automation, page builder  
**Requires at least:** 5.6  
**Tested up to:** 6.4  
**Stable tag:** 1.0.0  
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

A powerful plugin for developers and administrators to automatically create pages in bulk via a secure, dedicated REST API endpoint with advanced authentication.

## Description

**Simple Page Builder** provides a secure and efficient way to programmatically create WordPress pages from external applications. It exposes a custom REST API endpoint that allows you to create single or multiple pages in one go, complete with titles, content, custom fields, and more.

This plugin is perfect for:

- **Automated Content Workflows:** Automatically generate pages from a script, a third-party service, or a headless CMS.
- **Bulk Site Setup:** Quickly populate a new site with a predefined set of pages.
- **Integrating with External Data Sources:** Create pages based on data from CRMs, ERPs, or other external systems.

The plugin features an advanced authentication mechanism (e.g., API Key and Secret) to ensure that your site is protected and that only authorized applications can create content.

## Installation

1.  Upload the `simple-page-builder` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Navigate to **Settings > Simple Page Builder** to configure the plugin and generate your API credentials.

## How to Use

Once the plugin is activated and configured, you can start creating pages by sending `POST` requests to the custom REST API endpoint.

### Authentication

The plugin uses a custom authentication method to secure the endpoint. You must include your API Key and a generated signature in the request headers.

- **API Key:** Your public API key.
- **Signature:** A `SHA256` hash of the request body, concatenated with your API Secret.

_(Note: This is an example. Please update with your actual authentication mechanism, such as Bearer tokens, JWT, etc.)_

### API Endpoint

- **URL:** `https://yourdomain.com/wp-json/spb/v1/pages`
- **Method:** `POST`

### Parameters

The request body should be a JSON object containing an array of `pages`. Each page object can have the following properties:

- `post_title` (string, required): The title of the page.
- `post_content` (string, optional): The full content for the page. Default is empty.
- `post_status` (string, optional): The page status. Accepts `publish`, `pending`, `draft`, `private`. Default is `draft`.
- `post_parent` (integer, optional): The ID of the parent page. Default is `0`.
- `page_template` (string, optional): The filename of the page template to use (e.g., `template-full-width.php`). Default is `default`.
- `meta_input` (object, optional): An object of key/value pairs to be added as post meta.

### Example Request

Here is an example using `cURL` to create two pages at once.

```bash
#!/bin/bash

API_KEY="your_api_key"
API_SECRET="your_api_secret"
REQUEST_BODY='{"pages":[{"post_title":"About Our Company","post_content":"This is the about page.","post_status":"publish"},{"post_title":"Contact Us","post_content":"This is the contact page.","post_status":"publish","meta_input":{"contact_email":"contact@example.com"}}]}'
SIGNATURE=$(echo -n "$REQUEST_BODY$API_SECRET" | sha256sum | awk '{print $1}')

curl -X POST "https://yourdomain.com/wp-json/spb/v1/pages" \
-H "Content-Type: application/json" \
-H "X-SPB-API-Key: $API_KEY" \
-H "X-SPB-Signature: $SIGNATURE" \
-d "$REQUEST_BODY"
```

### Example Response

A successful request will return a `201 Created` status and a JSON object with the results.

```json
{
  "success": true,
  "message": "2 pages processed.",
  "results": [
    {
      "status": "success",
      "page_id": 123,
      "post_title": "About Our Company"
    },
    {
      "status": "success",
      "page_id": 124,
      "post_title": "Contact Us"
    }
  ]
}
```

## Frequently Asked Questions

**Q: Where do I find my API Key and Secret?**  
A: After activating the plugin, navigate to **Settings > Simple Page Builder** in your WordPress admin dashboard.

**Q: Can I create posts or other custom post types?**  
A: This version only supports creating pages. Future versions may include support for other post types.

## Changelog

### 1.0.0

- Initial release.
- Secure REST API endpoint for bulk page creation.
- API Key and Secret authentication.
