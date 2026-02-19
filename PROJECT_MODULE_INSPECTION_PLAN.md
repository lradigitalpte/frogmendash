# FrogmenDash: ROV Inspection Project Module - Requirements & Design Plan

**Status:** Planning Phase  
**Module Name:** Projects (Inspection Projects with Map Annotation & Media Tracking)  
**Target Users:** Internal Inspection Teams + Client Portal  
**Date:** February 18, 2026

---

## 1. Executive Summary

FrogmenDash Project Module allows inspection teams to:
1. **Upload site maps** (blueprints, floor plans, ship layouts)
2. **Plot inspection locations** on the map with numbered markers
3. **Attach media** (ROV videos, photos, reports) to each location
4. **Generate shareable reports** with interactive map views
5. **Track inspections** across multiple projects
6. **Allow clients** to view reports publicly or privately with media playback

**Real-World Example:**
- Ship owner contacts Frogmen: "Inspect my vessel's underwater hull"
- Inspector receives site map of the ship's hull layout
- Inspector conducts ROV inspection, records videos at 15 different locations
- Each location gets marked on map, videos attached
- Client receives report link, clicks locations on map, watches inspection videos
- Report is searchable, trackable, and shareable

---

## 2. Core Entities & Relationships

```
Project
├── Basic Info (name, client, date, location)
├── Site Map (uploaded blueprint/layout image)
├── Status (draft → in-progress → completed → archived)
├── Access Control (internal/client/public)
└── Inspection Points (1 to Many)
    ├── Location (x,y coordinates on map)
    ├── Marker Number (1, 2, 3, ... visual label)
    ├── Point Type (critical, moderate, minor, observation)
    ├── Notes/Description
    ├── Media Files (1 to Many)
    │   ├── Video Files (ROV footage)
    │   ├── Image Files (photos)
    │   ├── File URL (stored in DB)
    │   ├── Upload Date
    │   └── File Metadata (duration, size, format)
    └── Report Data
        ├── Defect Classification (corrosion, pitting, cracks, etc.)
        ├── Severity Level (high, medium, low)
        └── Recommendations
```

---

## 3. User Workflows

### Workflow A: Inspection Team (Internal)

**Step 1: Create Project**
```
Inspector clicks: "New Project"
Fields to fill:
  - Project Name (e.g., "Exxon Refinery Hull Structural Audit")
  - Client Name (e.g., "Exxon Mobil")
  - Project Date Range (start - end date)
  - Location (GPS or description)
  - Project Type (Hull Inspection, Underwater Structure, etc.)
  - Description
Status: DRAFT
```

**Step 2: Upload Site Map**
```
Inspector uploads:
  - Site Map Image (JPG/PNG of blueprint, floor plan, or layout)
  - Scale/Reference Information (optional)
System:
  - Stores image in storage/projects/{project_id}/
  - Generates thumbnail preview
  - Makes map interactive for plotting
Status: Ready for annotation
```

**Step 3: Plot Inspection Points on Map**
```
Inspector clicks on map image:
  - Click location → marker appears with auto-incrementing number (1, 2, 3...)
  - Can drag marker to adjust position
  - Can delete marker
Marker Properties:
  - X,Y coordinates (calculated from click)
  - Number (auto-assigned)
  - Color coding (can change by severity: red=critical, yellow=moderate, green=minor)
  - Label (optional: "Plank A1", "Dolphin_West", etc.)
Can plot 10-30+ markers depending on project
```

**Step 4: Upload Media & Attach to Points**
```
For each Inspection Point:
  Inspector clicks marker → Modal opens showing:
    - Point details
    - Upload section
    
  Upload Videos:
    - ROV footage (MP4, WebM, etc.)
    - Can upload multiple per location
    - System stores: file, duration, resolution
    - Generates video thumbnail
    - Creates playable URL: /projects/{id}/points/{point_id}/videos/{video_id}
    
  Upload Images:
    - Still photos from inspection
    - Can upload multiple
    - Creates gallery view
    
  Notes:
    - Description field for what was observed
    - Defect type dropdown (corrosion, pitting, cracks, pollution, degradation, etc.)
    - Severity: High/Medium/Low
    - Recommendations field
```

