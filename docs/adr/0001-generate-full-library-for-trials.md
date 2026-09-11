# Trial libraries are generated in full, then locked

A Trial Member reads only 3 episodes, but generation writes all 12 and locks the rest. Unlocking never calls the model — the episodes already exist. We pay for 9 episodes a trial member may never read, at roughly $0.08 per trial.

We do this because the generator balances the library as a whole, not episode by episode. The system prompt distributes episodes across four types proportionally and enforces `SERIES BALANCE` across the full set. Generating 3 now and 9 later produces two independently-balanced sets that don't know about each other — near-certain thematic duplication and a library that doesn't hang together. Deferring the cost degrades the product.

## Considered options

**Generate 3, generate the rest on purchase.** Rejected for the reason above. This is the obvious optimisation and someone will propose it again; the cost saved is about $0.07 per trial, which is not worth a worse library.

## Consequences

A trial library holds exactly 12 episodes because 12 is the smallest purchasable tier (`storybot-basic` / `partner-basic`, `max_episodes` 12). Any larger and a member buying Basic would unlock a library worth Premium or Professional, and the tier ladder would sell nothing above its bottom rung. If tier sizes change, trial library size follows the smallest one.
