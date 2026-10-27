# Testing

```bash
php artisan test                 # whole suite (SQLite in-memory, no setup needed)
php artisan test --filter=Royalty
```

The suite uses `RefreshDatabase` and an in-memory SQLite database (the Laravel default in `phpunit.xml`), and `withoutVite()` so no front-end build is required.

## Coverage

| File | What it verifies |
|---|---|
| `Unit/RoyaltyCalculatorTest` | Percentage + flat fee maths, zero/negative sales, rounding |
| `Feature/RoleAccessTest` | Login/logout, guests redirected, franchisor/regional/franchisee scoping on locations, franchisor-only actions, self-demotion blocked |
| `Feature/RoyaltyTest` | Sales → statement generation, no duplicates on resubmit, paid statements immutable, cross-location forbidden, mark-paid permissions, overdue flagging, validation |
| `Feature/ComplianceTest` | Assign to all visible locations only, franchisee permissions, completing/resetting checks |
| `Feature/SupplyOrderTest` | Totals and stock decrement, over-order rejected atomically, zero-quantity rejected, cross-location forbidden, cancel returns stock once, catalogue permissions |
| `Feature/MessagesAndDocumentsTest` | Franchisee messages pinned to own location, announcements, thread isolation, replies, upload/download, file type rules, document isolation |
| `Feature/ReportsAndApiTest` | Dashboard scoping, P&L totals, CSV export, API token auth, calculation endpoint, franchisor-only API actions |
| `Feature/OverdueCommandTest` | Overdue command flags and notifies once; leaves paid statements alone |

## Not covered

Browser-level behaviour (the Vue calculator and live order totals), load testing, and real mail delivery are not covered by automated tests. See `docs/KNOWN_ISSUES.md`.
