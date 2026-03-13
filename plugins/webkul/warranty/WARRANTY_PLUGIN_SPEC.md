## Warranty Plugin – Functional & Technical Spec

---

### 1. Business Goal

The **Warranty** plugin tracks after‑sales warranty for ROVs and other products sold through the
ERP. When a warrantable product is sold and delivered to a customer, the system should:

- Start a **warranty countdown** (e.g. 24 months from delivery date).
- Know **which exact unit** (serial / asset) is covered, for **which customer**.
- Know **what is covered** (hull, electronics, camera, labour…).
- Answer quickly: “Is this unit under warranty on this date?” and “When does it expire?”.

The plugin should be multi‑tenant (per company) and integrate cleanly with existing modules:
**Products / Inventory, Sales Orders, Deliveries, Invoices, and optional Service/Projects**.

---

### 2. High‑Level Concepts

1. **Warranty Policy** – a template attached to a **product** that describes:
   - How long the warranty lasts.
   - When the warranty starts (delivery, invoice, commissioning).
   - What components / services are covered.

2. **Warranty Instance** – a concrete warranty for **one sold unit** (e.g. one ROV with a serial).
   - Created automatically when a warrantable item is delivered.
   - Contains start/end dates, status, and links to the customer, product, and sales documents.

3. **Warranty Claim / Service Job** (optional later):
   - A maintenance ticket / project task that references a warranty instance to mark work as
     “under warranty”. This can be integrated later with the ROV Inspection / Project plugins.

The first version focuses on **Policies + Instances** and read‑only checks from Sales / Service.

---

### 3. Data Model

#### 3.1 `warranty_policies`

Defines default warranty for a product or product family.

| Column                     | Type                 | Notes |
|----------------------------|----------------------|-------|
| id                         | bigint PK           |       |
| name                       | string              | e.g. "Standard ROV – 24 months" |
| description                | text nullable       | Human description shown to users |
| duration_months            | unsignedSmallInteger| e.g. 12, 24, 36 |
| start_trigger              | string              | `delivery_date`, `invoice_date`, `commissioning_date`, `manual` |
| coverage_json              | json nullable       | Array of strings / tags: `["hull", "electronics", "camera", "labour"]` |
| max_visits_per_year        | unsignedTinyInteger nullable | Optional limit for service visits |
| include_spare_parts        | boolean default false | High‑level flag |
| include_labour             | boolean default false | High‑level flag |
| company_id                 | FK companies nullable | Multi‑tenant scoping |
| created_at / updated_at    | timestamps          |       |

> Implementation note: if the existing `products_products` table already has a simple warranty
> concept, this plugin will **replace or extend** it using a proper relation to `warranty_policies`.

#### 3.2 Product ↔ Policy relationship

We add **one foreign key on the product** record:

- On `products_products` (exact table name to confirm in code):
  - `warranty_policy_id` FK → `warranty_policies.id` (nullable).

This means:

- A product **may have** a default warranty policy.
- Different SKUs can share the same policy (e.g. “All ROV models – 24 months”).

#### 3.3 `warranties` (warranty instances)

Each row represents the warranty for **one sold asset** (e.g. one serial numbered ROV).

| Column                 | Type        | Notes |
|------------------------|------------|-------|
| id                     | bigint PK  |       |
| warranty_policy_id     | FK warranty_policies nullable | Snapshot link to the policy used |
| product_id             | FK products_products | The sold product |
| serial_number          | string nullable | Physical unit identifier; required for ROVs |
| asset_tag              | string nullable | Optional internal asset/CMMS ID |
| customer_id            | FK partners_partners | End customer |
| company_id             | FK companies nullable | Tenant / business unit |
| sales_order_id         | FK sales_orders nullable | Origin sales order (table name to confirm) |
| delivery_id            | FK deliveries nullable | Origin delivery / shipment |
| invoice_id             | FK invoices nullable | Origin invoice if needed |
| start_date             | date       | When warranty countdown starts |
| end_date               | date       | When warranty ends |
| status                 | string     | `draft`, `active`, `expired`, `void` |
| start_trigger          | string     | Copied from policy at creation time |
| duration_months        | unsignedSmallInteger | Copied from policy |
| coverage_snapshot_json | json       | Copied from policy at creation time for audit |
| notes                  | text nullable | Free‑form internal notes |
| created_at / updated_at| timestamps |       |

