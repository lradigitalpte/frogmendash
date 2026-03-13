# ROV Inspection Plugin – Revised Full Spec
*Based on reference screenshots from Planys PAD system (January 2026)*

---

## 1. Overview

The ROV Inspection plugin allows companies to manage underwater and visual inspection projects.
Each project covers a physical site (jetty, pontoon, pile cluster…) and contains multiple named
**structures**. Each structure has its own engineering diagram on which inspection observations
(pins) are plotted. Media (videos/images) and conclusions are attached per project.

---

## 2. Data Model (Revised)

### 2.1 `rov_projects` (already exists — extend)
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| name | string | e.g. "Frogmen Technologies – Pile Inspection" |
| description | text | |
| location | string | Human-readable location |
| latitude | decimal(10,7) | GPS for satellite map pin |
| longitude | decimal(10,7) | GPS for satellite map pin |
| plan_view_path | string | Top-down CAD/engineering drawing (shown in modal) |
| status | string | draft / in_progress / completed / archived |
| start_date | date | |
| end_date | date | |
| company_id | FK | |
| customer_id | FK partners_partners | Client company |
| creator_id | FK users | |

### 2.2 `project_structures` (NEW)
One project has **many structures** (Dolphin_West, PILE_1, PILE_2, Mooring_Pile_1…).
Each structure has its own annotatable diagram.

| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| rov_project_id | FK rov_projects | |
| name | string | e.g. "PILE_1", "Dolphin_West" |
| description | text | |
| diagram_path | string | Engineering drawing/elevation image (the map to pin on) |
| photo_path | string | Surface photo shown in Inspection Image gallery |
| sort | int | Display order |
| timestamps | | |

### 2.3 `inspection_views` (NEW – replaces old `inspection_points` top level)
One structure can have multiple inspection views (e.g., ROV view vs Diver view, or
different pass sessions). Shown as sub-tabs: "Dolphin_West_ROV", "Dolphin_West_Diver".

| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| structure_id | FK project_structures | |
| name | string | e.g. "VISUAL_1", "VISUAL_1_D", "Dolphin_West_ROV" |
| view_type | string | rov / diver |
| timestamps | | |

### 2.4 `inspection_points` (REVISED – pins on a view's diagram)
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| inspection_view_id | FK inspection_views | |
| observation_id | string | O1, O2, O3… (display label) |
| label | string | Short label (auto-generated or custom) |
| x_coordinate | float | % position on diagram image |
| y_coordinate | float | % position on diagram image |
| severity | string | major / moderate / minor |
| description | string | Corrosion, Marine Growth, Surface Deformation… |
| dive_location | string | Plank A1, Pile 1A, Pile 1B… |
| depth_m | decimal | Water depth at observation |
| dimension_mm | string | e.g. "67.00 x 28.18" |
| recommendations | text | |
| timestamps | | |

### 2.5 `inspection_media` (REVISED)
Videos and images per structure. Shown in "Inspection Data" tab as the full gallery.
**Each media file can also be linked to one or more inspection points** — this is the core UX
loop: a user places a pin on the diagram, then attaches the actual ROV video or photo of that
specific spot so viewers can click the pin and watch/see it immediately.

| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| structure_id | FK project_structures | Belongs to a structure (not a point) |
| inspection_point_id | FK inspection_points nullable | Linked observation pin (optional) |
| media_type | string | video / image |
| file_name | string | e.g. "Dive_PILE_1A" (display label) |
| file_path | string | Storage path |
| thumbnail_path | string | Auto-generated for video, or the image itself |
| mime_type | string | |
| file_size | bigint | bytes |
| duration | int | seconds (for video) |
| uploaded_by | FK users | |
| softDeletes | | |
| timestamps | | |

