# YouthNexus (Yovun Saviya)

Web-based centralized management platform for Sri Lanka's Youth Clubs under the NYSC — built with vanilla PHP, MySQL, and Bootstrap 5.

## Project Goal

Yovun Saviya digitizes the registration, governance, and monitoring of youth clubs while improving transparency, accountability, and national-level visibility for NYSC.

## Target Roles

- Youth Club Member
- Club Executive (President / Secretary / Treasurer)
- Zonal Coordinator
- Divisional Coordinator
- NYSC Administrator

## Functional Scope

### 1) Authentication & User Management
1. Login
2. Register Member (executive-managed only)
3. Assign Executive Role

### 2) Club Governance & Administration
4. Register New Club
5. Approve/Reject Club Registration
6. Leadership Handover
7. Disband Club (with unresolved asset/fund checks)

### 3) Event Management
8. Create Club Event
9. Create Divisional Event
10. Verify Event Occurrence

### 4) Financial Management
11. Log Club Transaction (receipt mandatory for expenses)
12. Allocate Funds to Club
13. Audit Club Finances

### 5) Asset Management
14. Register Club Asset
15. Transfer Asset Custody
16. Verify Asset Existence

### 6) Monitoring & Reporting
17. Monitor Zone Health
18. Generate Divisional Report (PDF)
19. View National Analytics

### 7) Certificates & Verification
20. Generate Volunteer Certificate (QR-coded PDF)
21. Verify Certificate (public portal, no login)

## Club Health Scoring

Club status is calculated with weighted metrics:

- Events: **40%**
- Financial Activity: **30%**
- Attendance: **30%**

## Core Non-Functional Requirements

- **Security:** bcrypt password hashing, strict server-side RBAC, session expiry.
- **Performance:** indexed query paths, AJAX-based dashboard updates.
- **Reliability:** transactional integrity for multi-table operations.
- **Maintainability:** custom PHP MVC with modular structure and consistent naming.
- **Usability:** role-specific dashboards and low-friction workflows for non-technical users.

## Constraints

- No frontend/backend frameworks (vanilla PHP, HTML, CSS, JS only; Bootstrap components permitted).
- No real payment processing (ledger tracking only).
- Digital certificate issuance only.
- Minimum one executive (President/Secretary) required for active club lifecycle.

## Technology Stack

- **Backend:** PHP 8.x (custom MVC), MySQL 8.x
- **Frontend:** HTML5, CSS3, Vanilla JS (Fetch/AJAX), Bootstrap 5
- **Mapping:** Leaflet.js + OpenStreetMap
- **Certificates:** FPDF/TCPDF + QR code library
- **Tooling:** Git/GitHub, VS Code, XAMPP/WAMP, MySQL Workbench, draw.io

## Deliverables

- Deployable web platform
- Version-controlled source code
- Database schema + ER diagram + sample data
- User documentation for all five roles
- Technical architecture/API documentation
- Public certificate verification portal
- Demo-ready final presentation build
