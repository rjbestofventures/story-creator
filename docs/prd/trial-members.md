# PRD: Trial Members

**Status:** ready-for-agent
**Glossary:** [CONTEXT.md](../../CONTEXT.md)
**Decisions:** [ADR-0001](../adr/0001-generate-full-library-for-trials.md), [ADR-0002](../adr/0002-trial-and-partner-are-orthogonal.md)

## Problem Statement

A prospective customer has no way to find out whether StoryCreator produces content that actually sounds like them, short of paying $180 up front. The public demo shows them somebody else's business — a fixed, pre-written walkthrough — which proves the product works in general but says nothing about whether it works for *them*. The moment that convinces a business owner is reading an Episode drawn from their own Interview, in their own voice. Today there is no way to reach that moment without buying first.

Sales has the mirror-image problem. When they meet a promising lead, the only thing they can offer is a discount or a description. They cannot put the actual product, built from that lead's own story, in front of them.

## Solution

A **Trial Member** is provisioned by StoryCreator or a partner through the provisioning API. They sign in, complete a full Interview exactly as a paying member does, and receive a complete twelve-Episode Story generated from their own answers.

Three Episodes are readable. The remaining nine are **Locked Episodes** — already written, already theirs, listed by title so the member can see precisely what is waiting. The pitch is not "buy and we will write more." It is "your library is written; here are three of it."

Acquiring any **Main Pack** unlocks the whole library and ends the trial. Nothing is regenerated, because nothing needs to be — Unlocking is a state change, not a generation.

A Trial Member pays retail prices by default. If they are separately vetted as a **Verified Partner**, they see partner prices instead. The two are independent: one governs whether Episodes are locked, the other governs what the member pays.

## User Stories

**Provisioning**

1. As a provisioning system, I want to create a Trial Member by passing a flag to the existing provisioning endpoint, so that I do not have to integrate against a second API.
2. As a provisioning system, I want a request that asks for both a trial and a pack to be rejected with a clear error, so that I find out immediately that I have asked for two contradictory things.
3. As a Trial Member, I want to receive a password-setup email when my account is created, so that I can sign in without anyone sending me a password.
4. As a Trial Member, I want my email to be pre-verified, so that I am not asked to confirm an address that StoryCreator already knows is mine.
5. As an operator, I want a newly provisioned Trial Member to start with a Trial Allowance of one, so that the cost of a trial is bounded from the moment the account exists.
6. As a provisioning system, I want to mark an existing member as a Verified Partner in a single unattended call, so that a lead who passes vetting gets partner pricing without a human touching the admin panel.
7. As a provisioning system, I want marking someone as a Verified Partner to be safe to repeat, so that a retried call does not fail or double-apply.

**Interviewing and generating**

8. As a Trial Member, I want to reach the Interview without holding Credits, so that I am not bounced to the shop before I have seen anything.
9. As a Trial Member, I want to complete the full Interview, so that the Story is built from my real answers and not a shortened sample.
10. As a Trial Member, I want to generate my Story without spending Credits, so that the trial costs me nothing.
11. As a Trial Member, I want my library generated at a fixed size, so that I am not asked to choose an Episode count whose meaning I cannot yet judge.
12. As a Trial Member, I want no tier selector shown to me during generation, so that the only decision in front of me is whether to unlock, not which pack to compare.
13. As an operator, I want a Trial Member's generation to consume their Trial Allowance, so that a single account cannot produce libraries indefinitely.
14. As a Trial Member who has used my Trial Allowance, I want to be told clearly that my trial Story is already made and shown the way to unlock it, so that I understand why I cannot start another.
15. As an operator, I want the total AI spend of a Trial Member to be exactly one generation, so that trial cost is predictable per account rather than open-ended.

**Reading a trial library**

16. As a Trial Member, I want to read the first three Episodes in full, so that I can judge whether the writing sounds like me.
17. As a Trial Member, I want to see the titles of my Locked Episodes, so that I know specifically what I am being offered rather than a count.
18. As a Trial Member, I want Locked Episodes shown in their real position in the library, so that I understand this is one continuous Story rather than a sample plus an advert.
19. As a Trial Member, I want a clear and single call to action on my Locked Episodes, so that I know exactly what unlocks them.
20. As a Trial Member, I want my trial Story listed among my Stories, so that it is plainly mine and not a demo.
21. As the business, I want the text of a Locked Episode never to reach the browser, so that the paywall cannot be bypassed by reading the page source or the network response.
22. As the business, I want Locked Episodes excluded from every per-Episode operation, so that no endpoint becomes a side door to content the member has not unlocked.
23. As a Trial Member, I want to copy and hand-edit my three unlocked Episodes, so that I can actually use them and see the product working.
24. As the business, I want AI refine and regenerate unavailable to Trial Members, so that a trial cannot be turned into an unlimited generation tap.
25. As a Trial Member, I want to be warned before deleting my trial Story, so that I do not destroy a library my Trial Allowance can no longer replace.