> **Why a direct FK and not a pivot?**
> Each media file belongs to exactly ONE structure (logical container) but can optionally be
> pinned to ONE observation point. A single ROV dive video is one file — it may show several
> defects, but is linked to its primary observation. If a point has multiple angles, upload
> multiple media files all pointing to the same `inspection_point_id`.
> This keeps queries simple: `$point->media` loads all files for that pin.

### 2.6 `inspection_reports` (already exists — extend)
| Column | Type | Notes |
|---|---|---|
| id | bigint | PK |
| rov_project_id | FK rov_projects | |
| title | string | |
| summary | text | Executive summary |
| full_report | longText | Rich text body |
| conclusions | text | Auto-generated or manual |
| recommendations | text | |
| status | string | draft / final / shared |
| shared_link_hash | string | UUID for public link |
| shared_link_password | string | Optional |
| shared_link_expires_at | timestamp | |
| client_can_download | bool | Allow PDF download |
| client_can_print | bool | Allow print |
| shared_date | timestamp | |
| shared_by | FK users | |
| timestamps | | |

---

## 3. Navigation / Pages (Admin Panel)

```
ROV Inspections (nav group)
├── Overview              /admin/rov-inspections
├── Inspection Projects   /admin/rov-inspection/projects
└── Reports               /admin/rov-inspection/reports
```

### Project sub-navigation (top tabs):
```
[Overview] [Structures] [Observations] [Inspection Data] [Reports] [Edit]
```

---

## 4. Admin Pages Detail

### 4.1 List Projects
- Table: name, client, location, status badge, structure count, date range
- Preset tabs: All / In Progress / Completed / Archived
- Create button

### 4.2 Create / Edit Project
**Form sections:**
- **General**: name, description, client (select partner), company
- **Site Location**: location text, latitude, longitude (for satellite map)
- **Plan View**: upload top-down CAD drawing (shown in Plan View modal on client report)
- **Dates & Status**: start_date, end_date, status

### 4.3 View Project
Infolist showing project info + quick-access cards to sub-pages.

### 4.4 Manage Structures (`/{record}/structures`)
Table: structure name, photo thumbnail, diagram thumbnail, observation count, media count.

**Create/Edit Structure form:**
- name
- description
- photo_path (surface photo shown in gallery tab)
- diagram_path (elevation/cross-section drawing — the annotatable map)

### 4.5 Annotate Diagram (`/admin/rov-inspection/map?structure=X&view=Y`)
Interactive canvas per structure + inspection view:
- Load structure's `diagram_path` as background image
- Select which `inspection_view` you are annotating (ROV / Diver, or create one)
- Click on the diagram → places a numbered pin → opens a slide-over form:
  - observation_id (auto-filled: O1, O2…)
  - description (Corrosion, Marine Growth, Surface Deformation…)
  - dive_location (free text)
  - depth_m, dimension_mm
  - severity (Major / Moderate / Minor) — changes pin color
  - **Attach media**: multi-file picker from already-uploaded media for this structure,
    OR upload new files directly. Selected files get `inspection_point_id` set to this pin.
- Pins colored by severity: Red=Major, Orange=Moderate, Yellow=Minor
- Click existing pin → slide-over shows details + attached media (thumbnail/video preview)
- Side panel: scrollable observation table; click row to highlight that pin on diagram

### 4.6 Manage Observations (`/{record}/observations`)
- Select structure → select inspection view (ROV/Diver)
- Table: O1 | Description | Dive Location | Depth | Dimension | Severity badge | Media count | Actions
- Expand row → shows attached media thumbnails (click to preview video/image)
- Add / Edit / Delete observations
- "Attach Media" button per row (links existing uploaded files to the point)
- Link to map annotation page

### 4.7 Manage Inspection Data (`/{record}/media`)
- Upload videos and images; assign to a structure
- Optionally link a media file to a specific observation point via dropdown
- Gallery grid: thumbnail | label | structure | linked observation (if any) | size | actions
- Preview button → plays video or shows image in a modal

