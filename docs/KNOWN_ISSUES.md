# Known Issues & Scope Notes

## Verification status

The code in this package was written in an environment that could not install Laravel or Composer packages (network policy blocked Packagist), so **the test suite has not yet been executed.** PHP syntax was checked, but the first `php artisan test` run on a real install is the real verification. Please run it and fix anything it reports before relying on this in production. Test results are therefore not included in this package; run `php artisan test` to generate them.

## Scope decisions (versus the 15-day plan)

- **Day 13 — AI/advanced logic:** the plan marks this as not core to the MVP (optional royalty forecasting in a future phase). Not implemented.
- **Day 14 — third-party integrations:** implemented as email notification only (overdue royalties). No payment gateway, SMS, or push. Mail uses the `log` driver until `MAIL_*` is configured.
- **Authentication:** Laravel session auth for the web UI and Sanctum bearer tokens for the API, instead of the JWT/OAuth mentioned in the plan.
- **Design (Day 4):** no Figma mockups are included; the UI is built directly in Blade + Tailwind.
- **Deployment (Day 15):** Docker and CI files are provided, but no server was provisioned and no monitoring/backup tooling is configured.

## Limitations

- **Vue and Tailwind fallback via CDN:** the layout loads Vue (and Tailwind, if assets aren't built) from public CDNs. For offline or locked-down environments, bundle Vue through Vite and run `npm run build`.
- **Reports:** monthly granularity only; sales and costs are entered manually (no POS integration).
- **Order approval:** orders decrement stock when placed (not when approved); cancelling restores it.
- **Documents:** stored on local disk. For multiple app servers use S3 or another shared disk (`FILESYSTEM_DISK`).
- **Search/pagination:** basic filters only; no full-text search.
- **Single currency:** amounts are displayed in `$`.
- **User passwords:** no self-service password reset or email verification flow.
- **No audit log** of who changed what.
