# CP07 Acceptance Test Matrix

| Test | Expected |
|---|---|
| InventoryRoutesRegisteredTest | inventory routes remain present after fresh app boot |
| InventoryStockWorkflowTest: reserve/consume | confirm reserves; kitchen preparing consumes once |
| InventoryStockWorkflowTest: insufficient stock | confirm rejected atomically |
| InventoryStockWorkflowTest: cancel | cancellation releases reservation; no consumption movement |
| PurchaseReceiptTest | partial receipt updates balance and duplicate idempotency key has no duplicate effects |
| InventoryCountTest | count posting applies variance once |
| StoreRoutesRegisteredTest | storefront route registration regression remains fixed |
| StoreCheckoutTest | all checkout scenarios pass together |
| KitchenRoutesRegisteredTest | kitchen routes survive re-boot |
| KitchenTicketWorkflowTest | existing kitchen workflow unaffected |
| OrderRoutesRegisteredTest / OrderManagementTest | admin order operations unaffected |
| npm run build | production client build succeeds |

The project-owner machine must run the tests against its installed dependencies. Static syntax validation is not a substitute for runtime testing.
