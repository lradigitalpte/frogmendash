# ROV Inspection Module: Architecture Decision
## Should We Extend Projects or Create a Separate Plugin?

**Document Date:** February 18, 2026  
**Status:** Architecture Analysis & Recommendation

---

## 1. Existing Projects Plugin Analysis

### Current Project Model Structure
**Location:** `plugins/webkul/projects/src/Models/Project.php`

**Current Fields:**
- Basic: `name`, `description`, `tasks_label`, `color`, `visibility`, `sort`
- Planning: `start_date`, `end_date`, `allocated_hours`
- Features: `allow_timesheets`, `allow_milestones`, `allow_task_dependencies`
- Status: `is_active`
- Relations: `partner_id`, `company_id`, `user_id`, `creator_id`, `stage_id`

**Current Relationships:**
```
Project
├── HasMany Tasks
├── HasMany Milestones  
├── HasMany TaskStages
├── BelongsTo Partner
├── BelongsTo Company (multi-tenancy)
├── BelongsTo User (assigned to)
├── BelongsTo Creator
├── BelongsTo Stage (project stage/workflow)
├── BelongsToMany Tags
└── BelongsToMany FavoriteUsers
```

**Current Purpose:** General project management with tasks, milestones, timesheets

**Already Has:**
- ✅ Multi-tenancy (company_id)
- ✅ User assignment
- ✅ Workflow stages
- ✅ Activity logging
- ✅ Soft deletes
- ✅ Filament integration

**Plugin Structure:**
```
plugins/webkul/projects/
├── src/
│   ├── Models/ (Project, Task, Milestone, TaskStage, etc.)
│   ├── Filament/ (Resources, Pages for CRUD)
│   ├── Policies/ (Permissions)
│   ├── ProjectPlugin.php (Main plugin class)
│   └── ProjectServiceProvider.php
├── database/
│   └── migrations/ (11 migration files)
└── resources/
    └── translations/
```

---

## 2. ROV Inspection Unique Requirements

Compare what ROV Inspection needs vs what Projects provides:

| Feature | Projects | ROV Inspection | Different? |
|---------|----------|----------------|-----------|
| Basic Info | ✅ Yes (name, date, description) | ✅ Yes | ❌ Same |
| Client Assignment | Partner ID | Client Contact | ⚠️ Similar |
| Company (Multi-tenant) | ✅ Yes | ✅ Yes | ❌ Same |
| Site Map Upload | ❌ No | ✅ YES | ✅ **New** |
| Location Markers | ❌ No | ✅ YES | ✅ **New** |
| Media (Video/Images) | ❌ No | ✅ YES | ✅ **New** |
| Defect Classification | ❌ No | ✅ YES | ✅ **New** |
| Severity Levels | ❌ No | ✅ YES | ✅ **New** |
| Map Annotation UI | ❌ No | ✅ YES | ✅ **New** |
| Report Generation | ❌ No | ✅ YES | ✅ **New** |
| Client Portal Share | ❌ No | ✅ YES | ✅ **New** |
| Video Streaming | ❌ No | ✅ YES | ✅ **New** |
| Access Logs | ❌ No | ✅ YES | ✅ **New** |
| Tasks/Milestones | ✅ Built-in | ❌ Not needed | ✅ **Overkill** |
| Timesheets | ✅ Built-in | ❌ Not needed | ✅ **Overkill** |
| Task Dependencies | ✅ Built-in | ❌ Not needed | ✅ **Overkill** |

---

## 3. Two Architecture Approaches

### OPTION A: Extend Existing Projects Plugin ❌ NOT RECOMMENDED

**Approach:** Add ROV inspection fields to Project model

