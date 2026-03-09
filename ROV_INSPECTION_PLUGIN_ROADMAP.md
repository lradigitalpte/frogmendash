# ROV Inspection Plugin – Roadmap

**Plugin name:** ROV Inspection (`rov-inspection` / `webkul.rov-inspection`)  
**Status:** Planning → Implementation  
**Source docs:**  
- [ROV_INSPECTION_ARCHITECTURE_DECISION.md](ROV_INSPECTION_ARCHITECTURE_DECISION.md) – why a separate plugin, data model, DB design  
- [PROJECT_MODULE_INSPECTION_PLAN.md](PROJECT_MODULE_INSPECTION_PLAN.md) – workflows, features, UI concepts, milestones  

This roadmap turns that design into a **installable plugin** (same pattern as Projects, Contacts, Invoices). **UI stays consistent** with the rest of FrogmenDash (Filament, same design system, multi-tenant).

---

## 1. Why a Plugin + Design Principles

- **Separate plugin** (not extending Projects): dedicated models, no task/milestone/timesheet clutter, clear “ROV Inspections” menu, per-company enable/disable via Settings → Plugins.
- **Same stack:** Laravel 11, Filament v4, Livewire, existing auth and multi-tenancy (`company_id`).
- **UI design system:** All admin and client-facing screens use the same Filament theme, components, spacing, typography, and navigation patterns as the rest of the app. No one-off “ROV-only” UI framework.
- **Core module:** Treated as a first-class, long-term core feature: proper tests, docs, and incremental delivery.

---

## 2. Plugin Structure (Target)

Align with existing plugins under `plugins/webkul/`:

```
plugins/webkul/rov-inspection/
├── src/
│   ├── RovInspectionServiceProvider.php   # extends PackageServiceProvider
│   ├── RovInspectionPlugin.php            # Filament Plugin (resources, nav)
│   ├── Models/
│   │   ├── RovProject.php
│   │   ├── InspectionPoint.php
│   │   ├── InspectionMedia.php
│   │   ├── InspectionReport.php
│   │   └── ReportAccessLog.php
│   ├── Filament/
│   │   ├── Resources/
│   │   │   ├── RovProjectResource.php (+ Pages)
│   │   │   ├── InspectionPointResource.php (or relation manager)
│   │   │   └── InspectionReportResource.php (+ Pages)
│   │   ├── Pages/
│   │   │   ├── MapAnnotationPage.php      # interactive map
│   │   │   └── ReportBuilderPage.php
│   │   └── Clusters/                       # if needed (e.g. “ROV Inspections” cluster)
│   ├── Livewire/
│   │   ├── MapMarkerPlotter.php
│   │   ├── MediaUploader.php
│   │   └── ClientReportViewer.php
│   ├── Http/
│   │   └── Controllers/                   # public report route /reports/{hash}
│   └── Services/
│       ├── MapService.php
│       ├── ReportGeneratorService.php
│       └── MediaService.php
├── database/
│   └── migrations/
│       ├── create_rov_projects_table
│       ├── create_inspection_points_table
│       ├── create_inspection_media_table
│       ├── create_inspection_reports_table
│       └── create_report_access_logs_table
├── resources/
│   └── lang/ (or lang/en/...)
├── routes/
│   └── web.php                            # public report viewer route
├── composer.json
└── package.json (if any frontend assets)
```

- **Navigation:** One group “ROV Inspections” (or “Inspections”) in admin sidebar; label/translation key added to `config/plugin-navigation-groups.php` for `webkul.rov-inspection` so per-company plugin filtering works.

---

## 3. UI Design System Consistency

- **Admin UI:** Filament resources only. Same form components (`TextInput`, `Select`, `DatePicker`, `FileUpload`, `Repeater`, etc.), same table patterns, same filters and bulk actions. Reuse existing patterns from Projects/Contacts/Invoices (e.g. company/client selectors, status badges).
- **Map annotation:** Implement as a Filament custom page (or full-page Livewire component) that fits inside the admin layout (same header, sidebar collapse, theme). Use the same color palette and severity colors (e.g. red/yellow/green) as the rest of the app.
- **Client report viewer:** Optional standalone layout for `/reports/{hash}` but still use Filament/Alpine/Livewire where it makes sense; or a minimal Blade view that shares CSS variables and typography so it doesn’t feel like a different product.
- **Icons & labels:** Use existing icon set and `__('...')` translation keys; add keys under `rov-inspection::` or a dedicated lang file so they can be overridden.
- **No new design system:** No separate UI kit, no different spacing/breakpoints. This keeps ROV Inspection feeling like “one of the cores” of the same product.

