# Trial Member API

Two endpoints: create a trial member, and convert one into a Verified Business Partner.

This is a focused subset of the [Provision API](./provision-api.md) — see that document for account creation with credit packs, and for deactivating accounts.

---

## Authentication

Every request carries a static bearer token, held on the server as `PROVISION_API_TOKEN`:

```
Authorization: Bearer {PROVISION_API_TOKEN}
```

There is no user session and no login flow. A missing or incorrect token returns `401 Unauthorized`.

---

## What a Trial Member is

A trial member signs in and completes the **full interview**, exactly as a paying customer does. StoryBot then writes a complete **12-episode** library from their own answers.

**The first 3 episodes are readable. The remaining 9 are locked** — written, stored, and theirs, but withheld until the trial ends. The locked episodes show their titles, so the member can see precisely what is waiting.

A trial member:

- holds **no credits** and spends none to generate
- may generate **one story** (`trial_allowance`, adjustable by an admin)
- can read, copy, and hand-edit their 3 unlocked episodes
- **cannot** use AI refine or regenerate, and cannot buy the Credit Boost add-on
- sees **retail** pack pricing by default

---

## 1. Create a Trial Member

**`POST /api/provision/user`**

Creates the account, marks its email verified, and emails the member a password-setup link. There is no confirmation step for them to complete.

### Request Body

| Field | Type | Required | Description |
|---|---|---|---|
| `name` | string | Yes | Full name. Max 255 characters. |
| `email` | string | Yes | Must not already exist on the instance. |
| `trial` | boolean | Yes | Send `true`. Omitting it creates an ordinary account with no credits. |

### Example

```bash
curl -X POST https://your-domain/api/provision/user \
  -H "Authorization: Bearer $PROVISION_API_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"name":"Jane Smith","email":"jane@example.com","trial":true}'
```

### Success — `201 Created`

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

### Errors

| Status | Cause |
|---|---|
| `401` | Missing or invalid bearer token |
| `422` | Validation failed — see the `errors` key |

Two `422` cases worth handling explicitly:

**Email already registered**

```json
{
    "message": "The email has already been taken.",
    "errors": { "email": ["The email has already been taken."] }
}
```

**`trial` sent together with `pack`** — these are mutually exclusive. A pack grants credits and ends a trial, while a trial holds none, so asking for both asks for opposite things. No account is created.

```json
{
    "message": "A trial member cannot be granted a pack. Provision the trial without a pack, or grant the pack without the trial flag.",
    "errors": {
        "pack": ["A trial member cannot be granted a pack. Provision the trial without a pack, or grant the pack without the trial flag."]
    }
}
```

---

## 2. Convert to Verified Business Partner

**`POST /api/provision/convert-to-partner`**

Converts a vetted trial member into a Verified Business Partner (VBP). Two things happen together:

1. They are offered **partner pricing** instead of retail.
2. Their **trial ends, and their whole library unlocks immediately** — all 12 episodes become readable.

Nothing is regenerated. The locked episodes were written during the trial and simply become readable, so what they unlock is exactly what they were shown the titles of.

**No credits are granted.** The member can read and copy all 12 episodes, but cannot AI-refine them or start a second story until they buy a pack — now at partner prices.

Safe to call more than once, and safe to call on someone who was never in a trial; they simply become a VBP.

### Request Body

| Field | Type | Required | Description |
|---|---|---|---|
| `email` | string | Yes | Email address of an existing account |

### Example

```bash
curl -X POST https://your-domain/api/provision/convert-to-partner \
  -H "Authorization: Bearer $PROVISION_API_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"jane@example.com"}'
```

### Success — `200 OK`

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

### Errors

| Status | Cause |
|---|---|
| `401` | Missing or invalid bearer token |
| `404` | No account with that email |
| `422` | Validation failed |

---

## The lifecycle end to end

1. **Provision.** `POST /api/provision/user` with `trial: true`. Jane exists, allowance 1, no credits.
2. **Jane sets her password** from the emailed link and signs in.
3. **Jane completes the interview.** The full set of questions, same as a paying customer.
4. **Jane generates.** 12 episodes are written. She reads 3; 9 are locked and listed by title. Her allowance is now 0.
5. **You vet Jane.** `POST /api/provision/convert-to-partner`. Her library opens and she is now on partner pricing.
6. **Jane buys a partner pack** when she wants to refine her episodes or write a second story.

---

## Notes for integrators

**Do not send the bearer token in the same channel as this document.** The token can open any trial member's library at no charge.

**There is no endpoint that re-locks a library.** Conversion is one-way. Setting `trial_allowance` back above zero in the admin panel starts a fresh trial but does not re-lock already-unlocked episodes.

**Partner status and trial status are independent.** If a trial member should get partner *pricing* while keeping their library locked, call `POST /api/provision/verify-partner` instead — it sets pricing alone and leaves the trial running. `convert-to-partner` is the one that unlocks.

**Deleting a story does not restore trial allowance.** A trial member who deletes their story cannot generate another one.