### 4.8 Manage Reports (`/{record}/reports`)
- Create report (rich text, executive summary, conclusions, recommendations)
- Generate share link (UUID)
- View access logs

---

## 5. Client Report Pages (Public – no login)

URL: `/report/{hash}`

### Tab 1: Home
- Satellite map (embedded Google Maps or Leaflet) centered on project GPS
- Company logo, project title, "Download Executive Report" button

### Tab 2: Inspection Image
- Grid of structure surface photos with labels (Dolphin_West, PILE_1…)

### Tab 3: Inspection Map
- Tabs for each structure (PILE_1, PILE_2…)
- Sub-tabs for each inspection view within structure (ROV, Diver)
- Left panel: structure diagram image with numbered colored pins
- Right panel: defect table (Defect ID | Description | Location | Depth | Download ↓)
- **Clicking a pin OR a table row** opens an inline media panel below (or a modal):
  - Shows the attached video/images for that observation
  - Toggle between Videos and Images if both exist
  - HTML5 video player or full-size image viewer
- Download icon per row → downloads the attached media file(s) for that observation

### Tab 4: Observations
- Dropdown to select structure, then inspection view
- Diagram with pins on the left (same as Inspection Map)
- Right: full observation table per view
- **Clicking any row reveals its media inline** (video player or image) below the table
  — the user sees "this is point O3, here is the actual ROV video of this location"
- Toggle Videos / Images (shows only video-linked or image-linked observations)

### Tab 5: Inspection Data
- Full media gallery for the project grouped by structure
- Video thumbnails with play button, image thumbnails
- Labeled (Dive PILE_1A, Dive PILE_1B…)
- Click to play video or view image in a fullscreen lightbox
- Each item shows which observation pin it is linked to (if any): "Linked to O3"

### Tab 6: Conclusions
- Auto-generated from observation counts:
  - Red (Major): X observations
  - Orange (Moderate): Y observations
  - Yellow (Minor): Z observations
- Free-text conclusion bullets from report record
- Overall health assessment

### Plan View (modal, accessible from any tab)
- Full-screen modal showing the plan_view CAD drawing
- Close button

---

