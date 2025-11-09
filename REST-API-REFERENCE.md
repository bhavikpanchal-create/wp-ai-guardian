# 🌐 WP AI Guardian REST API Reference

## Base URL
```
https://your-site.com/wp-json/wpaig/v1
```

---

## 🔓 Public Endpoints

### Health Check
```http
GET /health
```

**Response:**
```json
{
  "status": "ok",
  "version": "1.0",
  "timestamp": "2025-11-09 01:30:00"
}
```

**Example:**
```bash
curl https://your-site.com/wp-json/wpaig/v1/health
```

---

## 🔒 Protected Endpoints (Admin Only)

All endpoints below require admin authentication via WordPress REST API.

### Authentication Methods

**1. Cookie Authentication (Browser):**
```javascript
// Automatic when logged in to WordPress
fetch(wpaigData.restUrl + '/usage', {
    headers: {
        'X-WP-Nonce': wpaigData.restNonce
    }
})
```

**2. Application Passwords:**
```bash
curl -u username:app_password \
  https://your-site.com/wp-json/wpaig/v1/usage
```

**3. JWT Token (requires plugin):**
```bash
curl -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  https://your-site.com/wp-json/wpaig/v1/usage
```

---

## 📊 Scan Endpoints

### Run Scan
```http
POST /scan
```

**Response:**
```json
{
  "success": true,
  "results": [
    {
      "issue": "Outdated plugin detected",
      "severity": "high",
      "category": "security"
    }
  ],
  "timestamp": "2025-11-09 01:30:00"
}
```

**Example:**
```bash
curl -X POST https://your-site.com/wp-json/wpaig/v1/scan \
  -u username:app_password
```

---

## ⚡ Performance Endpoints

### Optimize Performance
```http
POST /performance
```

**Response:**
```json
{
  "success": true,
  "data": {
    "score": 85,
    "baseline": {
      "page_load_time": 2.5,
      "db_queries": 42
    },
    "optimized": {
      "page_load_time": 1.8,
      "db_queries": 28
    },
    "improvements": {
      "load_time": "28% faster",
      "db_queries": "33% reduction"
    },
    "ai_recommendations": [
      "Enable object caching",
      "Minify CSS and JavaScript"
    ]
  }
}
```

**Example:**
```bash
curl -X POST https://your-site.com/wp-json/wpaig/v1/performance \
  -u username:app_password
```

---

## 📈 SEO Endpoints

### Analyze SEO
```http
POST /seo
```

**Response:**
```json
{
  "success": true,
  "data": {
    "score": 75,
    "checks": {
      "title": true,
      "description": true,
      "permalinks": false
    },
    "issues": [
      {
        "type": "permalinks",
        "severity": "medium",
        "message": "Consider using SEO-friendly permalinks"
      }
    ]
  }
}
```

**Example:**
```bash
curl -X POST https://your-site.com/wp-json/wpaig/v1/seo \
  -u username:app_password
```

---

## 🔄 Workflow Endpoints

### Get All Workflows
```http
GET /workflows
```

**Response:**
```json
{
  "success": true,
  "data": {
    "1234567890": {
      "id": 1234567890,
      "name": "Auto SEO Optimize",
      "trigger": "post_published",
      "action": "ai_seo_optimize",
      "active": true
    }
  }
}
```

**Example:**
```bash
curl https://your-site.com/wp-json/wpaig/v1/workflows \
  -u username:app_password
```

---

### Create/Update Workflow
```http
POST /workflows
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "Auto SEO Optimize",
  "trigger": "post_published",
  "action": "ai_seo_optimize",
  "active": true
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1234567890,
    "name": "Auto SEO Optimize",
    "trigger": "post_published",
    "action": "ai_seo_optimize",
    "active": true
  }
}
```

**Example:**
```bash
curl -X POST https://your-site.com/wp-json/wpaig/v1/workflows \
  -u username:app_password \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Auto SEO Optimize",
    "trigger": "post_published",
    "action": "ai_seo_optimize",
    "active": true
  }'
```

---

### Delete Workflow
```http
DELETE /workflows/{id}
```

**Response:**
```json
{
  "success": true,
  "message": "Workflow deleted"
}
```

**Example:**
```bash
curl -X DELETE https://your-site.com/wp-json/wpaig/v1/workflows/1234567890 \
  -u username:app_password
```

