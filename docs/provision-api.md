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
| `trial` | boolean | No | Create the account as a Trial Member. Defaults to `false`. Cannot be combined with `pack`. |

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
        "is_trial": false,
        "trial_allowance": 0,
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

## Create a Trial Member

Pass `trial: true` to create the account as a **Trial Member**. They complete the full interview and receive a complete 12-episode story generated from their own answers, of which the first 3 are readable — the rest are locked until they buy a pack.

**`POST /api/provision/user`**

```http
POST /api/provision/user
Authorization: Bearer your-secret-token
Content-Type: application/json
Accept: application/json

{
    "name": "Jane Smith",
    "email": "jane@example.com",
    "trial": true
}
```

```json
{
    "user": {
        "id": 43,
        "name": "Jane Smith",
        "email": "jane@example.com",
        "is_verified_partner": false,
        "is_trial": true,
        "trial_allowance": 1,
        "credits": 0
    },
    "pack": null
}
```

- A Trial Member holds no credits and generates without spending any. `trial_allowance` bounds how many stories they may generate before converting; it starts at 1 and an admin can adjust it.
- Trial members see **retail** pack pricing by default. To trial a prospective partner at partner pricing, provision the trial and then call [Verify Partner](#verify-partner) — the two are independent.
- `trial` and `pack` are mutually exclusive and returning both yields `422`. A pack grants credits and ends a trial, so asking for both asks for opposite things.

### How a trial ends

Three ways, all of which unlock the member's whole library in the same moment. Nothing is regenerated — the locked episodes were written during the trial and simply become readable.

| | What ends the trial | Credits granted |
|---|---|---|
| The member buys a main pack in the shop | Purchase | Yes, the pack's credits |
| An admin grants a main pack | Grant | Yes, the pack's credits |
| You call [Convert to Partner](#convert-to-partner) | Vetting | **No** |

Add-ons never end a trial, and trial members cannot buy them.

Setting `trial_allowance` to `0` in the admin panel is **not** a conversion — it stops the member starting another story but leaves their existing episodes locked. It is a brake.

---

## Convert to Partner

Converts a vetted trial member into a verified business partner: they get partner pricing **and their trial ends**, which unlocks their whole library immediately. No credits are granted — they hold none until they buy a pack, so they can read and copy all 12 episodes but cannot refine them or start another story until they do.

Use this when someone has trialled the product, passed vetting, and should be given their library as part of onboarding. Use [Verify Partner](#verify-partner) instead when a trial member should keep their locked library and simply see partner prices.

Safe to call more than once, and safe to call on a member who was never in a trial — they just become a verified partner.

**`POST /api/provision/convert-to-partner`**

### Request Body

| Field | Type | Required | Description |
|---|---|---|---|
| `email` | string | Yes | Email address of an existing account |

### Example Request

```http
POST /api/provision/convert-to-partner
Authorization: Bearer your-secret-token
Content-Type: application/json
Accept: application/json

{
    "email": "jane@example.com"
}
```

### Success Response — `200 OK`

```json
{
    "user": {
        "id": 43,
        "name": "Jane Smith",
        "email": "jane@example.com",
        "is_verified_partner": true,
        "is_trial": false,
        "trial_allowance": 0,
        "credits": 0
    }
}
```

### Error Responses

| Status | Cause |
|---|---|
| `401` | Missing or invalid bearer token |
| `404` | No account with that email |
| `422` | Validation failed |

---

## Verify Partner

Marks an existing account as a verified business partner, which governs **pack pricing only**. It grants no credits, unlocks no episodes, and leaves a running trial untouched. Safe to call more than once.

To also end the trial and unlock the member's library, call [Convert to Partner](#convert-to-partner) instead.

**`POST /api/provision/verify-partner`**

### Request Body

| Field | Type | Required | Description |
|---|---|---|---|
| `email` | string | Yes | Email address of an existing account |

### Example Request

```http
POST /api/provision/verify-partner
Authorization: Bearer your-secret-token
Content-Type: application/json
Accept: application/json

{
    "email": "jane@example.com"
}
```

### Success Response — `200 OK`

```json
{
    "user": {
        "id": 43,
        "name": "Jane Smith",
        "email": "jane@example.com",
        "is_verified_partner": true,
        "is_trial": true,
        "credits": 0
    }
}
```

### Error Responses

| Status | Cause |
|---|---|
| `401` | Missing or invalid bearer token |
| `404` | No account with that email |
| `422` | Validation failed |

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