**Step 5: Create Report**
```
Inspector compiles findings:
  - Auto-generated from all points
  - Can add summary sections
  - Can add conclusions
  - Can add recommendations
  
Report sections:
  1. Executive Summary
  2. Inspection Details (date, personnel, equipment)
  3. Findings (by location, by severity)
  4. Media Gallery
  5. Conclusions
  6. Recommendations

Report Status:
  - Draft (team only)
  - Ready to Share (can send to client)
  - Shared (client has access)
  - Archived
```

**Step 6: Share with Client**
```
Inspector generates:
  - Public/Private Link: /reports/{unique_hash}
  - Password protect (optional)
  - Set expiration date (optional)
  - Configure what client can see:
    ☐ Site map with markers
    ☐ Video playback
    ☐ Full report text
    ☐ Download capability
    
System sends:
  - Email link to client
  - Upload link to client portal
```

---

### Workflow B: Client (External)

**Access Report**
```
Client receives link: https://frogmendash.com/reports/abc123xyz

Client sees:
  1. Project Overview
     - Project name, date, location
     - Summary of findings
     
  2. Interactive Site Map View
     - Displays site map image
     - Numbered markers visible (1, 2, 3, etc.)
     - Color-coded by severity (red=critical, yellow=moderate, green=minor)
     
  3. Click Marker → View Findings
     - Location details
     - Video player (embedded)
     - Photo gallery
     - Text description
     - Defect type
     - Severity
     - Recommendations
     
  4. Full Report
     - PDF download option
     - Print-friendly version
     
  5. Search/Filter (Optional)
     - Filter by severity
     - Filter by defect type
     - Search keywords
```

---

## 4. Database Schema Design

### Table: projects
```sql
CREATE TABLE projects (
  id BIGINT PRIMARY KEY,
  company_id BIGINT (foreign key - for multi-tenancy),
  name VARCHAR(255),
  client_id BIGINT (foreign key - contact),
  description TEXT,
  project_type ENUM('hull_inspection', 'structural_audit', 'training', 'other'),
  location VARCHAR(255),
  start_date DATE,
  end_date DATE,
  status ENUM('draft', 'in_progress', 'completed', 'archived'),
  site_map_path VARCHAR(255) (path to uploaded image),
  site_map_url VARCHAR(255) (public URL),
  created_by BIGINT (foreign key - user),
  accessibility ENUM('internal', 'client', 'public'),
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  deleted_at TIMESTAMP (soft delete)
);
```

