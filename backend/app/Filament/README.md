# Department 8 — Admin Panel (Filament 3)

Admin/agent control panel for the Aakash Realtor marketplace, mounted at **`/admin`**.
Brand colour is gold (`#C9A227`); login is enabled.

Bootstrapped by `app/Providers/Filament/AdminPanelProvider.php`, registered in
`bootstrap/providers.php`. Resources, pages and widgets are auto-discovered from
the `App\Filament` namespace, so new files appear without editing the provider.

Panel access is gated by `User::canAccessPanel()` (already in the model): only
**active** users with role **admin** or **agent** can sign in.

## Resources

| Resource | Model | Navigation group | Notes |
|----------|-------|------------------|-------|
| `PropertyResource` | Property | Listings | Moderation hub. Full edit form, status/flag filters, approve/reject/sold/rented actions, flag toggles. Relation managers: **Images** (upload, drag-reorder, set primary) and **Amenities** (attach/detach). Pending-count nav badge. |
| `UserResource` | User | People | Manage role, `is_active` (activate/deactivate quick action), `branch_id`. Password only re-hashed when filled. Admin-only. |
| `InquiryResource` | PropertyInquiry | Leads | View + status workflow (new → contacted → closed). CSV export of the filtered set. New-count nav badge. Agent-scoped. |
| `RequirementResource` | Requirement | Leads | View + status (open → fulfilled). CSV export. Admin-only. |
| `CityResource` | City | Taxonomy | CRUD; **Areas** relation manager. Admin-only. |
| `CategoryResource` | Category | Taxonomy | CRUD. Admin-only. |
| `AmenityResource` | Amenity | Taxonomy | CRUD. Admin-only. |
| `BranchResource` | Branch | Company | CRUD. Admin-only. |
| `TeamResource` | Team | Company | CRUD; photo upload + `socials` key-value editor. Admin-only. |
| `BlogResource` | Blog | Content | Rich-text body, cover-image upload, `published_at` gate. Agent-scoped to own posts. |
| `FaqResource` | Faq | Content | CRUD. Admin-only. |

CSV exports use the shared `App\Support\CsvExporter`, which streams rows
(no in-memory buffering) from a lazy cursor.

## Dashboard widgets (`app/Filament/Widgets/`)

- **StatsOverview** — active listings, pending approvals, new inquiries (7d), total users.
- **ListingsByCityChart** — bar chart, active listings per city (top 10).
- **ListingsByCategoryChart** — doughnut chart, active listings per category.

All three are admin-only (`canView()`), since the figures are marketplace-wide.

## Approval flow

A listing's lifecycle is **pending → active → sold/rented** (or **rejected**).

- **Approve** (`PropertyResource`, row + bulk): calls `Property::markActive()`
  — sets `status = active` and stamps `published_at` once — then dispatches
  `MatchRequirementsToProperty::dispatch($property->id)` so buyers with matching
  open requirements (same transaction_type/category/city, budget band contains the
  price) are notified off the request cycle. This mirrors the API approval path
  exactly, so the side effects never drift.
- **Reject**: opens a form to capture `rejection_reason`, sets `status = rejected`.
- **Mark Sold / Mark Rented**: only from `active`.
- **Flag toggles** (`is_featured`, `is_exclusive`, `is_emerging`, `is_open_house`,
  `is_by_owner`): per-flag set/unset bulk actions, generated from `Property::FLAGS`.

The status field on the edit form is intentionally read-mostly (disabled for
non-admins, hinted toward the actions) so the lifecycle side effects always run
through the actions rather than a raw status edit.

## Admin vs. agent access

Admins bypass every policy/gate via the `Gate::before` hook in
`AuthServiceProvider` (`role === 'admin'` ⇒ allow). The panel layers role rules
on top:

- **Admins** — see and do everything: all resources, all widgets, all moderation
  and flag actions.
- **Agents** — limited view:
  - `PropertyResource::getEloquentQuery()` scopes the table to listings the agent
    **owns** (`user_id`) or is **assigned** (`agent_id`).
  - `InquiryResource::getEloquentQuery()` scopes to enquiries on those listings.
  - `BlogResource::getEloquentQuery()` scopes to the agent's own posts.
  - Moderation actions, flag toggles, agent-reassignment, and the dashboard
    widgets are guarded on `auth()->user()->isAdmin()` and hidden from agents.
  - Admin-only resources (`User`, `City`, `Category`, `Amenity`, `Branch`, `Team`,
    `Faq`, `Requirement`) return `false` from `canViewAny()` for agents, so they
    drop out of navigation entirely.

## Notes / assumptions

- `composer` is not installed in this environment, so files were hand-authored to
  Filament 3 conventions and validated with `php -l` (Filament classes can't be
  autoloaded without `vendor/`). Run `composer install` before serving.
- `FileUpload` fields (property images, team photos, blog covers) store to the
  default disk under `properties/`, `team/`, `blog/`. Wire a public/Cloudinary
  disk as needed; the API resolves `PropertyImage::$url`/`$path` to public URLs.
- `money('NPR')` is used for price columns; adjust the locale/currency formatting
  if a different presentation is desired.