---

## 4. Roadmap Phases

### Phase 0: Prep (before coding)

- [ ] **0.1** Create `plugins/webkul/rov-inspection/` scaffold (composer package, `RovInspectionServiceProvider`, `RovInspectionPlugin`, register in `bootstrap/providers.php` and plugin manager).
- [ ] **0.2** Add `webkul.rov-inspection` to `config/plugin-navigation-groups.php` with the chosen nav group label(s).
- [ ] **0.3** Confirm DB schema from ROV_INSPECTION_ARCHITECTURE_DECISION.md (table names, FKs: `companies`, `partners_partners`, `users`). No schema change in this phase, only alignment.

**Deliverable:** Plugin appears in Settings → Plugins; can be enabled per company; “ROV Inspections” (or chosen label) appears in sidebar when enabled. No resources yet.

---

### Phase 1: Core data and list/detail (MVP part 1)

- [ ] **1.1** Migrations: `rov_projects`, `inspection_points`, `inspection_media`, `inspection_reports`, `report_access_logs` (match architecture doc).
- [ ] **1.2** Models: `RovProject`, `InspectionPoint`, `InspectionMedia`, `InspectionReport`, `ReportAccessLog` with relations, `company_id` scope (multi-tenancy), soft deletes where specified.
- [ ] **1.3** `RovProjectResource`: CRUD, list table, form (name, client/customer, dates, location, status, site map upload). Use same Select/FileUpload/DatePicker patterns as other plugins.
- [ ] **1.4** Policies + permissions (Filament Shield): e.g. `view_any_rov_project`, `create_rov_project`, etc.
- [ ] **1.5** Basic relation manager or inline for **Inspection Points** on RovProject (table of points: number, label, severity, defect type; no map yet).

**Deliverable:** Admins can create/list/edit ROV projects, attach inspection points (data only). No map UI, no media, no report generation yet.

---

### Phase 2: Site map and map annotation (MVP part 2)

- [ ] **2.1** Site map upload: store in `storage` (e.g. `rov-projects/{id}/site_map`), display in RovProject view/edit.
- [ ] **2.2** **Map annotation page:** Full-page or modal Livewire component “MapMarkerPlotter”: load site map image, click to add point, drag to reposition, delete. Persist `x_coordinate`, `y_coordinate`, `point_number` to `inspection_points`. Same styling as admin (same buttons, modals, colors).
- [ ] **2.3** Link from RovProject detail to “Annotate map” (opens Map Annotation page).
- [ ] **2.4** Optional: `MapService` for coordinate normalization (e.g. scale to image dimensions).

**Deliverable:** Inspector can upload a site map and place numbered markers on it; points sync to DB and show in the inspection points list.

---

### Phase 3: Media and point details (MVP part 3)

- [ ] **3.1** `InspectionMedia` model + migration already done; implement upload flow: per inspection point, upload video/image (and optional document). Store under e.g. `rov-projects/{project_id}/points/{point_id}/`.
- [ ] **3.2** Media list per point (relation manager or section on point edit): file name, type, duration (video), thumbnail, uploaded_by, uploaded_at.
- [ ] **3.3** Point form: defect type, severity, description, recommendations (dropdowns/inputs consistent with existing forms).
- [ ] **3.4** Optional: `MediaService` for thumbnails, validation (max size, types), and secure URLs for playback.

**Deliverable:** Each inspection point can have multiple media files and full defect/severity/recommendation data.

---

### Phase 4: Reports and sharing (MVP part 4)

