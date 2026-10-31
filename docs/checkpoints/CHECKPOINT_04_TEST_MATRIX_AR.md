# Checkpoint 04 — Acceptance Matrix

| Test | Expected |
|---|---|
| Store routes registered | All named public store routes exist |
| Guest add to cart | Cart created with hashed session token |
| Registered add to cart | Cart belongs to authenticated user |
| Branch-specific pricing | Server uses branch override, not client price |
| Modifier ownership | Reject option not attached to selected product |
| Required modifier group | Reject missing required selections |
| Checkout delivery | Address and city required |
| Checkout snapshots | Product/variant/modifier names and prices copied |
| Idempotency | Repeated checkout on converted cart returns same order |
| Guest order access | Only same guest session can view guest order |
| Vodafone Cash | Rejected until payment gateway CP08 |
| Build | Vite production build succeeds |

Do not mark the checkpoint complete until all tests and Vite build succeed in the user's Laravel environment.