```
projects_projects table (AFTER adding ROV fields):
├── Standard project fields (name, dates, etc.) ✅
├── site_map_image_path ← NEW
├── site_map_url ← NEW
├── inspection_type (general_project | rov_inspection) ← NEW ENUM
├── allow_timesheets ← ALWAYS REQUIRED (PROJECT MODEL)
├── allow_milestones ← ALWAYS REQUIRED (PROJECT MODEL)
├── allow_task_dependencies ← ALWAYS REQUIRED (PROJECT MODEL)
├── tasks relationship ← NOT USED FOR ROV
├── milestones relationship ← NOT USED FOR ROV
└── taskStages relationship ← NOT USED FOR ROV
```

**Pros:**
- Reuse multi-tenancy setup (company_id)
- Reuse user assignment logic
- Faster initial setup
- Single navigation entry

**Cons:**
- ❌ **Bloated project model** - Model has ROV fields + unused task/milestone logic
- ❌ **UI confusion** - Admin form shows "Allow Timesheets?", "Allow Milestones?" for inspection projects (irrelevant)
- ❌ **Database waste** - Every ROV project stores unused task_stages relationship
- ❌ **Hard to maintain** - Filament Resource needs conditional logic: "Show X fields if ROV, show Y fields if general project"
- ❌ **Pollutes admin navigation** - Projects menu will serve dual purpose
- ❌ **Hard filtering** - Must always filter by inspection_type enum
- ❌ **Hard to extend** - Next time you add new ROV feature, you're modifying existing project table again

**Code Example of Problem:**
```php
// In ProjectResource (Filament)
protected function getFormSchema(): array
{
    return [
        TextInput::make('name'),
        ...

        // Confusing: Why show this for ROV inspections?
        Toggle::make('allow_timesheets')
            ->label('Allow Timesheets?')
            ->visible(fn ($record) => $record?->inspection_type === 'general'),

        // More confusion...
        Toggle::make('allow_milestones')
            ->label('Allow Milestones?')
            ->visible(fn ($record) => $record?->inspection_type === 'general'),

        // These are ROV-specific...
        FileUpload::make('site_map_image')
            ->visible(fn ($record) => $record?->inspection_type === 'rov_inspection'),
        ];
}

// Foreign key pollution...
public function milestones()  // NOT USED FOR ROV PROJECTS
{
    return $this->hasMany(Milestone::class);
}

public function tasks()  // NOT USED FOR ROV PROJECTS
{
    return $this->hasMany(Task::class);
}
```

---

### OPTION B: Create Separate ROV Inspection Plugin ✅ RECOMMENDED

**Approach:** Create `plugins/webkul/rov_inspection/` as standalone plugin

```
RovInspection Plugin Structure:
├── Models/
│   ├── RovProject (minimal, focused)
│   ├── InspectionPoint
│   ├── InspectionMedia
│   ├── InspectionReport
│   └── ReportAccessLog
│
├── Filament/
│   ├── Resources/
│   │   ├── RovProjectResource (dedicated CRUD)
│   │   ├── InspectionPointResource
│   │   ├── InspectionMediaResource
│   │   └── ReportResource
│   │
│   └── Pages/
│       ├── MapAnnotation (interactive canvas)
│       ├── ReportBuilder (internal view)
│       └── ClientReportViewer (public read-only)
│
├── Livewire/
│   ├── MapMarkerPlotter.php (SVG canvas + click handling)
│   ├── MediaUploader.php (drag-drop videos/images)
│   ├── ReportPreview.php
│   └── ClientViewer.php
│
├── Database/
│   └── Migrations/
│       ├── create_rov_projects_table
│       ├── create_inspection_points_table
│       ├── create_inspection_media_table
│       ├── create_inspection_reports_table
│       └── create_report_access_logs_table
│
└── Services/
    ├── MapService (plot points, calculate X,Y coords)
    ├── ReportGeneratorService (compile report from points)
    ├── VideoStreamingService (serve videos securely)
    └── MediaService (handle uploads, transcoding)
```

