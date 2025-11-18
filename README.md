# Simple Page Builder

A secure WordPress plugin that provides a REST API endpoint for creating pages in bulk.  
Designed with strong API key authentication, logging, admin controls, and full developer documentation.

---

## Core Features

### 1. Bulk Page Creation via REST API

External applications can create WordPress pages using:

```
POST /wp-json/pagebuilder/v1/create-pages
```

The request must include valid API credentials (API key + secret).

---

### 2. API Key Authentication System

The plugin uses a secure API key + secret pair:

- API keys can be generated in the WordPress admin.
- Secret keys are shown only once.
- API keys are hashed in the database (similar to passwords).
- Keys can be revoked instantly.

API keys include metadata such as:

- Status (Active/Revoked)
- Request Count
- Last Used Timestamp
- Created Date

---

### 3. Admin Interface (Tools → Page Builder)

The admin panel includes:

#### API Keys Management

- Generate new API keys (name + generated key/secret)
- View list of existing keys
- Preview API keys (first 8 chars + \*\*\*)
- Revoke keys
- Track usage statistics

#### API Activity Log

Shows recent API requests including:

- Timestamp
- API Key Used
- Endpoint
- Status (Success/Failed)
- Pages Created
- IP Address

#### Created Pages List

Displays pages created via the API:

- Page Title
- Clickable URL
- Creation Date
- API Key Name that created it

#### Plugin Settings

- Rate limit (requests per hour per key)
- Ability to enable/disable API access

#### API Documentation

Includes:

- Authentication details
- Example request
- Explanation of headers

---

## Authentication

All API requests must include these headers:

```
X-SPB-API-Key: YOUR_API_KEY
X-SPB-API-Secret: YOUR_SECRET
```

If authentication fails, the API returns:

```
401 Unauthorized
```

Keys are securely hashed and logged.

---

## API Usage

### Endpoint

```
POST /wp-json/pagebuilder/v1/create-pages
Content-Type: application/json
X-SPB-API-Key: your_key
X-SPB-API-Secret: your_secret
```

### Request Body Example

```json
{
  "pages": [
    {
      "title": "About Us",
      "content": "<p>This is the about page.</p>"
    },
    {
      "title": "Contact",
      "content": "<p>Contact us at info@example.com</p>"
    }
  ]
}
```

### Successful Response Example

```json
{
  "status": "success",
  "created": 2,
  "pages": [
    { "id": 123, "title": "About Us" },
    { "id": 124, "title": "Contact" }
  ]
}
```

---

## Database Tables

The plugin creates three custom tables:

### 1. `wp_spb_api_keys`

Stores API keys (hashed), metadata, and status.

### 2. `wp_spb_api_logs`

Stores every API request with full details.

### 3. `wp_spb_page_logs`

Stores information about pages created via the API.

---

## Installation

Clone the repository into your `wp-content/plugins` directory:

```
git clone https://github.com/NaderMakram/simple-page-builder.git
```

Then:

1. Activate the plugin from **WordPress Admin → Plugins**
2. Navigate to **Tools → Page Builder**
3. Generate API keys and begin using the API

---

## Logging & Security

The plugin automatically logs:

- Successful API requests
- Failed authentication attempts
- Page creation events
- IP address of the requester
- Total usage count per API key

The system prevents:

- Invalid or revoked keys
- Exceeding rate limits
- Requests when API access is disabled