---

## 🔑 License Endpoints

### Get License Info
```http
GET /license
```

**Response:**
```json
{
  "success": true,
  "data": {
    "is_premium": true,
    "license": {
      "key": "WPAIG-****-****-****",
      "status": "valid",
      "expires": "2026-11-09",
      "checked": "2025-11-09 01:00:00"
    }
  }
}
```

**Example:**
```bash
curl https://your-site.com/wp-json/wpaig/v1/license \
  -u username:app_password
```

---

### Activate License
```http
POST /license/activate
Content-Type: application/json
```

**Request Body:**
```json
{
  "license_key": "WPAIG-DEMO-KEY"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Demo license activated successfully! All premium features unlocked.",
  "expires": "2026-11-09"
}
```

**Example:**
```bash
curl -X POST https://your-site.com/wp-json/wpaig/v1/license/activate \
  -u username:app_password \
  -H "Content-Type: application/json" \
  -d '{"license_key": "WPAIG-DEMO-KEY"}'
```

---

### Deactivate License
```http
POST /license/deactivate
```

**Response:**
```json
{
  "success": true,
  "message": "License deactivated successfully"
}
```

**Example:**
```bash
curl -X POST https://your-site.com/wp-json/wpaig/v1/license/deactivate \
  -u username:app_password
```

---

## 📊 Usage Endpoints

### Get Usage Stats
```http
GET /usage
```

**Response:**
```json
{
  "success": true,
  "data": {
    "is_premium": false,
    "usage": {
      "ai_calls": {
        "current": 25,
        "limit": 50,
        "period": "month"
      },
      "workflows": {
        "current": 2,
        "limit": 2,
        "period": "total"
      },
      "images": {
        "current": 10,
        "limit": 20,
        "period": "month"
      },
      "seo": {
        "current": 15,
        "limit": 30,
        "period": "month"
      },
      "scans": {
        "current": 3,
        "limit": 5,
        "period": "day"
      }
    },
    "limits": {
      "ai_calls": 50,
      "workflows": 2,
      "images_optimized": 20,
      "seo_optimizations": 30,
      "scans_per_day": 5
    }
  }
}
```

**Example:**
```bash
curl https://your-site.com/wp-json/wpaig/v1/usage \
  -u username:app_password
```

---

## 🔥 JavaScript Examples

### Using Fetch API

```javascript
// Health check (no auth)
fetch('https://your-site.com/wp-json/wpaig/v1/health')
  .then(res => res.json())
  .then(data => console.log(data));

// Get usage (with nonce - in WP admin)
fetch(wpaigData.restUrl + '/usage', {
  headers: {
    'X-WP-Nonce': wpaigData.restNonce
  }
})
  .then(res => res.json())
  .then(data => console.log(data));

// Activate license
fetch(wpaigData.restUrl + '/license/activate', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-WP-Nonce': wpaigData.restNonce
  },
  body: JSON.stringify({
    license_key: 'WPAIG-DEMO-KEY'
  })
})
  .then(res => res.json())
  .then(data => console.log(data));
```

---

### Using Axios

```javascript
// Install: npm install axios
import axios from 'axios';

// Health check
const health = await axios.get('https://your-site.com/wp-json/wpaig/v1/health');
console.log(health.data);

// Get usage (with auth)
const usage = await axios.get('https://your-site.com/wp-json/wpaig/v1/usage', {
  headers: {
    'X-WP-Nonce': wpaigData.restNonce
  }
});
console.log(usage.data);

// Activate license
const activation = await axios.post(
  'https://your-site.com/wp-json/wpaig/v1/license/activate',
  { license_key: 'WPAIG-DEMO-KEY' },
  {
    headers: {
      'X-WP-Nonce': wpaigData.restNonce
    }
  }
);
console.log(activation.data);
```

---

## 🐍 Python Example

```python
import requests
from requests.auth import HTTPBasicAuth

# Setup
base_url = 'https://your-site.com/wp-json/wpaig/v1'
auth = HTTPBasicAuth('username', 'app_password')

# Health check (no auth)
health = requests.get(f'{base_url}/health')
print(health.json())

# Get usage
usage = requests.get(f'{base_url}/usage', auth=auth)
print(usage.json())

# Activate license
activation = requests.post(
    f'{base_url}/license/activate',
    json={'license_key': 'WPAIG-DEMO-KEY'},
    auth=auth
)
print(activation.json())

# Run scan
scan = requests.post(f'{base_url}/scan', auth=auth)
print(scan.json())
```

