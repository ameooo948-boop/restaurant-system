# Restaurant Management System — Checkpoint Ledger

## Locked decisions
- Laravel 13 / PHP 8.3+
- Vue 3 + Inertia.js + Tailwind CSS + Vite
- Modular Monolith; Laravel owns business rules, auth, authorization, and workflows.
- Supabase PostgreSQL as the primary database; Supabase Storage when needed.
- One branch at launch, multi-branch-ready schema and branch-scoped permissions.
- Delivery, Pickup, Dine-in; Guest and Registered customers; Advanced Inventory.
- EGP, Africa/Cairo, Arabic default with English support.
- Vodafone Cash is the only planned online payment method in the first release; a payment provider/account integration must be verified before enabling real charges.

## Checkpoint 00 — Discovery & Architecture
- [x] Scope, stack, modular architecture, database design, payment approach.

## Checkpoint 01 — Project Foundation
- [x] Foundation files delivered in `restaurant-checkpoint-01.zip`.
- [x] User ran all base and restaurant migrations successfully on Supabase.
- [x] `RestaurantBranchSchemaTest`: 2 tests passed, 5 assertions.
- [x] Supabase connection established by user.

## Git / GitHub
- [ ] Local Git identity and repository verified.
- [ ] Initial commit and `checkpoint-01-foundation` tag verified.
- [ ] Private GitHub remote and push verified.

## Checkpoint 02 — Authentication & Authorization
- [x] Overlay package created: customer auth, profile, staff assignment, branch-scoped roles, middleware, first-branch/staff commands, Inertia auth UI, docs, tests.
- [ ] Composer dependencies installed by user.
- [ ] Spatie migration published with Teams enabled and `branch_id` team key.
- [ ] App providers, web middleware, and route include registered.
- [ ] New migrations completed on the user's database.
- [ ] First restaurant/branch data confirmed.
- [ ] Owner/staff account provisioned.
- [ ] Auth and branch-isolation tests passed.
- [ ] Vite production build passed.
- [ ] Git commit and `checkpoint-02-auth-authorization` tag created.

## Delivery ledger
| Version | ZIP | Scope | State |
|---|---|---|---|
| 0.1.0 | `restaurant-checkpoint-01.zip` | Foundation | Verified by user |
| 0.2.0 | `restaurant-checkpoint-02.zip` | Auth, profiles, staff, branch RBAC, Inertia auth UI | Delivered; user setup pending |

## Protocol
Each file change ships as a new versioned ZIP overlay containing `CHANGELOG.md` and checkpoint documentation. Secrets and `.env` are never included. Manual commands and one-line framework registrations are documented separately.