Indexing:

- Composite index on `(serial_number, product_id, customer_id)`.
- Index on `(customer_id, status, end_date)` for dashboards.

#### 3.4 Optional (later) `warranty_claims`

For v1 we **do not** implement claims; we only track instances and status. A later phase can add:

- `warranty_claims` + link to Projects/Tasks/ROV Inspections.

---

### 4. Lifecycle & Integration with Existing Modules

#### 4.1 Where warranties come from

**Source of truth:** a **delivery / shipment** of a warrantable product to a customer.

1. Product has `warranty_policy_id` set.
2. Sales flow:
   - Sales Order → Delivery (with quantities and, ideally, serials).
3. When Delivery is marked as **Completed**:
   - For each line where `product.warranty_policy_id` is not null:
     - For each **serial number** on that line (or each unit, if serials not used):
       - Create a `warranties` row with:
         - `product_id`, `serial_number`, `customer_id`, `company_id`
         - `warranty_policy_id`, `duration_months`, `start_trigger`
         - `coverage_snapshot_json` ← copy `coverage_json` from policy
         - `sales_order_id`, `delivery_id`, `invoice_id` (if already known)
         - `start_date`:
           - If `start_trigger = delivery_date` → use delivery date
           - If `invoice_date` → wait until invoice; see below
           - If `commissioning_date` → create as `draft` until commissioning set
         - `end_date = start_date + duration_months`
         - `status = active` (or `draft` if start date not yet known).

> If serial numbers are **not** available, fallback is one warranty per line item with a simple
> textual reference (e.g. “Qty 3 – not serialised”). For ROVs we expect serials, so the plugin
> will be designed with serials as the recommended path.

#### 4.2 Start date rules (per policy)

`start_trigger` controls how start_date is derived:

- `delivery_date` – use the delivery completion date (default for hardware like ROVs).
- `invoice_date` – wait until an invoice is posted for the delivery; start when invoice date set.
- `commissioning_date` – create warranty in `draft` state; start_date is set manually when
  commissioning/installation is complete.
- `manual` – created but no automatic start; user sets start/end manually.

If the trigger date is not yet available, the warranty is created as:

- `status = draft`, `start_date = null`, `end_date = null`.
- A **background job** updates draft warranties when the trigger date becomes known.

#### 4.3 Expiry & status transitions

- Nightly scheduled task:
  - `status = active` and `end_date < today` → set to `expired`.
- Manual actions:
  - User can **void** a warranty (e.g. due to non‑payment, misuse).
  - User can **extend** end_date (e.g. commercial gesture).

Status values:

- `draft` – created, but not yet started.
- `active` – within `[start_date, end_date]`.
- `expired` – end_date in the past.
- `void` – cancelled for other reasons.

---

### 5. User Interface (Admin Panel)

#### 5.1 Navigation

Under the existing **Sales / Service** group (exact label to match your panel):

```text
Sales / Service
├── Warranties        (/admin/warranty/warranties)
└── Warranty Policies (/admin/warranty/policies)
```

Alternatively, Policies can be purely behind the scenes (edited only via Product form) to keep
the sidebar smaller. We will keep the resource classes but can hide them from the nav if needed.

#### 5.2 Product Form – Warranty Policy section

On the existing **Product** Filament resource:

- Add a `Warranty` section:
  - Select `warranty_policy_id` (dropdown of active policies).
  - Read‑only preview:
    - Duration: `24 months`
    - Start: `Delivery date`
    - Coverage: `Hull, Electronics, Camera`

This is **Phase 1 critical** – nothing else changes for Sales once it’s there.

#### 5.3 Warranty Policies Resource

`WarrantyPolicyResource`:

- Table:
  - Name, Duration, Start Trigger, Coverage tags, Company.
- Form:
  - Basic information (name, description).
  - Duration (months).
  - Start trigger (select).
  - Coverage (multi‑select tags, map to JSON).
  - High‑level booleans: include_spare_parts, include_labour.

This resource is mainly for system admins / super users.

#### 5.4 Warranties Resource

`WarrantyResource`:

- **List view** (Filters: Company, Customer, Product, Status, Expires in X days):
  - Columns: Serial, Product, Customer, Start, End, Status badge, Days remaining.
