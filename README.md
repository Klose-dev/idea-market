# IdeaMarket

A full-stack idea marketplace web application where users submit innovation ideas, the community votes, and admins review and manage the platform. Built with PHP, MySQL, and Vanilla JavaScript.

## Features

- **User System**: Register, login, and manage profiles with role-based access (user, admin, investor)
- **Idea Management**: Submit ideas with unique Idea IDs, descriptions, categories, tags, and funding goals
- **Voting System**: Community voting with AJAX — no page reloads
- **Real-Time Updates**: 5-second polling for live stats, activity feeds, and notifications
- **Admin Dashboard**: Manage ideas, users, categories, and view platform analytics
- **Charts**: Interactive charts for idea trends and status distributions using Chart.js
- **Idea Tracking**: Public tracker with progress stepper and activity timeline
- **Comments & Engagement**: Comment on ideas with threaded replies
- **Dark Mode**: Toggle between light and dark themes
- **Security**: CSRF protection, password hashing, SQL injection prevention

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Frontend | HTML5, CSS3, Vanilla JS (ES2020+) |
| Charts | Chart.js 4.4 (CDN) |
| Icons | Font Awesome 6.5 (CDN) |
| Backend | PHP 8.0+ with PDO prepared statements |
| Database | MySQL 5.7+ / MariaDB 10.3+ |
| Auth | PHP sessions + password_hash (bcrypt) |

## File Structure

```
idea-market/
├── index.php              # Landing page
├── about.php              # About page
├── login.php              # Login + Register
├── register.php           # Redirects to login
├── logout.php             # Destroys session
├── browse.php             # Marketplace browser
├── idea.php               # Single idea detail
├── track.php              # Public idea tracker
├── dashboard.php          # User dashboard
├── submit-idea.php        # Idea submission form
├── my-ideas.php           # User's ideas list
├── admin.php              # Admin overview
├── admin-ideas.php        # Manage ideas
├── admin-users.php        # Manage users
├── database.sql           # MySQL schema + seed data
├── includes/
│   ├── db.php             # DB connection + timezone
│   ├── auth.php           # Session, login, register, CSRF
│   └── functions.php      # Helper functions
├── api/
│   ├── get_updates.php    # Real-time polling
│   ├── vote_idea.php      # AJAX vote toggle
│   └── get_chart_data.php # Chart data endpoint
└── assets/
    ├── css/
    │   ├── main.css       # Design system
    │   └── landing.css    # Landing page styles
    └── js/
        ├── app.js         # Core JS (dark mode, modals, toasts)
        ├── charts.js      # Chart.js configurations
        └── realtime.js    # Polling engine + AJAX voting
```

## Installation

### Prerequisites
- PHP 8.0 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Apache with mod_rewrite (XAMPP / WAMP / LAMP)

### Setup

1. **Place files** in your web root:
   ```
   XAMPP:  C:/xabmp/htdocs/idea-market/
   WAMP:   C:/wamp64/www/idea-market/
   Linux:  /var/www/html/idea-market/
   ```

2. **Import database**:
   ```bash
   mysql -u root -p < database.sql
   ```

3. **Configure database connection** in `includes/db.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'idea_market');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('SITE_URL', 'http://localhost/idea-market');
   ```

4. **Create uploads directory**:
   ```bash
   mkdir -p assets/uploads
   chmod 755 assets/uploads
   ```

5. **Start & visit**:
   ```bash
   cd idea-market
   php -S localhost:8000
   # Visit: http://localhost:8000
   ```

## Login Credentials

| Field | Value |
|-------|-------|
| Email | admin@ideamarket.com |
| Password | password |
| Role | Administrator |

> Change the admin password immediately after first login.

## Default Admin Login

- Email: `admin@ideamarket.com`
- Password: `password`

## Timezone

- PHP: `Africa/Douala` (WAT, UTC+1)
- MySQL: `SET time_zone = '+01:00'`
- JS: `timeZone: 'Africa/Douala'`

## License

Built for transparent, community-driven idea management.