**Converting**

26. As a Trial Member, I want acquiring any Main Pack to unlock my entire library immediately, so that what I bought is exactly what I was shown.
27. As a Trial Member, I want my trial to end when I buy, so that I become an ordinary member with no lingering restrictions.
28. As a converted member, I want the Credits I purchased to remain fully available after unlocking, so that I can start a second Story straight away.
29. As a Trial Member, I want unlocking to reveal the Episodes I was already shown the titles of, so that nothing is rewritten or substituted at the moment I pay.
30. As a Trial Member who never buys, I want my Locked Episodes to remain waiting indefinitely, so that I can come back later without losing the Interview I already gave.
31. As an operator, I want an admin-granted Main Pack to end a trial exactly as a purchase does, so that comping a customer needs no special handling.
32. As an operator, I want granting a partner-type Main Pack to also confer Verified Partner status, so that a member granted partner credits is not quoted retail prices at their next purchase.

**Pricing**

33. As a Trial Member, I want to see retail prices by default, so that the trial does not silently give away partner pricing.
34. As a vetted Trial Member, I want to see partner prices, so that the discount I was promised is the one I am charged.
35. As the business, I want Add-ons unavailable to Trial Members, so that nobody unlocks a library priced at $180 with a $45 top-up, and nobody strands themselves holding Credits they cannot spend.

**Administration**

36. As an admin, I want to see which members are Trial Members and how much Trial Allowance remains, so that I can answer a support question without reading the database.
37. As an admin, I want to adjust a member's Trial Allowance, so that I can give a promising lead a second run without a code change.
38. As an admin, I want a member's trial state and partner state shown separately, so that I am not misled into thinking one implies the other.

## Implementation Decisions

**Trial is a member property, not a pack type.** Two independent boolean properties live on the member: trial state and Verified Partner state. `CreditPack::audienceType()` stays binary and is not extended with a third audience. See ADR-0002.

**Locked is derived, never stored.** An Episode is Locked when its Story's owner is a Trial Member *and* its Episode number exceeds the unlocked count (3). No column is added to Episodes and no sweep runs at unlock time — clearing the member's trial state unlocks every Episode they own, atomically and for free. Both the unlocked count (3) and the trial library size (12) are named constants, not literals.

**Schema.** Members gain a trial boolean (default false) and a Trial Allowance integer (default 0; provisioning a trial sets it to 1). No changes to Episodes, Stories, Credit Packs, or the credit ledger.

**`CreditPack::grantTo()` is the single acquisition funnel.** It already writes the ledger entry and increments Credits; it additionally derives Verified Partner status when the pack type is partner, and ends the trial when the pack is a Main Pack. Because the Stripe webhook, the checkout success path, the admin grant, and provisioning all route through it, no acquisition path needs its own handling and none can drift from the others. Add-on grants change neither partner status nor trial state.

**The credit bypass is scoped to generation only.** Trial Members pass the credit-requirement middleware and skip the charge when generating a Story; their Trial Allowance is decremented instead, and generation is refused once it reaches zero. `User::canRefine()` is left untouched, so AI refine and regenerate remain blocked by a zero Credit balance with no new condition. Inline text editing, which costs nothing, stays available.

**Trial generation is fixed at 12 Episodes.** Trial Members are not offered an Episode-count choice and the tier check is not consulted for them; the count is forced. Twelve is not arbitrary — it is the smallest purchasable tier, so unlocking cannot hand a member a library above the tier they bought. See ADR-0001.

**Serialisation is the enforcement boundary.** A Locked Episode is serialised as its number and title only. Content, version history, and refine instructions are never selected or sent. This is a rule about what leaves the server, not about what the client renders — no locked content may appear in any response payload.

**Authorization is centralised.** Ownership, demo-story, and lock conditions currently repeat across the per-Episode endpoints. They move behind a single Story/Episode authorization layer so that the lock is not a fourth scattered condition, and so a newly added endpoint inherits the rule rather than forgetting it. The audio-playback endpoints refuse Locked Episodes on the same basis.

**Shop.** Add-on availability requires a Main Pack and non-trial state. Both the shop's advertised availability and the checkout guard enforce it, since a Verified Partner in trial would otherwise satisfy the existing partner-or-owns-pack condition on its own.

