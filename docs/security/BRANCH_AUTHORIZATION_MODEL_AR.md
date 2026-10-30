# Branch Authorization Model

## Boundary

- `branch_user_assignments` is the authoritative membership table.
- Spatie Teams stores each staff role assignment under `branch_id`.
- Every branch-admin route is protected by `auth` + `EnsureBranchAccess` + a permission check.
- The branch route-model binding must resolve a `Branch` model before middleware executes.
- Numeric branch IDs are not treated as secrets; membership authorization is the actual security boundary.

## Public customer registration

The public Fortify registration action creates `users` and `customer_profiles` only. It never accepts `role`, `branch_id`, `permissions`, `is_active`, or account status from the request.

## Staff provisioning

Staff accounts are provisioned from the server-side Artisan command. A valid branch assignment must exist before a branch-scoped role is assigned. Passwords are prompted as hidden input and must not be supplied as command-line arguments.

## Team context

`EnsureBranchAccess` sets Spatie's current team ID to the route branch ID only after checking active membership, clears cached `roles` and `permissions` relations, executes the request, and resets the team context in a `finally` block.

## Branch isolation

A staff member assigned to branch A must receive HTTP 403 for branch B, even if they know branch B's ID. The permission check is performed after branch membership and team context have been established.

## Operational rules

- Never rely on hiding navigation links as authorization.
- Apply policies or permission middleware on every write route added later.
- Never accept role names or permissions from public forms.
- When changing the active branch in a request/job, set the Spatie team ID explicitly and clear cached role/permission relations after switching.
- For queued jobs, include `branch_id` in the payload and set/reset team context in the job's `try/finally` block.