- **View page**:
  - Header:
    - Product, Serial / Asset, Customer, Status.
  - Body sections:
    - Dates (start, end, duration, remaining).
    - Coverage snapshot (list of components).
    - Links to source docs (order, delivery, invoice).
    - Notes.
  - Actions:
    - Change status (void / reinstate if allowed).
    - Extend duration (adds months / sets new end date).

#### 5.5 Customer / Partner – “Assets & Warranties” tab (optional Phase 2)

Within the existing **Customer** resource:

- New tab: `Assets & Warranties`.
- Table:
  - Product, Serial, Start, End, Status.
  - Icon if **active today**.
- Clicking a row opens the `WarrantyResource` view.

---

### 6. Integration Points (Technical)

#### 6.1 Hooking into Deliveries

We will create a small service class, e.g. `WarrantyGenerator`, and call it from one place:

```php
WarrantyGenerator::forDelivery($delivery)->generate();
```

Where:

- `$delivery` is an Eloquent model for the existing delivery/shipment document.
- The generator:
  - Loops over lines.
  - Checks `product->warranty_policy_id`.
  - Resolves serial numbers on the line (or generates placeholder if not used).
  - Creates/updates `warranties` accordingly.

Integration options (to be confirmed in actual code):

1. **Observer on Delivery model** – listen for `status = completed`.
2. **Explicit call in Delivery completion service** – minimal, more explicit.

We will favour an explicit service call wired into the same place that already handles inventory
movements for deliveries.

#### 6.2 Serial Number Handling

Assumptions:

- The ERP already tracks serial/lot numbers in Inventory.
- Delivery lines expose either:
  - `serial_numbers` collection, or
  - one serial per line, or
  - a linking table.

In the spec we keep it generic: `serial_number` is a string. In implementation we will:

- Read from the existing serial/lot relation when available.
- If not available, we allow creating warranties without a serial, but **ROV products will be
  configured to require serials**.

#### 6.3 Multi‑company / Multi‑tenant

- Policies can be **global** (`company_id null`) or per company.
- Warranties always store `company_id` from the sales / delivery company.
- All Eloquent queries in resources add the existing `CompanyScope` (similar to ROV plugin).

---

### 7. Implementation Phases

#### Phase 1 – Data Model & Admin Basics

- [ ] Migrations:
  - [ ] Create `warranty_policies` table.
  - [ ] Create `warranties` table.
  - [ ] Add `warranty_policy_id` FK to `products_products`.
- [ ] Eloquent models:
  - [ ] `WarrantyPolicy` with `company` and `products()` relationships.
  - [ ] `Warranty` with `policy`, `product`, `customer`, `company`, source docs.
- [ ] Filament resources:
  - [ ] `WarrantyPolicyResource` (list + create/edit).
  - [ ] `WarrantyResource` (read‑only list + view; no auto‑generation yet).
- [ ] Product resource:
  - [ ] Add **Warranty Policy** section to the product form.

#### Phase 2 – Auto‑Generation from Deliveries

- [ ] Implement `WarrantyGenerator` service:
  - [ ] Given a delivery, generate/update warranties according to policies.
  - [ ] Handle start_trigger logic (delivery/invoice/commissioning/manual).
- [ ] Wire `WarrantyGenerator` into delivery completion logic.
- [ ] Background scheduler:
  - [ ] Job to set `status = expired` when `end_date` passes.
  - [ ] Job to start `draft` warranties when trigger date becomes known (e.g. invoice posted).
- [ ] Update `WarrantyResource`:
  - [ ] Simple actions: extend end date, void/unvoid.

#### Phase 3 – Customer & Service Integration (Optional, after go‑live)

- [ ] Customer / Partner resource:
  - [ ] Add `Assets & Warranties` tab.
- [ ] Service / Project / ROV Inspection integration:
  - [ ] On service ticket / project, allow linking a `warranty_id`.
  - [ ] Show “Under warranty” badges and end date.
  - [ ] (Later) track `warranty_claims` and link them to jobs.

---

### 8. Non‑Goals (v1)

- No complex SLA engine (response time, resolution time).
- No automatic pricing rules (e.g. free labour, discounted parts) – those can be added later in
  service/maintenance modules.
- No contractual documents or PDF templates beyond simple Filament views.

v1 focuses on:

- Correct, auditable creation of warranty records from real sales/deliveries.
- Clear visibility for sales/support: **who owns what**, **until when**, and **what is covered**.