**API contract — provisioning a trial.** The existing user-creation endpoint accepts an optional `trial` boolean, defaulting to false. Supplying `trial` together with `pack` returns 422 naming the contradiction, rather than silently resolving it. Trial Allowance is not settable through the API in this release.

**API contract — vetting.** A new endpoint marks an existing member as a Verified Partner by email, mirroring the shape of the existing deactivation endpoint: a single email in, a member summary out, idempotent on repeat. It sets partner status only — it does not grant Credits, unlock Episodes, or end a trial.

## Testing Decisions

**What makes a good test here.** Tests assert externally observable behaviour: what a request returns, what a payload contains, what state an acquisition leaves behind. They do not assert that a particular class was consulted or a particular method called. The lock is the clearest case — the test that matters asserts that locked content is *absent from the response*, not that a policy returned false. Written that way, the tests survive the authorization layer being refactored, which it will be.

**Seams**, all of which already exist in the suite:

1. **HTTP routes** — the primary seam, exercised with an acting-as member and named routes. Covers provisioning, the contradictory-request rejection, reaching the Interview without Credits, Trial Allowance exhaustion, rejection of every per-Episode operation on a Locked Episode, and Add-on unavailability. Prior art: the episode tier gate tests.
2. **Inertia props** — asserting on the rendered page's props, specifically the *absence* of content keys for Locked Episodes. This is the seam that encodes the serialisation rule; nothing else can distinguish "withheld" from "hidden in the UI." Prior art: the billing history assertion in the answers playback tests.
3. **`CreditPack::grantTo()` called directly** — one place to prove that acquisition grants Credits, writes the ledger, confers partner status for partner packs, ends a trial for Main Packs, and does none of the latter two for Add-ons. Covers the webhook, admin, and provisioning paths without Stripe or HTTP. Prior art: the episode tier gate tests already use this seam.
4. **Faked HTTP for external services** — for playback endpoints on Locked Episodes, so no request reaches a speech provider. Prior art: the answers playback tests.
5. **A mocked story generator** — to assert that generation produces twelve Episodes and locks nine without calling Anthropic. Prior art: the story generation episode count tests, subject to the prerequisite below.

**Explicitly not a seam.** The authorization layer is not tested directly. It is the implementation behind seam 1.

**Prerequisite.** The existing story generation episode count tests currently fail: they exercise the removed subscription-based episode limit, which is the same code path this feature rewrites. They must be deleted or rewritten against the credit-pack model before this work begins, or seam 5 has no trustworthy prior art and the suite stays red throughout the build.

## Out of Scope

- **Per-Episode unlocking.** Unlocking is all-or-nothing. Spending Credits to unlock individual Episodes is a plausible future model but is not built here.
- **Model-nominated teaser Episodes.** Episodes 1 to 3 unlock. Letting the generator nominate its three strongest would require changing the generation output contract for every member, and should wait for conversion data.
- **Setting partner status at creation time via the API.** Reachable through the admin panel; the API flag is deferred until a caller needs it.
- **Trial expiry.** Locked Episodes persist indefinitely. There is no time box.
- **Setting Trial Allowance through the API.** Defaults to 1; adjustable by an admin.
- **Paywall visual design** beyond the payload contract stated above.
- **Provisioning endpoint hardening** — constant-time token comparison, rate limiting, and the account-enumeration behaviour of the deactivation endpoint. Real issues in the same file, tracked separately.
- **The other failing tests** in the suite (registration redirect, the stock example test). Only the story generation count tests are in scope, as a prerequisite.

## Further Notes

**Cost.** A trial costs roughly $0.08 in generation, once, per Trial Allowance consumed. Generating the full library rather than three Episodes costs about $0.07 more and is a deliberate quality decision recorded in ADR-0001 — the generator balances Episode types across a complete set, so a library assembled from two separate generations does not hang together.

**Acquisition cost.** A converting Trial Member receives their twelve-Episode library free and retains the full Credit balance they purchased — approximately 12 Credits, or about $45 of retail value on a $180 sale. This is deliberate: charging Credits to unlock would add friction at the exact moment of purchase intent.

**Relationship to demo Stories.** The existing seeded demo Stories are unrelated and do not collide. They are attached only during self-registration through the demo on-ramp, a path provisioned members never take. A trial Story is the member's own, appears in their Story list, and is not a demo Story — though both happen to forbid AI operations, for different reasons.

**Trial Allowance is consumed at generation, not held per Story.** Deleting a trial Story does not restore it. This is the safe default, but it means a Trial Member can permanently destroy a library they cannot regenerate, which is why story 25 asks for a confirmation step.