- [ ] **4.1** `InspectionReport` generation: one report per RovProject; compile from points (summary, findings, conclusions, recommendations). `ReportGeneratorService` (or inline) to build HTML/markdown.
- [ ] **4.2** Report edit page (Filament): title, summary, full report body, status (draft / ready / shared), optional password and expiry for share link.
- [ ] **4.3** Share link: generate `shared_link_hash`, store in DB. Public route `GET /reports/{hash}` (and optional `?password=...`). Use `ReportAccessLog` to log access (ip, timestamp, optional duration).
- [ ] **4.4** **Client report viewer:** Read-only view of report + site map + markers; click marker → show point details and media (video player, image gallery). Reuse same design tokens (colors, typography) as admin.

**Deliverable:** Inspector can generate a report, share a link; client opens link and sees report with interactive map and media.

---

### Phase 5: Polish and production readiness

- [ ] **5.1** Permissions and visibility: ensure only allowed companies see their ROV projects (company scope); client share link respects expiry and password.
- [ ] **5.2** Download/print: optional “Download PDF” and “Print” for client report (e.g. mPDF/DomPDF), respecting “client_can_download” / “client_can_print” if present in schema.
- [ ] **5.3** Search/filter: list RovProjects by status, client, date range; filter points by severity/defect type in report view.
- [ ] **5.4** Tests: feature tests for create project → add points → add media → generate report → share link → access log; unit tests for services if needed.
- [ ] **5.5** Docs: update MULTI_TENANT_AND_PLUGINS_IMPLEMENTATION.md (or a short ROV section) and a brief “ROV Inspection” section in main docs (how to enable, main workflows).

**Deliverable:** ROV Inspection plugin is installable, enableable per company, and safe for production use with consistent UI and multi-tenancy.

---

### Phase 6: Future (post-MVP)

- Video streaming (e.g. range requests for large files), thumbnails, transcoding.
- PDF export improvements, email report link to client.
- Mobile-friendly client viewer or on-site marking (if required).
- Defect classification taxonomy (configurable list).
- Analytics: most viewed points, time spent per report (using `ReportAccessLog`).

---

## 5. Dependencies and Constraints

- **Multi-tenancy:** All ROV data scoped by `company_id`; plugin respects existing “per-company plugin” logic (middleware + nav filtering).
- **Contacts/Partners:** ROV “client” can be `partners_partners` (or Contact if you prefer); must align with architecture doc and existing Partner/Contact usage in the app.
- **Storage:** Local disk first; path layout as in PROJECT_MODULE_INSPECTION_PLAN.md; later S3/CDN if needed.
- **No duplication of “Projects”:** No reuse of `projects_projects` or task/milestone tables; ROV is standalone.

---

## 6. Success Criteria (Summary)

- Plugin **ROV Inspection** is installable and enableable per company like Contacts or Projects.
- **UI design system remains the same:** Filament + existing theme and components throughout.
- **MVP:** Create ROV project → upload site map → plot points on map → attach media and defect data → generate report → share link → client views report with map and media.
- **One of the cores:** Documented, testable, and maintainable as a long-term core module.

---

## 7. File Checklist (Implementation)

| Item | Path / note |
|------|-------------|
| Provider | `plugins/webkul/rov-inspection/src/RovInspectionServiceProvider.php` |
| Filament plugin | `plugins/webkul/rov-inspection/src/RovInspectionPlugin.php` |
| Nav config | Add entry in `config/plugin-navigation-groups.php` for `webkul.rov-inspection` |
| App registration | Register provider in `bootstrap/providers.php` |
| Migrations | All 5 tables under `plugins/webkul/rov-inspection/database/migrations/` |
| Models | RovProject, InspectionPoint, InspectionMedia, InspectionReport, ReportAccessLog |
| Resources | RovProjectResource, InspectionPoint (relation or resource), InspectionReportResource |
| Custom pages | MapAnnotationPage, ReportBuilderPage (or equivalent) |
| Livewire | MapMarkerPlotter, MediaUploader, ClientReportViewer |
| Public route | e.g. `Route::get('/reports/{hash}', ...)` in plugin routes |
| Lang | `rov-inspection::...` or plugin `resources/lang/...` |

Use **ROV_INSPECTION_ARCHITECTURE_DECISION.md** for exact table/column and relationship details, and **PROJECT_MODULE_INSPECTION_PLAN.md** for workflows, feature set, and UI mockups. This roadmap is the single place to track **what to build and in what order** while keeping the UI design system consistent and the plugin structure aligned with the rest of FrogmenDash.
