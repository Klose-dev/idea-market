# 💡 IdeaMarket — Complete System Documentation

> **Version:** 1.0 | **Timezone:** Africa/Douala (WAT — UTC+1) | **Stack:** PHP 8.0+ · MySQL · Vanilla JS · Chart.js

---

## 📋 Table of Contents

1. [System Overview](#1-system-overview)
2. [Tech Stack](#2-tech-stack)
3. [File Structure](#3-file-structure)
4. [Installation & Setup](#4-installation--setup)
5. [Login Credentials](#5-login-credentials)
6. [Frontend — Page-by-Page Guide](#6-frontend--page-by-page-guide)
7. [Real-Time Update System](#7-real-time-update-system)
8. [All Functionalities Explained](#8-all-functionalities-explained)
9. [Database Schema](#9-database-schema)
10. [API Endpoints](#10-api-endpoints)
11. [Idea Status Lifecycle](#11-idea-status-lifecycle)
12. [User Roles & Permissions](#12-user-roles--permissions)
13. [Design System](#13-design-system)
14. [Security Features](#14-security-features)
15. [Customization Guide](#15-customization-guide)
16. [Troubleshooting](#16-troubleshooting)

---

## 1. System Overview

**IdeaMarket** is a full-stack idea marketplace web application where:

- **Idea Submitters** (regular users) post innovation ideas, track their progress using a unique Idea ID, and build community support through votes.
- **The Community** votes on ideas to surface the best ones — no gatekeepers, fully democratic.
- **Admins** review submitted ideas, assign reviewers, update statuses, and manage the full platform.
- **Investors** (role available) browse approved ideas and pledge funding directly.

Every action — submission, vote, status change — is logged and reflected live in dashboards via a **5-second polling system** synchronized to the **Africa/Douala (WAT, UTC+1)** timezone.

---

## 2. Tech Stack

| Layer        | Technology                                       |
|--------------|--------------------------------------------------|
| Frontend     | HTML5, CSS3 (custom design system — no Bootstrap) |
| JavaScript   | Vanilla JS (ES2020+), Fetch API                  |
| Charts       | Chart.js 4.4 (CDN)                               |
| Icons        | Font Awesome 6.5 (CDN)                           |
| Fonts        | Syne (headings) + DM Sans (body) — Google Fonts  |
| Backend      | PHP 8.0+ with PDO (prepared statements)          |
| Database     | MySQL 5.7+ / MariaDB 10.3+                       |
| Timezone     | Africa/Douala (WAT, UTC+1) — set in PHP + MySQL  |
| Auth         | PHP sessions + password_hash (bcrypt)            |

---

## 3. File Structure

```
idea-market/
|
+-- index.php              <- Landing page (Home)
+-- about.php              <- About page
+-- login.php              <- Login + Register (tabbed card)
+-- register.php           <- Redirects to login.php?tab=register
+-- logout.php             <- Destroys session, redirects to login
+-- browse.php             <- Public marketplace browser
+-- idea.php               <- Single idea detail page
+-- track.php              <- Public idea status tracker
+-- dashboard.php          <- User dashboard (requires login)
+-- submit-idea.php        <- Idea submission form (requires login)
+-- my-ideas.php           <- User's personal ideas list
+-- admin.php              <- Admin overview dashboard
+-- admin-ideas.php        <- Admin: manage all ideas (CRUD + AJAX)
+-- admin-users.php        <- Admin: manage all users
+-- database.sql           <- Full MySQL schema + seed data
+-- README.md              <- This documentation file
|
+-- includes/
|   +-- db.php             <- DB connection + timezone (Africa/Douala)
|   +-- auth.php           <- Session, login, register, CSRF helpers
|   +-- functions.php      <- Helpers: dates (WAT), badges, stats
|
+-- api/
|   +-- get_updates.php    <- Real-time polling: stats + activity JSON
|   +-- vote_idea.php      <- AJAX vote toggle endpoint
|   +-- get_chart_data.php <- Chart.js time-series data endpoint
|
+-- assets/
    +-- css/
    |   +-- main.css       <- Full design system (variables, components)
    |   +-- landing.css    <- Landing/public page styles
    +-- js/
    |   +-- app.js         <- Core JS: dark mode, tabs, modals, toasts
    |   +-- charts.js      <- All Chart.js chart configurations
    |   +-- realtime.js    <- Polling engine + AJAX voting + live search
    +-- uploads/           <- (Create this folder manually, chmod 755)
```

---

## 4. Installation & Setup

### Prerequisites
- PHP 8.0 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Apache with mod_rewrite (XAMPP / WAMP / LAMP)

### Step 1 — Place Files
```
XAMPP:  C:/xampp/htdocs/idea-market/
WAMP:   C:/wamp64/www/idea-market/
Linux:  /var/www/html/idea-market/
```

### Step 2 — Import Database

Open phpMyAdmin > Import > select `database.sql` > Go.

Or via terminal:
```bash
mysql -u root -p < database.sql
```

### Step 3 — Configure Database Connection

Edit `includes/db.php`:
```php
define('DB_HOST', 'localhost');   // usually localhost
define('DB_NAME', 'idea_market'); // database name (already in SQL file)
define('DB_USER', 'root');        // your MySQL username
define('DB_PASS', '');            // your MySQL password
define('SITE_URL', 'http://localhost/idea-market');
```

### Step 4 — Create Uploads Directory
```bash
mkdir -p assets/uploads
chmod 755 assets/uploads
```

### Step 5 — Start & Visit
Start Apache + MySQL, then open:
```
http://localhost/idea-market
```

Or with PHP built-in server:
```bash
cd idea-market
php -S localhost:8000
# Visit: http://localhost:8000
```

---

## 5. Login Credentials

### Default Admin Account

| Field    | Value                   |
|----------|-------------------------|
| Email    | admin@ideamarket.com    |
| Password | password                |
| Role     | Administrator           |
| Access   | Full admin panel        |

> WARNING: Change the admin password immediately after first login!

### Creating a Test User Account

1. Visit `http://localhost/idea-market/login.php`
2. Click the **Register** tab
3. Fill in Full Name, Email, Password (min. 8 characters)
4. Click **Create Account**
5. You will be redirected to login automatically

### Role Types

| Role       | What They Can Do                                     |
|------------|------------------------------------------------------|
| user       | Submit ideas, vote, track, browse, comment           |
| admin      | Everything + manage ideas, users, update statuses    |
| investor   | Browse approved ideas + pledge funding               |

---

## 6. Frontend — Page-by-Page Guide

### index.php — Landing Page

The first page visitors see. It has:

- **Fixed Navbar**: Deep navy background, logo left, navigation links center, dark mode toggle + Login/Get Started buttons right. Becomes more opaque on scroll.
- **Hero Section**: Full-viewport animated background with floating colored particles, large headline "Where Ideas Find Their Future", subtext, two CTA buttons ("Submit Your Idea" + "Browse Ideas"), and four animated count-up stats (Ideas Submitted, Funded, Votes Cast, Active Investors).
- **Marquee Ticker Bar**: Continuously scrolling dark navy bar with live platform stats — pauses on hover.
- **Feature Cards Section**: Three white cards with colored top border accents: Transparent by Design, Fast Community Validation, Direct Investor Access. Cards lift on hover.
- **How It Works**: Vertical 6-step timeline — Register, Submit, Get Idea ID, Community Votes, Investor Matching, Launch & Grow. Each step has a navy circle number, title, description, and tag badge.
- **Categories Grid**: 8 clickable category cards with Font Awesome icons and idea counts linking to the filtered browse page.
- **CTA Banner**: Navy gradient banner with submit + browse CTAs.
- **Footer**: Navigation links, social icons (Twitter, LinkedIn, GitHub, Instagram), copyright.

---

### about.php — About Page

- Same fixed navbar
- Hero banner with title and subtitle
- Mission split layout: text on left explaining the platform's purpose, emoji illustration on right, two CTAs
- Stats Banner: Dark navy strip with 4 count-up animated numbers
- Values Grid: Four white cards (Transparency, Speed, Accountability, Community-Driven) with colored left border
- How We Operate: 4-item list with blue icon boxes
- CTA banner and footer

---

### login.php — Login and Register

Centered white card on a deep navy gradient background.

- Logo box and "Welcome to IdeaMarket" navy header
- Tabbed interface: "Login" and "Register" — switching is instant (no page reload)
- Login tab: Email, password with show/hide toggle, "Forgot password?" link, submit button
- Register tab: Full Name, Email, Password, Confirm Password, Terms checkbox
- Client-side validation before submit (empty fields, email format, password match)
- Server-side errors shown as styled alert box above the form
- "Back to IdeaMarket" link below the card

What happens:
- Login: PHP verifies credentials, sets session, redirects to dashboard.php (user) or admin.php (admin)
- Register: PHP creates user, redirects to login with success flash message

---

### browse.php — Marketplace Browser

Fixed navbar + search bar + two-column layout (sidebar filters + idea card grid).

**Sidebar:**
- "All Categories" + 8 category links with idea counts and colored icons
- Active category highlighted in blue
- Submit Idea / Track Idea quick action buttons

**Main area:**
- Search input, sort dropdown (Most Voted / Newest / Most Funded), Filter button, Clear link
- Info alert when search results are showing
- Idea card grid (auto-fill, min 300px per card)

**Each Idea Card contains:**
- Category icon on navy gradient background (Featured badge if applicable)
- Status badge and category badge
- Title (links to idea.php)
- Blue tagline text
- Truncated description (110 chars)
- Funding progress bar (when a funding goal is set)
- Footer: submitter name, date, View button, Vote button with heart icon and count

Clicking the Vote button immediately toggles the vote via AJAX — no page reload. Button turns blue when voted, count updates instantly. Toast notification appears.

Pagination appears when more than 12 results.

---

### idea.php — Single Idea Detail

Fixed navbar + breadcrumb trail + two-column layout.

**Left (main content):**
- Title card with navy gradient header: all badges, full title, tagline, submitter/date/views/Idea ID
- Description, Problem Statement, Solution, Target Market sections
- Tags shown as blue pill badges
- Comments section: submission form (login required), list of comments with initials avatars, author, text, and WAT time-ago

**Right sidebar:**
- Vote card: large vote count, heart vote button (AJAX, no reload)
- Details card: category, submitter, date, Idea ID, reviewer name
- Funding card (when goal > 0): raised amount, animated progress bar, percentage funded
- Action buttons: Track This Idea, Browse More, My Ideas (if submitter)

---

### track.php — Public Idea Tracker

Anyone (even without an account) can use this page.

- Dark navy hero with a text input to enter an Idea ID (e.g., IDEA-A1B2C3D4)
- After submit, the results appear below:

**Progress Stepper:**
- 4-step horizontal bar: Submitted > Under Review > Approved > Funded
- Completed steps turn solid blue, current step turns orange with a glow ring
- Connecting lines between steps fill blue as progress advances

**Detail Grid (left):**
- Description, Problem, Solution, Target Market
- Meta grid: Category, Submitter, Date, Idea ID, Reviewer, Funding progress bar
- Tags

**Activity Timeline (right sidebar):**
- Vertical timeline of every logged action on this idea
- Colored dot per status type (orange=pending, blue=review, green=approved, purple=funded, red=closed)
- Action text, performer name, WAT time-ago

---

### dashboard.php — User Dashboard

Deep navy sidebar + top header + content area.

**Sidebar:**
- Logo + brand text
- User avatar (first two initials in orange circle) + name + "Idea Submitter" label
- Menu: Dashboard (active), Submit Idea, My Ideas (with count badge), Browse Market, Track an Idea, My Profile, Notifications, Log Out
- "Back to Home" at bottom

**Top Header:**
- Page title
- Welcome message with user's first name
- Dark mode toggle pill
- Notification bell (red badge count updates live via polling)
- User initials avatar

**Content:**

Four stat cards:
- Total Ideas — Blue
- Pending Review — Orange
- Approved — Green
- Funded Ideas — Purple

Each card has the number with `data-stat` attribute. The real-time poller checks every 5 seconds and updates it with a pulse scale animation if the number changed.

Two charts:
- Doughnut chart ("My Ideas Overview"): One colored segment per status, legend below
- Line chart ("Submission Trend"): Blue line showing idea submissions over time, period selector (7/30/90 days) changes data via AJAX

Bottom two-column row:
- Recent Ideas Table: Idea ID, Title, Status badge, Votes, Date, View button
- Activity Feed: Timestamped list of status changes with colored dot indicators — updates live

---

### submit-idea.php — Submit Idea Form

Sidebar + two-column layout: form on left, guidance on right.

**Form sections:**

Card 1 — Basic Information:
- Idea Title (required)
- Tagline (optional one-liner)
- Category dropdown
- Priority selector (Low / Medium / High)

Card 2 — Idea Details:
- Full Description (required, min 30 chars — border color changes to green/red live)
- Problem You're Solving
- Your Proposed Solution
- Target Market

Card 3 — Funding and Tags:
- Funding Goal in USD (0 = not seeking investment)
- Tags (comma-separated)

**Right guidance sidebar:**
- "What Happens Next" — 5-step mini-guide
- "Tips for Success" — 5 writing tips

On submit:
1. Client-side validation runs
2. PHP generates Idea ID (IDEA-XXXXXXXX)
3. Inserts into ideas table with status = 'pending'
4. Logs to activity_log ("Idea submitted to marketplace")
5. Redirects to dashboard with flash message showing the new Idea ID

---

### my-ideas.php — My Ideas List

Five mini stat cards (Total, Pending, Under Review, Approved, Funded) — clicking any filters the table to that status.

Full table with: Idea ID, Title, Category with icon, Status badge, Priority badge, Votes, Date, View and Track buttons.

Pagination at 10 ideas per page. Empty state with Submit CTA if no ideas yet.

---

### admin.php — Admin Dashboard

Same sidebar layout as user dashboard but with crown icon and expanded admin menu: Dashboard, Manage Ideas (pending count badge), Users, Investments, Categories, Reports, Activity Log, Settings, Log Out. "View Live Site" at bottom.

**Content — platform-wide data:**

Two rows of four stat cards:
- Row 1: Total Ideas (blue), Pending Review (orange), Approved (green), Funded (purple)
- Row 2: Total Users (blue), Under Review (red), Total Votes (green), Closed (orange)

Two charts:
- Doughnut: Platform-wide status distribution, populated with real PHP DB data
- Line Chart: Four lines — Submitted (blue), Under Review (orange), Approved (green), Funded (purple). Period dropdown changes data via AJAX.

Bottom row:
- Recent Ideas Table: ID, Title, Submitter, Status, Priority, Votes, View + Review action buttons
- Live Activity Feed: All platform activity with user name, WAT time-ago, colored dot. "Live" green dot indicator in header.

Recent Registrations table below the row.

---

### admin-ideas.php — Manage Ideas

Status filter tab bar (All, Pending, Under Review, Approved, Funded, Closed).

Filter bar: Search + Priority dropdown + result count.

Full data table (15 per page): Checkbox, Idea ID, Title, Submitter, Category, Status badge, Priority badge, Votes, Date, Actions.

Row actions:
- View: goes to idea.php
- Edit: opens Status Update Modal
- Delete: confirms, AJAX DELETE, row fades out without page reload

**Status Update Modal:**
- Shows idea title
- New Status dropdown
- Assign Reviewer dropdown (admin users)
- Update button: AJAX POST, status badge updates in table instantly

---

### admin-users.php — Manage Users

Search filter by name or email.

Table (20 per page): User ID, Name with initials avatar, Email, Role dropdown (inline AJAX update), Idea count, Votes Cast, Joined date, Status badge, Ban/Unban button.

Inline role change: Selecting from dropdown immediately fires AJAX — no form submit needed. Toast confirmation appears.

Ban button: AJAX toggle, button class and text change instantly. Cannot ban your own account.

---

## 7. Real-Time Update System

### How It Works (5-Second Polling)

```
Browser
  |
  +-- every 5 seconds --> fetch('api/get_updates.php')
                              |
                              +--> PHP queries MySQL
                              |
                              +--> JSON: { stats, activity, unread_notifications }
                              |
                              +--> JS updates DOM (no page reload)
```

### What Updates Automatically

| Element | Update Behavior |
|---------|----------------|
| Stat card numbers | Text replaced, pulse animation if value changed |
| Activity feed | New items prepended at top, max 8 kept visible |
| Notification bell badge | Count shown/hidden |
| Vote counts | Instant on click (does not wait for poll cycle) |
| Chart data | On dropdown change via separate AJAX call |

### Polling Code

```javascript
// In dashboard.php / admin.php script block:
RealTime.init(userId, isAdmin);

// Inside realtime.js:
// - Calls poll() immediately on init
// - Then every 5000ms
// - Admin gets platform-wide stats
// - Users get only their own stats
```

### AJAX Voting Flow

```
Click vote button
  --> fetch POST /api/vote_idea.php { idea_id: X }
  --> PHP: check votes table (INSERT or DELETE)
  --> UPDATE ideas.vote_count
  --> Return { success, voted, vote_count }
  --> JS: toggle .voted class on button
  --> Update count text
  --> Show Toast notification
```

### Timezone Synchronization

PHP and MySQL are synchronized to Africa/Douala (WAT, UTC+1):

```php
// includes/db.php
date_default_timezone_set('Africa/Douala');
$pdo->exec("SET time_zone = '+01:00'");

// includes/functions.php
function timeAgo(string $dateStr): string {
    $tz   = new DateTimeZone('Africa/Douala');
    $now  = new DateTime('now', $tz);
    $then = new DateTime($dateStr, $tz);
    // ...
}
```

JavaScript uses WAT for display:
```javascript
then.toLocaleDateString('en-CM', { timeZone: 'Africa/Douala' });
```

---

## 8. All Functionalities Explained

### User Side

| Feature | How It Works |
|---------|-------------|
| Register | Form POST -> PHP validate -> password_hash() -> INSERT users -> flash -> redirect |
| Login | Email + password -> password_verify() -> session set -> redirect dashboard/admin |
| Logout | session_unset() + session_destroy() -> redirect login |
| Submit Idea | Form POST -> validate -> generateIdeaId() -> INSERT ideas -> log -> flash |
| Vote | Click -> AJAX POST -> INSERT or DELETE votes -> UPDATE vote_count -> instant UI |
| Track Idea | Enter Idea ID -> SELECT from DB -> show stepper + timeline |
| Browse | Category/search/sort filters -> dynamic SQL -> paginated card grid |
| Comment | POST form -> INSERT comments -> reload to #comments anchor |
| Dark Mode | Toggle pill -> body.classList toggle -> localStorage persist -> restored on load |
| Count-up | IntersectionObserver on [data-countup] -> JS counter over 1.2s |
| Scroll reveal | IntersectionObserver on .scroll-reveal -> opacity + translateY transition |

### Admin Side

| Feature | How It Works |
|---------|-------------|
| View Dashboard | Aggregated SQL queries -> stat cards + Chart.js + activity feed |
| Update Status | AJAX POST -> UPDATE ideas.status -> log activity -> badge updates in table |
| Assign Reviewer | AJAX POST -> UPDATE ideas.assigned_to -> stored in DB |
| Delete Idea | Confirm dialog -> AJAX POST -> DELETE CASCADE -> row fades out |
| Ban/Unban User | AJAX toggle -> UPDATE users.is_banned -> button/badge updates |
| Change User Role | Inline select onchange -> AJAX -> UPDATE users.role |
| Feature Idea | AJAX toggle -> UPDATE ideas.is_featured -> badge appears |
| Live Activity | Poller fetches activity_log -> prepends new items to DOM |
| Charts | Chart.js initialized with PHP data -> dropdown changes -> AJAX refetch |

### Global Features

| Feature | How It Works |
|---------|-------------|
| Responsive | CSS Grid + media queries: sidebar collapses to hamburger on mobile |
| Mobile Sidebar | .sidebar-toggle click -> .sidebar.open class -> CSS transform |
| Toast Notifications | JS creates div -> appended to fixed container -> auto-removed after 3.5s |
| Modal System | data-modal-open="id" -> .modal-overlay.open -> backdrop click closes |
| Live Table Search | Input event -> filter tbody rows by textContent match |
| Form Validation | validateForm() -> check [required] -> add .error class + show .form-error |
| CSRF Protection | csrfToken() generates + stores in session; verifyCsrf() validates on POST |

---

## 9. Database Schema

### users
```sql
id            INT UNSIGNED PK AUTO_INCREMENT
full_name     VARCHAR(120)     NOT NULL
email         VARCHAR(180)     NOT NULL UNIQUE
password_hash VARCHAR(255)     NOT NULL          -- bcrypt
role          ENUM('admin','user','investor')     DEFAULT 'user'
avatar        VARCHAR(255)     NULL
bio           TEXT             NULL
is_banned     TINYINT(1)       DEFAULT 0         -- 0=active 1=banned
last_login    DATETIME         NULL
created_at    DATETIME         DEFAULT NOW()     -- WAT
```

### categories (pre-seeded)
```sql
id    SMALLINT UNSIGNED PK
name  VARCHAR(80)              -- Technology, Social, Health, etc.
slug  VARCHAR(80) UNIQUE       -- technology, social, health, etc.
icon  VARCHAR(40)              -- Font Awesome class
color VARCHAR(20)              -- Hex color
```

### ideas
```sql
id             INT UNSIGNED PK AUTO_INCREMENT
idea_id        VARCHAR(20) UNIQUE              -- IDEA-A1B2C3D4
user_id        INT UNSIGNED FK -> users
category_id    SMALLINT UNSIGNED FK -> categories
title          VARCHAR(255)     NOT NULL
tagline        VARCHAR(255)     NULL
description    TEXT             NOT NULL
problem        TEXT             NULL
solution       TEXT             NULL
target_market  TEXT             NULL
funding_goal   DECIMAL(12,2)   NULL            -- NULL = no funding needed
funding_raised DECIMAL(12,2)   DEFAULT 0.00
status         ENUM('pending','review','approved','funded','closed')
priority       ENUM('high','medium','low')
assigned_to    INT UNSIGNED FK -> users NULL
vote_count     INT UNSIGNED     DEFAULT 0      -- denormalized for speed
view_count     INT UNSIGNED     DEFAULT 0      -- incremented on idea.php load
is_featured    TINYINT(1)       DEFAULT 0
tags           VARCHAR(500)     NULL            -- comma-separated
created_at     DATETIME                        -- WAT
updated_at     DATETIME         ON UPDATE NOW()
```

### votes
```sql
id         INT UNSIGNED PK
idea_id    INT UNSIGNED FK -> ideas (CASCADE DELETE)
user_id    INT UNSIGNED FK -> users (CASCADE DELETE)
created_at DATETIME                            -- WAT
UNIQUE KEY (idea_id, user_id)                 -- prevents double voting
```

### comments
```sql
id         INT UNSIGNED PK
idea_id    INT UNSIGNED FK -> ideas (CASCADE)
user_id    INT UNSIGNED FK -> users (CASCADE)
parent_id  INT UNSIGNED FK -> comments NULL    -- for threaded replies
body       TEXT             NOT NULL
created_at DATETIME                            -- WAT
```

### investments
```sql
id          INT UNSIGNED PK
idea_id     INT UNSIGNED FK -> ideas (CASCADE)
investor_id INT UNSIGNED FK -> users (CASCADE)
amount      DECIMAL(12,2)    NOT NULL
message     TEXT             NULL
status      ENUM('pledged','confirmed','withdrawn')
created_at  DATETIME                           -- WAT
```

### activity_log
```sql
id           INT UNSIGNED PK
idea_id      INT UNSIGNED FK -> ideas (CASCADE)
action       VARCHAR(255)     NOT NULL
performed_by INT UNSIGNED FK -> users (CASCADE)
status       VARCHAR(40)      NULL
timestamp    DATETIME                          -- WAT
```

### notifications
```sql
id         INT UNSIGNED PK
user_id    INT UNSIGNED FK -> users (CASCADE)
idea_id    INT UNSIGNED FK -> ideas NULL (CASCADE)
type       VARCHAR(60)
message    VARCHAR(500)
is_read    TINYINT(1)        DEFAULT 0
created_at DATETIME                            -- WAT
```

---

## 10. API Endpoints

### GET api/get_updates.php

Returns live stats and activity. Requires active PHP session.

Parameters:
- `?admin=1` — returns platform-wide data
- `?user_id=X` — returns data scoped to user X

Response:
```json
{
  "stats": {
    "total": 42,
    "pending": 8,
    "review": 5,
    "approved": 20,
    "funded": 6,
    "closed": 3,
    "users": 150
  },
  "activity": [
    {
      "id": 99,
      "idea_title": "AI Study Assistant",
      "action": "Status changed to approved",
      "status": "approved",
      "timestamp": "2025-03-21 14:30:00",
      "full_name": "Admin User"
    }
  ],
  "unread_notifications": 2
}
```

### POST api/vote_idea.php

Toggles vote for the authenticated user. Requires active session.

Request body (JSON):
```json
{ "idea_id": 5 }
```

Response:
```json
{ "success": true, "voted": true, "vote_count": 143 }
```

Error:
```json
{ "success": false, "message": "Please log in to vote." }
```

### GET api/get_chart_data.php

Returns time-series data for Chart.js line charts. Requires active session.

Parameters:
- `?period=7` (or 30 or 90)
- `?user_id=X` (optional filter)

Response:
```json
{
  "labels": ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
  "pending":  [3, 5, 2, 7, 4, 6, 3],
  "review":   [2, 4, 1, 5, 3, 4, 2],
  "approved": [1, 2, 0, 3, 2, 3, 1],
  "funded":   [0, 1, 0, 1, 0, 1, 0]
}
```

---

## 11. Idea Status Lifecycle

```
User submits idea
      |
      v
  PENDING  <-- Initial status. Visible to admin only.
      |
      v
UNDER REVIEW <-- Admin assigns reviewer. Idea enters queue.
      |
      v
  APPROVED  <-- Idea goes LIVE on marketplace. Community can vote.
      |
      v
   FUNDED   <-- Investors pledge full funding goal.
   
(Any stage can transition to)
   CLOSED   <-- Admin rejects or closes the idea.
```

Every transition creates a row in `activity_log` with action text, performer, status, and WAT timestamp.

---

## 12. User Roles & Permissions

| Feature                    | Visitor | User | Admin |
|----------------------------|---------|------|-------|
| View landing page          | YES     | YES  | YES   |
| Browse marketplace         | YES     | YES  | YES   |
| Track idea by ID           | YES     | YES  | YES   |
| Read idea details          | YES     | YES  | YES   |
| Register / Login           | YES     | YES  | YES   |
| Submit idea                | NO      | YES  | YES   |
| Vote on ideas              | NO      | YES  | YES   |
| Post comments              | NO      | YES  | YES   |
| User dashboard             | NO      | YES  | YES   |
| View own ideas             | NO      | YES  | YES   |
| Admin dashboard            | NO      | NO   | YES   |
| Update idea status         | NO      | NO   | YES   |
| Assign reviewers           | NO      | NO   | YES   |
| Delete ideas               | NO      | NO   | YES   |
| Manage users               | NO      | NO   | YES   |
| Ban / unban users          | NO      | NO   | YES   |
| Change user roles          | NO      | NO   | YES   |
| Feature ideas              | NO      | NO   | YES   |

---

## 13. Design System

### Color Palette
```
Navy dark:   #0f2347   -- Sidebar top gradient, hero backgrounds
Navy:        #1a3a6b   -- Sidebar body
Navy light:  #1e4080   -- Sidebar bottom gradient
Blue:        #2563eb   -- Primary buttons, blue stat cards
Blue light:  #3b82f6   -- Button hover state
Orange:      #f97316   -- Pending status, orange stat cards
Green:       #16a34a   -- Approved/success
Green dark:  #15803d   -- Completed dark variant
Red:         #dc2626   -- Closed/danger
Purple:      #7c3aed   -- Funded/special
Gold:        #f59e0b   -- Admin crown, gold accents
BG light:    #f0f4f8   -- Page background (light mode)
Card light:  #ffffff   -- Card background (light mode)
BG dark:     #0f172a   -- Page background (dark mode)
Card dark:   #1e293b   -- Card background (dark mode)
```

### Typography
- Headings: Syne from Google Fonts — geometric, bold, modern
- Body text: DM Sans from Google Fonts — clean, highly readable

### Key CSS Classes
```
Stat cards:    .stat-card.blue / .orange / .green / .purple / .red
Status badges: .badge-pending / .badge-review / .badge-approved / .badge-funded / .badge-closed
Priority:      .badge-high / .badge-medium / .badge-low
Buttons:       .btn-primary / .btn-outline / .btn-navy / .btn-danger / .btn-success
Animations:    .fade-in / .fade-up / .delay-1 through .delay-4 / .scroll-reveal
Activity dots: .activity-dot.orange / .green / .blue / .purple / .red
```

---

## 14. Security Features

| Feature | Implementation |
|---------|----------------|
| Password hashing | password_hash() with bcrypt default cost |
| SQL injection | All user input via PDO prepared statements — no raw SQL |
| XSS prevention | htmlspecialchars() via e() helper on all output |
| Session security | session_regenerate_id(true) on every login |
| CSRF protection | csrfToken() generates token, verifyCsrf() validates on POST |
| Role enforcement | requireLogin() and requireAdmin() on every protected page |
| Ban enforcement | is_banned = 0 checked on every login attempt |
| Self-protection | Admin cannot ban or change role of their own account |
| API authentication | All API endpoints check isLoggedIn() before responding |

---

## 15. Customization Guide

### Change Timezone
```php
// includes/db.php
date_default_timezone_set('America/New_York'); // PHP
$pdo->exec("SET time_zone = '-05:00'");        // MySQL (match above)
```

### Change Site Name
```php
// includes/db.php
define('SITE_NAME', 'YourBrandName');
```

### Change Primary Color
```css
/* assets/css/main.css :root block */
--blue:   #your-color;      /* Primary action color */
--navy:   #your-dark-color; /* Sidebar / headers */
```

### Add a Category
```sql
INSERT INTO categories (name, slug, icon, color) 
VALUES ('Healthcare', 'healthcare', 'fa-hospital', '#dc2626');
```

### Change Polling Interval
```javascript
// assets/js/realtime.js
this.timer = setInterval(() => this.poll(), 10000); // 10 seconds
```

### Add Email Notifications
1. Install PHPMailer: `composer require phpmailer/phpmailer`
2. In `includes/functions.php` > `logActivity()`, add email send logic when status changes to 'approved' or 'funded'

---

## 16. Troubleshooting

| Problem | Fix |
|---------|-----|
| Blank white page | Add `ini_set('display_errors', 1);` to top of `includes/db.php` to see errors |
| Database connection failed | Double-check DB_HOST, DB_USER, DB_PASS, DB_NAME in includes/db.php |
| "Table does not exist" | Re-import `database.sql` in phpMyAdmin |
| Cannot login as admin | Run in MySQL: `UPDATE users SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE email='admin@ideamarket.com'` (sets password to "password") |
| Charts not appearing | Check browser console for Chart.js CDN errors. Ensure internet connection. |
| Wrong timezone on timestamps | Verify: `echo date_default_timezone_get();` should output `Africa/Douala` |
| File upload not working | Ensure `assets/uploads/` folder exists and is writable (chmod 755) |
| Sidebar broken on mobile | Ensure app.js is loaded correctly — check browser console for JS errors |
| AJAX vote not working | Check path `api/vote_idea.php` is accessible from the page calling it |
| Real-time not updating | Check browser console for fetch errors; verify session has not expired |
| Sessions expiring too fast | Increase `session.gc_maxlifetime` in php.ini |

---

## Quick Reference

```
URLs:
  /                        index.php         (landing home)
  /login.php               Login + Register
  /login.php?tab=register  Jump to register tab
  /browse.php              Public marketplace
  /browse.php?cat=tech     Filter by category
  /idea.php?id=X           Single idea page
  /track.php               Track by Idea ID
  /track.php?id=IDEA-XXX   Direct track URL
  /dashboard.php           User dashboard (auth required)
  /submit-idea.php         Submit form (auth required)
  /my-ideas.php            My ideas (auth required)
  /admin.php               Admin overview (admin only)
  /admin-ideas.php         Manage ideas (admin only)
  /admin-users.php         Manage users (admin only)
  /logout.php              Log out

API:
  GET  /api/get_updates.php?admin=1      Live stats (admin)
  GET  /api/get_updates.php?user_id=X   Live stats (user)
  POST /api/vote_idea.php               Toggle vote
  GET  /api/get_chart_data.php?period=7 Chart data (7/30/90 days)

Default Admin Login:
  Email:    admin@ideamarket.com
  Password: password

Timezone:
  PHP:   Africa/Douala (WAT, UTC+1)
  MySQL: SET time_zone = '+01:00'
  JS:    timeZone: 'Africa/Douala'
```

---

*IdeaMarket v1.0 — PHP + MySQL + Vanilla JS*
*Timezone: Africa/Douala (WAT, UTC+1) — Cameroon*
*Built for transparent, community-driven idea management.*
# idea-market