**Pros:**
- ✅ **Clean separation of concerns** - Each plugin has single responsibility
- ✅ **Focused models** - RovProject only has ROV-specific fields
- ✅ **Simple Filament forms** - No conditional visibility logic, no "unused" fields
- ✅ **Dedicated UI** - "ROV Inspections" menu separate from "Projects"
- ✅ **Easy to maintain** - Add new feature? Just add to RovProject, don't touch general projects
- ✅ **Scalable** - Next time add new inspection type (drone_inspection, underwater_asset_inspections)? Just create another plugin
- ✅ **Professional separation** - Client can purchase/use just ROV module, not forced to use general projects
- ✅ **No database bloat** - No unused foreign keys, no extra columns
- ✅ **Reuse infrastructure** - Still inherit multi-tenancy from company_id
- ✅ **Custom workflows** - Can define unique report types, status enums, etc.

**Code Example of Clarity:**
```php
// RovProject model - ONLY what it needs
class RovProject extends Model
{
    protected $table = 'rov_projects';
    
    protected $fillable = [
        'name',
        'client_id',
        'description',
        'location',
        'start_date',
        'end_date',
        'status',
        'site_map_path',
        'site_map_url',
        'company_id',
        'created_by',
    ];
    
    public function company() { return $this->belongsTo(Company::class); }
    public function client() { return $this->belongsTo(Contact::class); }
    public function creator() { return $this->belongsTo(User::class); }
    public function inspectionPoints() { return $this->hasMany(InspectionPoint::class); }
    public function report() { return $this->hasOne(InspectionReport::class); }
}

// Filament Resource - CLEAN form, no conditionals needed
class RovProjectResource extends Resource
{
    protected static ?string $model = RovProject::class;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->required(),
            Textarea::make('description'),
            DatePicker::make('start_date'),
            DatePicker::make('end_date'),
            FileUpload::make('site_map_image')->image(),
            Select::make('client_id')->relationship('client', 'name'),
            // That's it. Simple. Clear. No confusion.
        ]);
    }
}

// Navigation: Separate "ROV Inspections" in admin menu
NavigationGroup::make()
    ->label(__('ROV Inspections'))
    ->icon('icon-rov'),
```

---

## 4. Comparison Table

| Aspect | Extend Projects | Separate Plugin |
|--------|-----------------|-----------------|
| **Code Clarity** | ⚠️ Confusing (dual purpose) | ✅ Crystal clear (single purpose) |
| **Model Complexity** | ⚠️ High (18+ fields) | ✅ Low (8-10 fields) |
| **Filament UI** | ⚠️ Conditional fields | ✅ Simple, no conditionals |
| **Database Size** | ⚠️ Bloated (unused cols) | ✅ Optimized |
| **Maintenance** | ⚠️ Hard (modify existing) | ✅ Easy (add new) |
| **Independence** | ❌ Coupled | ✅ Loosely coupled |
| **Reusability** | ❌ Mixed concerns | ✅ Pure module |
| **Scalability** | ⚠️ Hard to extend types | ✅ Pattern for new types |
| **Testing** | ⚠️ Complex (many branches) | ✅ Focused (fewer branches) |
| **Development Speed** | ✅ Slightly faster | ⏱️ Slightly slower |
| **Long-term Cost** | ❌ Higher maintenance | ✅ Lower maintenance |

---

## 5. RECOMMENDATION: Create Separate Plugin ⭐

### Why:

1. **ROV Inspections is a completely different workflow** than general project management
   - Projects = task management, timesheets, milestones
   - ROV = site maps, location markers, media attachments, client reports