### Table: inspection_points
```sql
CREATE TABLE inspection_points (
  id BIGINT PRIMARY KEY,
  project_id BIGINT (foreign key),
  point_number INT (1, 2, 3, etc.),
  label VARCHAR(100) (e.g., "Plank A1", "Dolphin_West"),
  x_coordinate FLOAT (pixel position on image),
  y_coordinate FLOAT (pixel position on image),
  point_type ENUM('critical', 'moderate', 'minor', 'observation'),
  defect_type VARCHAR(100) (corrosion, pitting, cracks, etc.),
  severity ENUM('high', 'medium', 'low'),
  description TEXT,
  recommendations TEXT,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

### Table: inspection_media
```sql
CREATE TABLE inspection_media (
  id BIGINT PRIMARY KEY,
  inspection_point_id BIGINT (foreign key),
  media_type ENUM('video', 'image', 'document'),
  file_name VARCHAR(255),
  file_path VARCHAR(255) (storage location),
  file_url VARCHAR(255) (public URL),
  file_size INT (in bytes),
  mime_type VARCHAR(50) (video/mp4, image/jpeg, etc.),
  duration INT (for videos, in seconds),
  resolution VARCHAR(20) (1920x1080, etc.),
  thumbnail_path VARCHAR(255),
  uploaded_by BIGINT (foreign key - user),
  uploaded_at TIMESTAMP,
  created_at TIMESTAMP,
  deleted_at TIMESTAMP
);
```

### Table: project_reports
```sql
CREATE TABLE project_reports (
  id BIGINT PRIMARY KEY,
  project_id BIGINT (foreign key),
  report_title VARCHAR(255),
  summary TEXT,
  full_report LONGTEXT (HTML or markdown),
  conclusions TEXT,
  recommendations TEXT,
  status ENUM('draft', 'ready', 'shared', 'archived'),
  shared_by BIGINT (foreign key - user),
  shared_date TIMESTAMP,
  shared_link_hash VARCHAR(255) (unique),
  shared_link_password VARCHAR(255) (hashed),
  shared_link_expires_at TIMESTAMP,
  client_can_download BOOLEAN,
  client_can_print BOOLEAN,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

### Table: report_access_logs (Track who viewed)
```sql
CREATE TABLE report_access_logs (
  id BIGINT PRIMARY KEY,
  report_id BIGINT (foreign key),
  accessed_by VARCHAR(255) (email or anonymous),
  accessed_at TIMESTAMP,
  ip_address VARCHAR(45),
  duration INT (seconds spent)
);
```

---

## 5. Feature Set

### MVP (Minimum Viable Product)
- ✅ Create projects with site map upload
- ✅ Plot markers on site map
- ✅ Upload videos/images to markers
- ✅ Generate report with interactive map
- ✅ Share link with client
- ✅ Client-side report viewer

### Phase 2
- [ ] Video/image gallery per point
- [ ] Defect classification system
- [ ] Severity color-coding
- [ ] PDF export reports
- [ ] Search/filter functionality

### Phase 3
- [ ] Mobile app for on-site marking
- [ ] Real-time collaboration (multiple inspectors)
- [ ] Historical versions of reports
- [ ] Notification system
- [ ] Analytics (most viewed points, etc.)

### Future
- [ ] Integration with ROV systems for auto-geotagging
- [ ] 3D model support (instead of 2D maps)
- [ ] Machine learning for defect detection
- [ ] Integration with asset management systems

---

## 6. Technical Implementation Stack

### Frontend Components (Filament + Laravel)
```
Resources:
  - ProjectResource (CRUD for projects)
  - InspectionPointResource (CRUD for points)
  - InspectionMediaResource (CRUD for uploads)
  - ProjectReportResource (Generate & manage reports)

Pages:
  - Project Detail (view + edit site map)
  - Map Annotation Interface (interactive canvas)
  - Report Viewer (client-facing, read-only)
  - Report Builder (team-facing, private)

Livewire Components:
  - MapMarkerPlotter (interactive canvas for plotting)
  - MediaUploader (drag-drop video/image upload)
  - ReportPreview (real-time report preview)
  - ClientReportViewer (read-only client view)
```

### Storage
```
Directory Structure:
/storage/projects
  /{project_id}/
    /site_map/
      sitemap-{date}.jpg
    /media/
      /point_{point_id}/
        video_{id}.mp4
        image_{id}.jpg
```

### Sharing/URLs
```
Public Report Link:
  /reports/{unique_hash}
  
Optional:
  /reports/{unique_hash}?password=abc123
  /projects/{project_id}/viewer (internal preview)
```

---

## 7. Business Logic & Workflow Rules

### Project Creation
- ✅ User (Inspector) can create project
- ✅ Auto-assign to user's company
- ✅ Set initial status to DRAFT

### Site Map Upload
- ✅ Only image formats (JPG, PNG, WebP)
- ✅ Max size: 10MB
- ✅ Validate image dimensions (sanity check)
- ✅ Generate thumbnail for preview

### Marker Plotting
- ✅ Auto-increment marker numbers (1, 2, 3...)
- ✅ Save X,Y coordinates relative to image dimensions
- ✅ Allow drag-to-reposition
- ✅ Allow delete
- ✅ Support 50+ markers per project

### Media Upload
- ✅ Videos: MP4, WebM, MOV (max 2GB per file)
- ✅ Images: JPG, PNG, WebP (max 50MB)
- ✅ Generate video thumbnails
- ✅ Store file URLs in DB
- ✅ Support multiple files per point

### Report Generation
- ✅ Auto-compile from all inspection points
- ✅ Group by severity level
- ✅ Include media thumbnails/links
- ✅ Generate unique share link
- ✅ Track access with timestamp

### Access Control
- ✅ Internal: Only team members can view
- ✅ Client: Client email has read-only access
- ✅ Public: Anyone with link can view
- ✅ Password-protected: Optional

---

## 8. UI/UX Mockup (Conceptual)

### Inspector View: Project Dashboard
```
┌─────────────────────────────────────────────────────────┐
│ Frogmen Dashboard > Projects                             │
├─────────────────────────────────────────────────────────┤
│ [+ New Project]  [My Projects] [Completed] [Archive]    │
├─────────────────────────────────────────────────────────┤
│                                                           │
│ Project: "Exxon Refinery Hull Audit"                    │
│ Client: Exxon Mobil                                     │
│ Status: In Progress (15 of 25 locations inspected)      │
│ [View Map]  [Edit]  [Share Report]  [Archive]           │
│                                                           │
│ Date: 5-Oct-2020 to 25-Oct-2020                         │
│ Location: Rotterdam Port                                │
│                                                           │
└─────────────────────────────────────────────────────────┘
```

### Inspector View: Map Annotation
```
┌────────────────────────────────────────┬──────────────────┐
│ Site Map - Click to plot points        │ Point Details    │
│ ┌──────────────────────────────────┐   │                  │
│ │                                  │   │ Point #3         │
│ │  [1]  [2]   [3] ● (Selected)    │   │ Label: Plank A2  │
│ │                                  │   │                  │
│ │           [4]      [5] [6]       │   │ Defect: Corrosion│
│ │                                  │   │ Severity: HIGH   │
│ │ [Refinery Blueprint]             │   │                  │
│ │                                  │   │ [Upload Video]   │
│ │       [7]    [8]   [9]           │   │ [Upload Image]   │
│ │                                  │   │                  │
│ │    [10]         [11][12][13]     │   │ [Add Note]       │
│ │                                  │   │                  │
│ └──────────────────────────────────┘   │                  │
│                                        │                  │
└────────────────────────────────────────┴──────────────────┘
```

### Client View: Report
```
┌──────────────────────────────────────────────────────────────┐
│ Frogmen Assessment Dashboard                                 │
│                                                               │
│ Structural Audit of Steel Structure at Exxon Refinery       │
│ DATE: 5-Oct-2020 to 25-Oct-2020                             │
│                                                               │
│ ┌────────────────────────────────────────────────────────┐   │
│ │ [Site Map with Interactive Markers]                    │   │
│ │ ┌──────────────────────────────────────────────────┐   │   │
│ │ │   [1-Green]  [2-Green] [3-Green] [4-Yellow]     │   │   │
│ │ │                                                  │   │   │
│ │ │ [Refinery Blueprint/Floor Plan]                 │   │   │
│ │ │          [5-Yellow] [6-Red!] [7-Green]         │   │   │
│ │ │                                                  │   │   │
│ │ │              [8-Green] [9-Yellow] [10-Red!]     │   │   │
│ │ │                                                  │   │   │
│ │ └──────────────────────────────────────────────────┘   │   │
│ └────────────────────────────────────────────────────────┘   │
│                                                               │
│ [Click on any marker to view inspection videos & details]    │
│                                                               │
│ When user clicks marker → Modal shows:                       │
│ ┌────────────────────────────────────────────────────────┐   │
│ │ Location #6: Plank B1 (CRITICAL)                       │   │
│ │                                                         │   │
│ │ [Video Player - ROV Footage]                           │   │
│ │ ▶━━━━━━━━━━━━━━━━━━━━━━┿━━━━━  3:45 / 15:20          │   │
│ │                                                         │   │
│ │ Defect: Heavy Corrosion | Severity: HIGH               │   │
│ │ Description: Severe metal loss detected in lower       │   │
│ │ section of plank. Immediate repair recommended.        │   │
│ │                                                         │   │
│ │ [Photo 1] [Photo 2] [Photo 3]                          │   │
│ │                                                         │   │
│ │ [Download Full Report as PDF]                          │   │
│ │                                                         │   │
│ └────────────────────────────────────────────────────────┘   │
│                                                               │
└──────────────────────────────────────────────────────────────┘
```

---

## 9. Implementation Milestones

### Phase 1: MVP (6-8 weeks)
- [ ] Week 1-2: Database schema + model creation
- [ ] Week 2-3: Project CRUD (Filament Resource)
- [ ] Week 3-4: Map annotation interface (Livewire component)
- [ ] Week 4-5: Media upload system
- [ ] Week 5-6: Report generation
- [ ] Week 6-7: Share link + client viewer
- [ ] Week 7-8: Testing + polish

### Phase 2: Enhancements (4-6 weeks)
- [ ] Defect classification system
- [ ] Severity color-coding
- [ ] Video/image gallery improvements
- [ ] PDF export
- [ ] Search/filter

### Phase 3: Advanced (Ongoing)
- [ ] Mobile support
- [ ] Real-time collaboration
- [ ] Advanced analytics
- [ ] Integration with external systems

---

## 10. Security & Compliance Considerations

### Data Protection
- ✅ Uploaded files scanned for malware
- ✅ Video files transcoded (to prevent embedded malware)
- ✅ Access logs tracked
- ✅ Soft deletion (archive instead of hard delete)
- ✅ Encrypted storage for sensitive files

### Access Control
- ✅ Role-based (Inspector, Client, Admin)
- ✅ Multi-tenancy (each company sees only their projects)
- ✅ Share link expiration
- ✅ Password protection on reports
- ✅ Audit trail of who accessed what

### Compliance
- ✅ GDPR: Right to delete
- ✅ SOC 2: Encryption in transit & at rest
- ✅ Regular backups of media files
- ✅ Data retention policies

---

## 11. Success Metrics

### For Inspectors
- Time to create report: < 30 mins
- Time to upload/annotation: < 1 hour
- Ease of sharing: One-click link

### For Clients
- Time to understand findings: < 5 mins per location
- Engagement: Avg. 3+ minutes per location viewed
- Satisfaction: Would recommend feature

### For Business
- Customer retention: Increased due to professional reporting
- Competitive advantage: Unique inspection visualization
- Scalability: Support 100+ concurrent reports

---

## 12. Notes & Next Steps

### Questions to Clarify
1. Should clients be able to download high-res videos or just stream?
2. Should reports be PDF-based or web-based (current design = web)?
3. Should there be a native mobile app for on-site inspection, or just web?
4. Max project size? (Max markers, max media files?)
5. Compliance requirements? (GDPR, SOC 2, ISO, etc.)
6. Budget for video hosting? (Local storage vs CDN?)

### Technology Decisions
1. **Video Streaming:** Use Laravel's streaming for video playback (no external service needed)
2. **Storage:** Local filesystem initially, cloud (S3) if scaling
3. **Map Annotation:** HTML5 Canvas + JavaScript with Livewire
4. **PDF Export:** Use mPDF or TCPDF Laravel package
5. **CDN:** Optional for video delivery if many concurrent users

### Team Requirements
- 1 Backend Developer (Database, APIs)
- 1 Frontend Developer (UI/UX, Map interface)
- 1 QA Engineer (Testing workflows)
- ~4-6 weeks for MVP

---

## 13. References

**Inspiration:**
- Frogmen dive inspection service (Google it)
- ROV inspection platforms (VideoRay, Saab Seaeye)
- ProjectManagement tools (Asana, Monday.com)
- Collaborative markup tools (Figma, InVision)

**Technology Stack:**
- Laravel 11 + Filament v4 (Framework)
- Livewire v3 (Interactivity)
- Alpine.js (Frontend reactivity)
- MySQL + multi-tenancy structure (Data)
- Laravel Spatie Media Library (File management)
- FFmpeg (Video transcoding - optional)

---

**End of Requirements Document**

This plan is comprehensive but flexible. We can refine based on:
- Budget constraints
- Timeline requirements
- Specific technical preferences
- Client feedback
- Market research

Ready to start implementation whenever you approve!
