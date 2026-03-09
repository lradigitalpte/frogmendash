# ROV Inspection Plugin – Pages to Create

The current **ROV Inspections** screen is a **placeholder**: the route and nav work, but no real UI is built yet. Below is the full list of pages/screens to implement (from the roadmap and architecture docs).

---

## Already done (placeholder only)

| Page | Purpose | Status |
|------|---------|--------|
| **RovInspectionPage** | Landing entry under "ROV Inspections" (current empty page) | Exists as shell; can become dashboard or redirect to list |

---

## Phase 1 – Core CRUD

| # | Page / Resource | Type | Purpose |
|---|------------------|------|---------|
| 1 | **ListRovProjects** | Resource list page | Table of all ROV projects (name, client, dates, status). |
| 2 | **CreateRovProject** | Resource create page | Form: name, client, dates, location, description, site map upload. |
| 3 | **EditRovProject** | Resource edit page | Edit project details and site map. |
| 4 | **ViewRovProject** | Resource view page | Read-only project summary; link to "Annotate map" and points. |
| 5 | **Inspection points** | Relation manager (or inline on View/Edit) | Table of points per project: point number, label, severity, defect type (no map yet). |

*Requires: migrations + models (RovProject, InspectionPoint, …), RovProjectResource.*

---

## Phase 2 – Map annotation

| # | Page / Component | Type | Purpose |
|---|------------------|------|---------|
| 6 | **MapAnnotationPage** | Custom Filament page | Full-page (or modal) canvas: load site map image, click to add marker, drag to move, delete. Saves x, y, point_number to `inspection_points`. |
| 7 | **MapMarkerPlotter** | Livewire component | Used by Map Annotation page for click/drag/delete and persistence. |

*Link "Annotate map" from ViewRovProject to MapAnnotationPage.*

---

## Phase 3 – Media and point details

| # | Page / Component | Type | Purpose |
|---|------------------|------|---------|
| 8 | **Media per point** | Relation manager or section on point | Upload videos/images per inspection point; list with thumbnail, duration, uploaded by. |
| 9 | **Point detail form** | Form (edit point) | Defect type, severity, description, recommendations for each point. |

*Can be part of InspectionPoint relation manager or a dedicated edit point page.*

---

## Phase 4 – Reports and sharing

| # | Page / Screen | Type | Purpose |
|---|---------------|------|---------|
| 10 | **Report builder / Edit report** | Filament page or resource page | One report per project: edit title, summary, full report body, status (draft/ready/shared); set share link password/expiry. |
| 11 | **Client report viewer** | Public route + view | Read-only page at e.g. `/reports/{hash}`: site map + markers, click marker → point details + media (video player, images). Optional: Blade + Livewire, not necessarily Filament. |

*Requires: InspectionReport model, ReportGeneratorService (or similar), report access logging.*

---

## Phase 5 – Optional / later

| # | Page / Feature | Purpose |
|---|----------------|---------|
| 12 | **ROV dashboard** | Replace or complement current placeholder with stats (e.g. project count, recent projects). |
| 13 | **PDF export / print** | Button on report builder or client viewer to download/print report. |
| 14 | **Report list** | Optional resource to list all reports (or manage from project view). |

---

## Summary count

- **Resources + standard pages:** RovProjectResource (List, Create, Edit, View) + inspection points (relation or inline) → **5 main admin pages**.
- **Custom pages:** Map Annotation, Report builder/edit → **2 custom Filament pages**.
- **Public:** Client report viewer → **1 public page**.
- **Livewire/components:** MapMarkerPlotter, MediaUploader, ClientReportViewer as needed.

The empty **ROV Inspections** page you see is expected until **ListRovProjects** (and optionally a small dashboard) is implemented; then you can point the main nav to the list or to a simple dashboard that links to it.