## 6. Severity Color System
| Severity | Color | Pin | Badge |
|---|---|---|---|
| major | Red (#ef4444) | 🔴 | danger |
| moderate | Orange (#f97316) | 🟠 | warning |
| minor | Yellow (#eab308) | 🟡 | info |
| none / unknown | Gray (#6b7280) | ⚫ | gray |

---

## 7. Implementation Phases

### Phase 1 – Data Model (migrations + models)
- [ ] New migration: `project_structures` (name, diagram_path, photo_path, sort)
- [ ] New migration: `inspection_views` (structure_id, name, view_type: rov/diver)
- [ ] New migration: alter `rov_projects` → add latitude, longitude, plan_view_path
- [ ] New migration: drop + recreate `inspection_points`
      → FK: `inspection_view_id` (was `rov_project_id`)
      → add: observation_id, severity, dive_location, depth_m, dimension_mm
- [ ] New migration: alter `inspection_media`
      → add `structure_id` FK
      → add `inspection_point_id` nullable FK  ← **the pin→media link**
      → add `thumbnail_path`, `duration`, `uploaded_by`
- [ ] Models: `ProjectStructure`, `InspectionView`, update `InspectionPoint`, update `InspectionMedia`
- [ ] Relationships:
      - `RovProject` hasMany `ProjectStructure`
      - `ProjectStructure` hasMany `InspectionView`, hasMany `InspectionMedia`
      - `InspectionView` hasMany `InspectionPoint`
      - `InspectionPoint` hasMany `InspectionMedia`  ← **click pin → load media**
      - `InspectionMedia` belongsTo `InspectionPoint` (nullable)

### Phase 2 – Admin Resources
- [ ] Manage Structures page inside project (ManageRelatedRecords)
- [ ] Manage Inspection Views per structure
- [ ] Map annotation page: per-structure/per-view, attach media to pins
- [ ] Manage Observations: expand row shows linked media
- [ ] Manage Media: upload with structure + optional point link

### Phase 3 – Client Report Viewer
- [ ] Multi-tab public page: Home | Inspection Image | Inspection Map | Observations | Inspection Data | Conclusions
- [ ] Tab 1 Home: Leaflet satellite map + GPS pin
- [ ] Tab 3 Inspection Map: diagram pins + click pin → inline video/image player
- [ ] Tab 4 Observations: same pin-to-media behavior
- [ ] Tab 5 Inspection Data: full gallery with play/lightbox
- [ ] Plan View modal (CAD drawing fullscreen)
- [ ] Tab 6 Conclusions: severity counts + free-text bullets

---

## 8. Pin → Media UX Flow (Core Loop)

This is the heart of the system. Here is the exact flow end-to-end:

**Admin side (during data entry):**
1. Upload a ROV video for a structure (e.g., "Dive_PILE_1A.mp4") — it gets stored in
   `inspection_media` linked to `structure_id`. No observation link yet.
2. Open the Annotate Diagram page for that structure.
3. Click on the diagram where the defect is → a pin is created (O3, orange/moderate).
4. In the pin form, select "Attach Media" → pick "Dive_PILE_1A.mp4" from the uploaded files.
   → `inspection_media.inspection_point_id` is set to the new pin's ID.
5. Save. The pin now has a video attached.

**Client side (reading the report):**
1. Client opens the Inspection Map tab.
2. They see the diagram with numbered pins (O1 red, O3 orange, O7 yellow…).
3. They click pin O3 (or click row O3 in the table on the right).
4. An inline panel expands (or a modal opens) showing:
   - Observation details (description: "Marine Growth", depth: 3m…)
   - Video player streaming "Dive_PILE_1A.mp4"
   - If multiple media files linked to O3 → thumbnail strip to switch between them
5. Client can optionally download the video file.

**What if a media file is not linked to any pin?**
- It still appears in the "Inspection Data" tab (full gallery).
- It will NOT appear in the pin pop-up — only linked media shows there.

---

## 9. Key Technical Notes

1. **Diagram annotation**: Alpine.js canvas per `inspection_view_id`. Load the structure's
   `diagram_path` as background. Pins stored with x/y as percentages so they scale on any screen.

2. **Pin → media link**: `inspection_media.inspection_point_id` is a nullable FK.
   `InspectionPoint::media()` = `hasMany(InspectionMedia::class)`. On the client report,
   eager-load: `$view->points()->with('media')->get()`.

3. **Inline media player**: Alpine.js `x-show` panel. When pin or row clicked, set
   `activePoint = point` in Alpine data. Panel renders `@foreach($activePoint->media)` with
   HTML5 `<video>` or `<img>` depending on `media_type`.

4. **GPS/Satellite map**: Leaflet.js with OpenStreetMap tiles (free, no API key). Drop a
   single pin at project lat/lng with a popup showing project name.

5. **Severity pin colors**: Red (#ef4444) = Major, Orange (#f97316) = Moderate,
   Yellow (#eab308) = Minor. CSS class driven, not inline styles.

6. **Plan View modal**: Alpine.js `x-dialog` or native `<dialog>` element showing project's
   `plan_view_path` image in full screen with zoom support.

7. **Video storage**: `storage/app/public/rov-inspection/media/`. Symlink via `php artisan storage:link`.
   Thumbnails auto-generated via FFmpeg (if available) or a placeholder.

8. **Executive Report PDF**: Use Barryvdh/DomPDF (already in Laravel ecosystem). Generate from
   a Blade view template. Include: project info, structure list, observation table per structure,
   conclusions.

9. **Observation IDs**: Sequential per inspection_view (O1, O2, O3…). Auto-generate in model
   boot: `static::creating(fn($m) => $m->observation_id ??= 'O'.($m->view->points()->count()+1))`.
