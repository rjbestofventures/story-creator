# Provision API

Machine-to-machine endpoint for creating users with a plan. No user session required — authenticated via a static bearer token stored in the server environment.

---

## Authentication

All requests must include the `Authorization` header with the token set in `PROVISION_API_TOKEN` on the server.

```
Authorization: Bearer {PROVISION_API_TOKEN}
```

Returns `401 Unauthorized` if the token is missing or incorrect.

---

## Create User

Creates a new user account, assigns the specified plan, and sends the user a password-setup email.

**`POST /api/provision/user`**

### Request Body

| Field | Type | Required | Description |
|---|---|---|---|
| `name` | string | Yes | Full name of the user |
| `email` | string | Yes | Email address (must be unique) |
| `plan` | string | Yes | Plan slug — see [Plans](#plans) |
| `billing_interval` | string | No | `monthly` or `yearly` — defaults to `monthly` |

### Example Request

```http
POST /api/provision/user
Authorization: Bearer your-secret-token
Content-Type: application/json
Accept: application/json

{
    "name": "Jane Smith",
    "email": "jane@example.com",
    "plan": "basic",
    "billing_interval": "monthly"
}
```

### Success Response — `201 Created`

```json
{
    "user": {
        "id": 42,
        "name": "Jane Smith",
        "email": "jane@example.com"
    },
    "subscription": {
        "plan": "basic",
        "status": "active",
        "expires_at": "2026-07-19T00:00:00+00:00"
    }
}
```

> `expires_at` is `null` for free plans (no expiry).

### Error Responses

| Status | Cause |
|---|---|
| `401` | Missing or invalid bearer token |
| `422` | Validation failed (see `errors` key in response) |

**Example 422 response:**
```json
{
    "message": "The email has already been taken.",
    "errors": {
        "email": ["The email has already been taken."]
    }
}
```

---

## Plans

| Slug | Price | Stories / mo | Refines / mo | Episode limit | Notes |
|---|---|---|---|---|---|
| `free` | $0 | 0 | 0 | 12 | — |
| `partner` | $0 | 2 | 12 | 12 | 6-month trial |
| `basic` | $10 / mo | 2 | 36 | 12 | — |
| `premium` | $15 / mo | 2 | 54 | 18 | — |
| `professional` | $25 / mo | 2 | 72 | 24 | — |

---

## Notes

- The user's email is automatically marked as verified — no confirmation step required.
- A password-setup email is sent to the user immediately after creation.
- `billing_interval` affects the subscription `expires_at` calculation for paid plans. It has no effect on `free` or `partner` plans.
