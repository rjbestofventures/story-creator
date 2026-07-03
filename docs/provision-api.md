# Provision API

Machine-to-machine endpoint for creating users and optionally granting them a credit pack. No user session required — authenticated via a static bearer token stored in the server environment.

---

## Authentication

All requests must include the `Authorization` header with the token set in `PROVISION_API_TOKEN` on the server.

```
Authorization: Bearer {PROVISION_API_TOKEN}
```

Returns `401 Unauthorized` if the token is missing or incorrect.

---

## Create User

Creates a new user account, optionally grants the specified credit pack, and sends the user a password-setup email.

**`POST /api/provision/user`**

### Request Body

| Field | Type | Required | Description |
|---|---|---|---|
| `name` | string | Yes | Full name of the user |
| `email` | string | Yes | Email address (must be unique) |
| `pack` | string | No | Credit pack slug — see [Packs](#packs). Omit to create the account with 0 credits and no pack. |

### Example Request

```http
POST /api/provision/user
Authorization: Bearer your-secret-token
Content-Type: application/json
Accept: application/json

{
    "name": "Jane Smith",
    "email": "jane@example.com",
    "pack": "partner-basic"
}
```

### Success Response — `201 Created`

```json
{
    "user": {
        "id": 42,
        "name": "Jane Smith",
        "email": "jane@example.com",
        "is_verified_partner": true,
        "credits": 48
    },
    "pack": "partner-basic"
}
```

> `is_verified_partner` is set to `true` automatically when the granted pack's type is `partner`. `pack` is `null` in the response if no `pack` was requested.

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

## Packs

Credits are one-time grants and never expire — there is no subscription or billing interval. `max_episodes` is the highest episode count the user can choose per story; it reflects the most recently granted/purchased pack.

### Verified Business Partner packs (discounted, sets `is_verified_partner: true`)

| Slug | Price | Credits | Max episodes |
|---|---|---|---|
| `partner-basic` | $20 | 48 | 12 |
| `partner-premium` | $30 | 72 | 18 |
| `partner-professional` | $40 | 96 | 24 |

### Pay to Play packs (public retail pricing)

| Slug | Price | Credits | Max episodes |
|---|---|---|---|
| `storybot-basic` | $180 | 48 | 12 |
| `storybot-premium` | $270 | 72 | 18 |
| `storybot-professional` | $360 | 96 | 24 |

### Add-on

| Slug | Price | Credits | Notes |
|---|---|---|---|
| `credit-boost` | $45 | 12 | Top-up only; granting it via this API still works even without an existing pack, unlike the in-app shop which requires an active pack first. |

---

## Notes

- The user's email is automatically marked as verified — no confirmation step required.
- A password-setup email is sent to the user immediately after creation.
- 1 credit = 1 episode generation, or 1 episode refine/redo.
- Credits never expire and there is no subscription to manage — granting a pack is a one-time, permanent credit top-up.
