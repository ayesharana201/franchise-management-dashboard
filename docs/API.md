# REST API

Base URL: `/api`. JSON in, JSON out. Send `Accept: application/json`.
All endpoints except `POST /auth/token` require `Authorization: Bearer <token>`.
Results are scoped to the caller's role exactly as in the web app (franchisee → own location, regional manager → own region, franchisor → all). Out-of-scope records return `403`.

## Authentication

### `POST /api/auth/token`
Body: `{ "email": "...", "password": "...", "device_name": "optional" }`
`200` → `{ "token": "1|abc...", "user": { "id", "name", "role", "location_id" } }`
`422` on bad credentials. Rate-limited to 10 requests/minute.

### `GET /api/me`
Current user profile.

### `POST /api/auth/logout`
Revokes the current token.

## Dashboard

### `GET /api/dashboard?from=YYYY-MM-DD&to=YYYY-MM-DD`
KPIs for visible locations (defaults to the last 6 months):
`location_count`, `total_sales`, `net_profit`, `royalties_outstanding`, `overdue_statements`, `compliance_rate`, `failed_checks`, `monthly_sales[]`, `leaderboard[]`.

## Locations

| Method | Path | Notes |
|---|---|---|
| GET | `/locations?region=` | Paginated list of visible locations |
| GET | `/locations/{id}` | Location + 12-month financials |
| POST | `/locations` | **Franchisor only.** Fields: `name, code (unique), region, city?, address?, manager_name?, status (active\|onboarding\|suspended\|closed), royalty_rate, marketing_fee_rate, tech_fee` → `201` |
| GET | `/locations/{id}/financials?from=&to=` | Monthly P&L + totals (`gross_sales, expenses, net_profit, fees_paid, fees_outstanding`) |

## Royalties

| Method | Path | Notes |
|---|---|---|
| GET | `/royalties?status=pending\|paid\|overdue` | Paginated statements |
| POST | `/royalties/calculate` | Body `{ location_id, gross_sales }` → `{ gross_sales, royalty_amount, marketing_fee, tech_fee, total_due }` |
| POST | `/royalties/{id}/paid` | **Franchisor / regional manager.** Marks a statement paid |

## Compliance

| Method | Path | Notes |
|---|---|---|
| GET | `/compliance?status=pending\|passed\|failed` | Paginated checks |
| PUT | `/compliance/{id}` | Body `{ status: pending\|passed\|failed, notes? }` |

## Errors

`401` unauthenticated · `403` not permitted / out of scope · `404` not found · `422` validation (`{ "message", "errors": { field: [...] } }`).

## Example

```bash
TOKEN=$(curl -s -X POST http://localhost:8000/api/auth/token \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"email":"admin@franchise.test","password":"password"}' | jq -r .token)

curl -s http://localhost:8000/api/dashboard -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
```
