# Trial and Partner are orthogonal flags, not values of one status

`is_trial` and `is_verified_partner` are independent booleans. Trial answers "are this member's episodes locked?" and Partner answers "what does this member pay?" — unrelated questions with four meaningful combinations, including trialling a prospective partner (locked episodes, partner pricing).

We chose this over adding `trial` as a third value to `CreditPack::audienceType()`, which is the shape the code invites. A third audience value forces one global answer to "what does a trial member pay?" for a group that plainly contains both prospective partners and retail leads — and getting it wrong is expensive, because partner pricing is roughly a ninth of retail for the same credits ($20 vs $180 for 48).

## Consequences

`audienceType()` stays binary and untouched. A Trial Member sees retail pricing by default; partner pricing is granted by setting `is_verified_partner` separately, which composes rather than conflicts.

Because the two are independent, a partner-flagged trial member satisfies the Add-on's `is_verified_partner` gate without holding a Main Pack. Trial Members are therefore blocked from buying Add-ons explicitly — see the `is_trial` check in `ShopController`. Without that block a $45 Add-on would strand a member with credits and a still-locked library.