---

## 🚀 Node.js Example

```javascript
// Install: npm install node-fetch
const fetch = require('node-fetch');

const baseUrl = 'https://your-site.com/wp-json/wpaig/v1';
const auth = Buffer.from('username:app_password').toString('base64');

// Health check
const health = await fetch(`${baseUrl}/health`);
console.log(await health.json());

// Get usage
const usage = await fetch(`${baseUrl}/usage`, {
  headers: {
    'Authorization': `Basic ${auth}`
  }
});
console.log(await usage.json());

// Activate license
const activation = await fetch(`${baseUrl}/license/activate`, {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Basic ${auth}`
  },
  body: JSON.stringify({
    license_key: 'WPAIG-DEMO-KEY'
  })
});
console.log(await activation.json());
```

---

## ⚠️ Error Responses

### 400 Bad Request
```json
{
  "success": false,
  "message": "Missing required fields"
}
```

### 401 Unauthorized
```json
{
  "code": "rest_forbidden",
  "message": "Sorry, you are not allowed to do that.",
  "data": {
    "status": 401
  }
}
```

### 404 Not Found
```json
{
  "success": false,
  "message": "Workflow not found"
}
```

### 500 Internal Server Error
```json
{
  "success": false,
  "message": "Optimization failed"
}
```

---

## 🛡️ Security Best Practices

### 1. Use HTTPS
```
✅ https://your-site.com/wp-json/wpaig/v1/...
❌ http://your-site.com/wp-json/wpaig/v1/...
```

### 2. Application Passwords
```
WP Admin → Users → Your Profile → Application Passwords
→ Create new password for API access
```

### 3. Rate Limiting
```php
// Add to functions.php or plugin
add_filter('rest_request_before_callbacks', function($response, $handler, $request) {
    if (strpos($request->get_route(), 'wpaig/v1') !== false) {
        // Implement rate limiting
        // Return 429 if too many requests
    }
    return $response;
}, 10, 3);
```

### 4. IP Whitelisting
```php
// In .htaccess or Nginx config
# Allow only specific IPs to access REST API
<If "%{REQUEST_URI} =~ m#^/wp-json/wpaig/v1/#">
    Require ip 192.168.1.100
    Require ip 10.0.0.0/8
</If>
```

---

## 📖 Quick Reference

| Endpoint | Method | Auth | Purpose |
|----------|--------|------|---------|
| `/health` | GET | ❌ | Health check |
| `/scan` | POST | ✅ | Run scan |
| `/performance` | POST | ✅ | Optimize |
| `/seo` | POST | ✅ | Analyze SEO |
| `/workflows` | GET | ✅ | List workflows |
| `/workflows` | POST | ✅ | Save workflow |
| `/workflows/{id}` | DELETE | ✅ | Delete workflow |
| `/license` | GET | ✅ | Get license |
| `/license/activate` | POST | ✅ | Activate |
| `/license/deactivate` | POST | ✅ | Deactivate |
| `/usage` | GET | ✅ | Get usage |

---

## 🎯 Testing

### Postman Collection
```json
{
  "info": {
    "name": "WP AI Guardian API",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "auth": {
    "type": "basic",
    "basic": [
      {"key": "username", "value": "{{username}}"},
      {"key": "password", "value": "{{app_password}}"}
    ]
  },
  "variable": [
    {"key": "base_url", "value": "https://your-site.com/wp-json/wpaig/v1"}
  ]
}
```

### cURL Test Script
```bash
#!/bin/bash
BASE_URL="https://your-site.com/wp-json/wpaig/v1"
AUTH="username:app_password"

echo "Testing health..."
curl -s "$BASE_URL/health" | jq

echo "Testing usage..."
curl -s -u "$AUTH" "$BASE_URL/usage" | jq

echo "Testing license activation..."
curl -s -X POST -u "$AUTH" \
  -H "Content-Type: application/json" \
  -d '{"license_key":"WPAIG-TEST"}' \
  "$BASE_URL/license/activate" | jq
```

---

**🌐 Your REST API is ready for integration!**

Use these endpoints to build mobile apps, integrations, or external dashboards! 🚀