2. **The existting Projects model has zero overlap in features**
   - Tasks not needed (no subtasks in ROV workflow)
   - Milestones not needed (single deliverable = report)
   - Timesheets not needed (inspectors don't log hours)
   - Task dependencies not needed

3. **Future expansion opportunity**
   - Once you build ROV_Inspection plugin well, you can create:
     - `rov_asset_inspections` (for recurring inspections)
     - `drone_inspections` (similar workflow, different media)
     - `quality_audits` (same map-marking pattern)
   - All follow the same plugin pattern

4. **Frogmen's business model benefits**
   - ROV users might not use general Projects
   - Can offer ROV module as add-on with extra cost
   - Don't force overhead on customers who only need ROV

5. **Cleaner codebase**
   - No conditional rendering in Filament
   - No enum checks scattered through code
   - New developer can understand purpose instantly

---

## 6. Complete Relationship Structure (ALL LINKS)

### ER Diagram:

```
┌─────────────────────────────────────────────────────────────┐
│                         RovProject                          │
│                      (Master Record)                        │
├─────────────────────────────────────────────────────────────┤
│ id, name, location, status, site_map_path                  │
│ start_date, end_date, description                          │
│                                                              │
│ company_id ────→ companies table (YOUR Company)            │
│ customer_id ────→ partners_partners table (CLIENT)         │
│ created_by ────→ users table (Inspector)                   │
└─────────────────────────────────────────────────────────────┘
                            │
                            │ HasMany
                            ▼
┌──────────────────────────────────────────────────────────────┐
│                    InspectionPoint                           │
│                  (Numbered Locations)                        │
├──────────────────────────────────────────────────────────────┤
│ id, rov_project_id, point_number, label                     │
│ x_coordinate, y_coordinate, severity, defect_type          │
│ description, recommendations                                │
│                                                               │
│ rov_project_id ────→ rov_projects table (Points belong to)│
└──────────────────────────────────────────────────────────────┘
                            │
                            │ HasMany
                            ▼
┌──────────────────────────────────────────────────────────────┐
│                    InspectionMedia                           │
│              (Videos, Images per Location)                   │
├──────────────────────────────────────────────────────────────┤
│ id, inspection_point_id, media_type                         │
│ file_name, file_path, file_url, duration                   │
│ uploaded_by, uploaded_at                                    │
│                                                               │
│ inspection_point_id ────→ inspection_points table           │
│ uploaded_by ────→ users table (Who uploaded)                │
└──────────────────────────────────────────────────────────────┘


Back to RovProject:
│
│ HasOne
▼
┌──────────────────────────────────────────────────────────────┐
│                   InspectionReport                           │
│                (Compiled Findings)                           │
├──────────────────────────────────────────────────────────────┤
│ id, rov_project_id, title, summary, full_report            │
│ status, shared_link_hash, shared_date                       │
│                                                               │
│ rov_project_id ────→ rov_projects table                     │
└──────────────────────────────────────────────────────────────┘
                            │
                            │ HasMany
                            ▼
┌──────────────────────────────────────────────────────────────┐
│                  ReportAccessLog                             │
│              (Track Who Viewed Report)                       │
├──────────────────────────────────────────────────────────────┤
│ id, report_id, accessed_by, accessed_at, ip_address         │
│ duration (how long viewed)                                   │
│                                                               │
│ report_id ────→ inspection_reports table                    │
└──────────────────────────────────────────────────────────────┘
```

### All Model Relationships (PHP Code):

```php
// RovProject - THE MASTER
class RovProject extends Model 
{
    // Link to YOUR Company (Frogmen branch/office)
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // Link to CLIENT (Ship owner, refinery, etc.)
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'customer_id');
    }

    // Link to INSPECTOR (who created it)
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // One project has Many inspection points
    public function inspectionPoints(): HasMany
    {
        return $this->hasMany(InspectionPoint::class);
    }

    // One project has One report
    public function report(): HasOne
    {
        return $this->hasOne(InspectionReport::class);
    }
}

// InspectionPoint - LOCATIONS ON MAP
class InspectionPoint extends Model
{
    // Many points belong to One project
    public function rovProject(): BelongsTo
    {
        return $this->belongsTo(RovProject::class);
    }

    // One point has Many media files
    public function mediaFiles(): HasMany
    {
        return $this->hasMany(InspectionMedia::class);
    }
}

// InspectionMedia - VIDEOS & IMAGES
class InspectionMedia extends Model
{
    // Many media belong to One point
    public function inspectionPoint(): BelongsTo
    {
        return $this->belongsTo(InspectionPoint::class);
    }

    // Who uploaded this? Link to User
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

// InspectionReport - COMPILED FINDINGS
class InspectionReport extends Model
{
    // One report belongs to One project
    public function rovProject(): BelongsTo
    {
        return $this->belongsTo(RovProject::class);
    }

    // One report has Many access logs
    public function accessLogs(): HasMany
    {
        return $this->hasMany(ReportAccessLog::class);
    }
}

// ReportAccessLog - WHO VIEWED
class ReportAccessLog extends Model
{
    // Many access logs belong to One report
    public function report(): BelongsTo
    {
        return $this->belongsTo(InspectionReport::class);
    }
}
```

---

## 7. Implementation Strategy

### Phase 1: Create Base Plugin Structure
```bash
# Create plugin scaffold
composer require --dev webkul/plugin-generator

# When generator available, create:
plugins/webkul/rov_inspection/
```

### Phase 2: Create Core Models
```
RovProject (master record with company_id + customer_id)
InspectionPoint (location on map, belongs to RovProject)
InspectionMedia (videos/images, belongs to InspectionPoint)
InspectionReport (compiled findings, belongs to RovProject)
ReportAccessLog (access tracking, belongs to InspectionReport)
```

### Phase 3: Create Filament Resources
```
RovProjectResource (CRUD)
InspectionPointResource (CRUD)
ReportResource (generate & manage)
```

### Phase 4: Create Custom Pages
```
MapAnnotationPage (interactive canvas)
ReportBuilderPage (internal)
ClientReportViewer (public)
```

### Phase 5: Create Livewire Components
```
MapMarkerPlotter (interactive canvas)
MediaUploader (video/image upload)
```

---

## 7. Shared Infrastructure (Reused Benefits)

Even as separate plugin, you'll still get:
- ✅ Multi-tenancy (company_id foreign key)
- ✅ Filament admin panel integration
- ✅ User permissions (through Filament Shield)
- ✅ Activity logging (HasLogActivity trait)
- ✅ Soft deletes
- ✅ Custom fields support (if needed)

---

## 8. Complete Database Design (With All Relations)

```sql
-- MASTER TABLE: ROV INSPECTION PROJECT
CREATE TABLE rov_projects (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  
  -- Basic Info
  name VARCHAR(255) NOT NULL,
  description TEXT,
  location VARCHAR(255),
  status ENUM('draft', 'in_progress', 'completed', 'archived') DEFAULT 'draft',
  
  -- Site Map
  site_map_path VARCHAR(255),           -- /storage/projects/123/sitemap.jpg
  site_map_url VARCHAR(255),            -- /projects/123/sitemap
  
  -- Dates
  start_date DATE,
  end_date DATE,
  
  -- RELATIONSHIPS
  company_id BIGINT UNSIGNED NOT NULL,  -- FK: companies(id) - YOUR Company
  customer_id BIGINT UNSIGNED,          -- FK: partners_partners(id) - CLIENT
  created_by BIGINT UNSIGNED,           -- FK: users(id) - Inspector
  
  -- Soft Delete & Timestamps
  deleted_at TIMESTAMP NULL,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  
  -- Constraints
  FOREIGN KEY (company_id) REFERENCES companies(id),
  FOREIGN KEY (customer_id) REFERENCES partners_partners(id),
  FOREIGN KEY (created_by) REFERENCES users(id),
  
  -- Indexes for queries
  INDEX idx_company (company_id),
  INDEX idx_customer (customer_id),
  INDEX idx_status (status),
  INDEX idx_created (created_by)
);


-- INSPECTION POINTS TABLE
CREATE TABLE inspection_points (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  
  -- Location Data
  rov_project_id BIGINT UNSIGNED NOT NULL,  -- FK: rov_projects(id)
  point_number INT,                         -- 1, 2, 3, ... (auto increment)
  label VARCHAR(100),                       -- "Plank A1", "Dolphin_West", etc.
  x_coordinate FLOAT,                       -- Pixel X on image
  y_coordinate FLOAT,                       -- Pixel Y on image
  
  -- Inspection Data
  severity ENUM('high', 'medium', 'low', 'observation'),
  defect_type VARCHAR(100),                 -- corrosion, pitting, cracks, etc.
  description TEXT,                         -- What was observed
  recommendations TEXT,                     -- What to do
  
  -- Timestamps
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  
  -- Constraints
  FOREIGN KEY (rov_project_id) REFERENCES rov_projects(id) ON DELETE CASCADE,
  
  -- Indexes
  INDEX idx_project (rov_project_id),
  INDEX idx_severity (severity),
  UNIQUE KEY unique_point_per_project (rov_project_id, point_number)
);


-- INSPECTION MEDIA TABLE
CREATE TABLE inspection_media (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  
  -- Link to Location
  inspection_point_id BIGINT UNSIGNED NOT NULL,  -- FK: inspection_points(id)
  
  -- File Info
  media_type ENUM('video', 'image', 'document'),
  file_name VARCHAR(255),
  file_path VARCHAR(255),                 -- /storage/projects/123/points/456/video1.mp4
  file_url VARCHAR(255),                  -- /projects/123/points/456/videos/1
  file_size INT,                          -- in bytes
  mime_type VARCHAR(50),                  -- video/mp4, image/jpeg, etc.
  
  -- Video Specific
  duration INT,                           -- in seconds (for videos only)
  resolution VARCHAR(20),                 -- 1920x1080 (for videos only)
  thumbnail_path VARCHAR(255),            -- Thumbnail image path
  
  -- Upload Info
  uploaded_by BIGINT UNSIGNED,            -- FK: users(id) - Who uploaded
  uploaded_at TIMESTAMP,
  
  -- Timestamps
  created_at TIMESTAMP,
  deleted_at TIMESTAMP NULL,
  
  -- Constraints
  FOREIGN KEY (inspection_point_id) REFERENCES inspection_points(id) ON DELETE CASCADE,
  FOREIGN KEY (uploaded_by) REFERENCES users(id),
  
  -- Indexes
  INDEX idx_point (inspection_point_id),
  INDEX idx_type (media_type),
  INDEX idx_uploaded (uploaded_by)
);


-- INSPECTION REPORT TABLE
CREATE TABLE inspection_reports (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  
  -- Link to Project
  rov_project_id BIGINT UNSIGNED NOT NULL,  -- FK: rov_projects(id)
  
  -- Report Content
  title VARCHAR(255),
  summary TEXT,                           -- Executive summary
  full_report LONGTEXT,                   -- HTML/Markdown complete report
  conclusions TEXT,                       -- Final conclusions
  recommendations TEXT,                   -- What needs to be done
  
  -- Status & Sharing
  status ENUM('draft', 'ready', 'shared', 'archived') DEFAULT 'draft',
  shared_by BIGINT UNSIGNED,              -- FK: users(id) - Who shared it
  shared_date TIMESTAMP NULL,
  
  -- Share Link Security
  shared_link_hash VARCHAR(255) UNIQUE,   -- Random hash for share link
  shared_link_password VARCHAR(255),      -- Hashed password (optional)
  shared_link_expires_at TIMESTAMP NULL,  -- When access expires
  
  -- Client Controls
  client_can_download BOOLEAN DEFAULT false,
  client_can_print BOOLEAN DEFAULT false,
  
  -- Timestamps
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  
  -- Constraints
  FOREIGN KEY (rov_project_id) REFERENCES rov_projects(id) ON DELETE CASCADE,
  FOREIGN KEY (shared_by) REFERENCES users(id),
  
  -- Indexes
  INDEX idx_project (rov_project_id),
  INDEX idx_status (status),
  INDEX idx_share_hash (shared_link_hash),
  UNIQUE KEY unique_report_per_project (rov_project_id)
);


-- REPORT ACCESS LOG TABLE (ANALYTICS)
CREATE TABLE report_access_logs (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  
  -- Link to Report
  report_id BIGINT UNSIGNED NOT NULL,     -- FK: inspection_reports(id)
  
  -- Access Info
  accessed_by VARCHAR(255),               -- Email or "Anonymous"
  accessed_at TIMESTAMP,                  -- When accessed
  ip_address VARCHAR(45),                 -- IPv4 or IPv6
  duration INT,                           -- Seconds spent viewing
  
  -- Timestamps
  created_at TIMESTAMP,
  
  -- Constraints
  FOREIGN KEY (report_id) REFERENCES inspection_reports(id) ON DELETE CASCADE,
  
  -- Indexes
  INDEX idx_report (report_id),
  INDEX idx_accessed (accessed_at),
  INDEX idx_ip (ip_address)
);
```

### Relationship Summary:

```
companies (YOUR Company)
    ↓ (company_id)
rov_projects ← - - - - → partners_partners (CLIENT)
    ↓                        ↑
    └─→ inspection_reports
         ↓
    report_access_logs
    
rov_projects
    ↓ HasMany
inspection_points
    ↓ HasMany
inspection_media
```

### Access Patterns:

```php
// Get all inspections your company did
$inspections = RovProject::where('company_id', auth()->user()->default_company_id)->get();

// Get all inspections for a specific client
$clientInspections = RovProject::where('customer_id', $clientId)->get();

// Get all locations in a project + their media
$project = RovProject::with('inspectionPoints.mediaFiles')->find($projectId);

// Get inspection report with access logs
$report = InspectionReport::with('accessLogs')->find($reportId);

// See how many times client viewed the report
$viewCount = ReportAccessLog::where('report_id', $reportId)->count();
```

---

## 9. Filament Resource Structure (With Relations)

```
Admin Panel Navigation:
├── Dashboard
├── Contacts
├── Sales
├── Purchases
├── Invoices
├── Accounting
├── Inventory
├── Projects (General project management)
├── ROV Inspections ⭐ (NEW - For inspection sites/maps)
├── Employees
├── Time Off
├── Recruitment
└── Settings
```

---

## 10. Final Decision Matrix

```
Decision: ✅ CREATE NEW ROV_INSPECTION PLUGIN

Confidence Level: 95% (Very High)
Reasoning:
  ✅ Different data models
  ✅ Different workflows
  ✅ Different UI/UX
  ✅ Future expansion potential
  ✅ Cleaner architecture
  ✅ Better maintainability
  ✅ Professional separation
  ✅ Zero feature overlap
```

---

## Next Steps (When Approved)

1. Define exact schema for `rov_projects` table
2. Plan Livewire component for map annotation (most complex part)
3. Design video streaming approach (local vs CDN)
4. Create migration files
5. Build Models + Relationships
6. Create Filament Resources
7. Create custom Pages
8. Build Livewire components
9. Test workflows end-to-end
10. Document for future developers

---

## Questions to Clarify Before Building

1. Should reports be web-based or PDF export? (Recommend: both)
2. Max number of markers per project? (Suggest: 100+)
3. Video hosting: Local filesystem or cloud CDN? (Start: Local, upgrade later)
4. Mobile support needed for on-site marking? (Future phase)
5. Real-time collaboration for multiple inspectors? (Future phase)

---

**Status:** Ready for approval and development  
**Estimated Build Time:** 6-8 weeks for MVP  
**Complexity Level:** High (but manageable with plan)
