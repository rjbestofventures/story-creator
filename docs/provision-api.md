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

Creates a new user account, optionally grants the specified credit pack, and sends the user a password-setup email. If the email already belongs to an account, that account is updated instead — see [If the email already exists](#if-the-email-already-exists).

**`POST /api/provision/user`**

### Request Body

| Field | Type | Required | Description |
|---|---|---|---|
| `name` | string | Yes | Full name of the user |
| `email` | string | Yes | Email address. An existing account with this email is updated, not rejected. |
| `pack` | string | No | Credit pack slug — see [Packs](#packs). Omit to create the account with 0 credits and no pack. |
| `trial` | boolean | No | Create the account as a [Complementary Trial](#create-a-complementary-trial). Defaults to `false`. Cannot be combined with `pack` or `vbp_plan`. |
| `vbp_plan` | string | No | `gold`, `silver`, or `other`. Creates the account as a Verified Business Partner on that plan and grants the plan's credits (Gold 36, Silver 24, Other 36). Cannot be combined with `trial` or `pack`. |

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
    "created": true,
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

> `is_verified_partner` is set to `true` automatically when the granted pack's type is `partner`. `pack` is `null` in the response if no `pack` was requested. `created` is `true` for a new account.

### Error Responses

| Status | Cause |
|---|---|
| `401` | Missing or invalid bearer token |
| `422` | Validation failed (see `errors` key in response) |

**Example 422 response:**
```json
{
    "message": "The selected vbp plan is invalid.",
    "errors": {
        "vbp_plan": ["The selected vbp plan is invalid."]
    }
}
```

### If the email already exists

An email that already has an account is **updated, not rejected**. The response is `200 OK` with `"created": false`.

| Request field | Applied to the existing account? |
|---|---|
| `vbp_plan` | Yes. Records the plan and makes them a partner. Plan credits are added only if they were not already a full partner, so credits are never granted twice. |
| `pack` | Yes. The pack's credits are granted, exactly as when an admin grants a pack. |
| `name` | No. The existing name is kept. |
| `trial` | No. An existing account never starts a trial. |

- No account-created or password email is sent, since they already have a login.
- The same validation applies: `vbp_plan` cannot be combined with `trial` or `pack`, and `trial` cannot be combined with `pack`. A conflicting request returns `422` even for an existing email.
- If neither `pack` nor `vbp_plan` is sent, nothing changes and the current account is returned.

```json
{
    "created": false,
    "user": {
        "id": 42,
        "name": "Jane Smith",
        "email": "jane@example.com",
        "is_verified_partner": true,
        "vbp_plan": "silver",
        "credits": 24
    },
    "pack": null
}
```

---

## Create a Complementary Trial

There is one trial: the **Complementary Trial** (shown as the account type in the admin panel). Its credits are called **Complementary Credits** inside StoryBot. It replaces the old Trial Member and Temporary VBP, so both requests below create the same account. Either way, the user is emailed a password-setup link.

A Complementary Trial:

- starts with **6 Complementary Credits**
- generates **one story of 12 episodes** for **3 credits**. Only the first **3 episodes are readable**; the other **9 are locked** until the member becomes a Verified Business Partner
- keeps the remaining **3 credits for AI Refine only**. Locked episodes cannot be refined. A second story is refused, even after deleting the first
- cannot buy packs, and cannot unlock the locked episodes with credits. The shop redirects them to their library
- is **deactivated automatically 3 months after creation** unless converted with [Convert to Partner](#convert-to-partner)

Two ways to create one:

- **`POST /api/provision/temporary-vbp`**, with `name` and `email` only.
- **`POST /api/provision/user`** with `trial: true`, which also accepts the other create-user fields. `trial` cannot be combined with `pack` or `vbp_plan` (returns `422`).

### Request Body (`/api/provision/temporary-vbp`)

| Field | Type | Required | Description |
|---|---|---|---|
| `name` | string | Yes | Full name of the user |
| `email` | string | Yes | Email address (must be unique) |

### Example Request

```http
POST /api/provision/temporary-vbp
Authorization: Bearer your-secret-token
Content-Type: application/json
Accept: application/json

{
    "name": "Tess Trial",
    "email": "tess@example.com"
}
```

### Success Response — `201 Created`

```json
{
    "user": {
        "id": 51,
        "name": "Tess Trial",
        "email": "tess@example.com",
        "is_active": true,
        "is_verified_partner": false,
        "vbp_plan": null,
        "is_temporary_vbp": true,
        "temporary_vbp_expires_at": "2026-12-29T13:35:50+00:00",
        "is_trial": false,
        "trial_allowance": 0,
        "credits": 6
    }
}
```

`is_temporary_vbp` is the flag that marks a Complementary Trial. `is_trial` and `trial_allowance` belong to the retired Trial Member and stay `false` / `0` for new accounts.

### Error Responses

| Status | Cause |
|---|---|
| `401` | Missing or invalid bearer token |
| `422` | Validation failed (for example, the email is already taken, or `trial` was combined with `pack` or `vbp_plan`) |

### Public signup form

The website's **Complementary Trial** button opens a form (first name, last name, phone, email) that creates the same account without the provision token. It emails the admin, and posts `first_name`, `last_name`, `phone`, `email` and `source: storybot_trial_signup` to the CRM webhook set in `CRM_TRIAL_WEBHOOK_URL`.

### Legacy Trial Members

Accounts created before this change as Trial Members (`is_trial: true`, no credits, a `trial_allowance`) keep working as before. No new ones can be created.

---

## Convert to Partner

Converts a **Complementary Trial** (or a legacy trial member) into a Verified Business Partner on a VBP plan. They get partner pricing and the plan's credits:

| `vbp_plan` | Credits granted |
|---|---|
| `gold` | 36 |
| `silver` | 24 |
| `other` | 36 |

- **Complementary Trial:** the trial status and its deactivation date are cleared, leftover credits are kept (a fresh trial has 3 left), and the one-story limit is lifted. The 9 locked episodes stay locked until they spend credits to open them. An account that the 3-month expiry already deactivated is reactivated.
- **Legacy trial member:** the trial ends, but the library stays locked. They spend the new credits to open it.
- **Already a full partner:** only the plan is changed. No credits are granted again.

**`POST /api/provision/convert-to-partner`**

### Request Body

| Field | Type | Required | Description |
|---|---|---|---|
| `email` | string | Yes | Email address of an existing account |
| `vbp_plan` | string | Yes | `gold`, `silver`, or `other` |

### Example Request

```http
POST /api/provision/convert-to-partner
Authorization: Bearer your-secret-token
Content-Type: application/json
Accept: application/json

{
    "email": "tess@example.com",
    "vbp_plan": "gold"
}
```

### Success Response — `200 OK`

```json
{
    "user": {
        "id": 51,
        "name": "Tess Trial",
        "email": "tess@example.com",
        "is_active": true,
        "is_verified_partner": true,
        "vbp_plan": "gold",
        "is_temporary_vbp": false,
        "temporary_vbp_expires_at": null,
        "is_trial": false,
        "trial_allowance": 0,
        "credits": 39
    }
}
```

### Error Responses

| Status | Cause |
|---|---|
| `401` | Missing or invalid bearer token |
| `404` | No account with that email |
| `422` | Validation failed (`vbp_plan` missing or not `gold`/`silver`/`other`) |

---

## Verify Partner

Marks an existing account as a verified business partner, which governs **pack pricing only**. It grants no credits, unlocks no episodes, and leaves a running trial untouched. Safe to call more than once.

To also end the trial and grant the plan's credits, call [Convert to Partner](#convert-to-partner) instead.

Accepts an optional `vbp_plan` (`gold`, `silver`, or `other`), which is recorded on the account without granting credits.

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
