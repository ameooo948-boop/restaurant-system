# Advanced Inventory — Database Schema v1

| Migration | Table | Purpose |
|---|---|---|
| 000031 | measurement_units | Restaurant measurement units and dimensions |
| 000032 | ingredients | Ingredient master data and base unit |
| 000033 | ingredient_unit_conversions | Ingredient-specific conversion to base unit |
| 000034 | recipes | One active recipe per product variant |
| 000035 | recipe_items | Ingredient quantities and waste percentage |
| 000036 | modifier_option_ingredients | Ingredient usage per modifier selection |
| 000037 | branch_ingredient_stocks | Branch on-hand and reserved balances |
| 000038 | stock_movements | Append-only stock movement ledger |
| 000039 | stock_reservations | Per kitchen ticket item ingredient reservation |
| 000040 | suppliers | Restaurant suppliers |
| 000041 | purchase_orders | Branch purchase orders |
| 000042 | purchase_order_items | Ordered/received quantities and base-unit cost |
| 000043 | goods_receipts | Idempotent receipt header |
| 000044 | goods_receipt_items | Received quantities per PO item |
| 000045 | inventory_counts | Branch physical count header |
| 000046 | inventory_count_items | Expected, counted and variance quantities |

## Balance semantics
- `on_hand_qty`: physical system balance including items reserved but not yet consumed.
- `reserved_qty`: subset of on-hand committed to confirmed orders awaiting preparation.
- Available-to-promise: `on_hand_qty - reserved_qty`.
- Preparing a kitchen ticket decrements both on-hand and reserved by the reservation amount and appends a negative `consumption` movement.
- Cancelling an unprepared order decrements reserved only and marks the reservation `released`; no stock movement is recorded because physical stock did not change.

## Concurrency and idempotency
- Stock rows are locked in ascending ingredient ID order before reservations/consumption/adjustments.
- A reservation is unique per kitchen ticket item and ingredient.
- Purchase receipt headers have a unique UUID idempotency key.
- Inventory count posting locks count, count items and stock rows; `posted` counts return without replaying movements.
- `stock_movements` are ledger records and their Eloquent model rejects updates/deletes; corrections use compensating entries.
- An order’s first successful reservation set is frozen; subsequent kitchen transitions do not recalculate from recipes that may have changed after confirmation.

## Limitations
- Cost values are captured at receipt only; no FIFO or average-cost layer.
- Unit conversion factors are per ingredient, but the PO UI inputs base-unit quantities for this version.
- Recipe automation requires an active recipe; modifier consumption requires explicit modifier ingredient mappings.
