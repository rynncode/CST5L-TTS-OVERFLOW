# Overflow — Task Tracking System

A dark-themed, database-driven task management web application built with PHP and MySQL. Users can register, log in, and manage their personal tasks through a clean dashboard with full CRUD functionality.

> **Live Demo:** [your-railway-url.up.railway.app](https://your-railway-url.up.railway.app)  
> **GitHub:** [github.com/rynncode/CST5L-TTS-OVERFLOW](https://github.com/rynncode/CST5L-TTS-OVERFLOW)  
> **Video Presentation:** [add your link here]

---

## Project Description

Overflow is a personal task tracker that allows users to create and manage tasks organized by priority and status. The dashboard provides an at-a-glance overview of all tasks through stat counters, a due date urgency widget, and a filterable, searchable task table. Each task supports a title, description, priority level, status, and an optional due date with overdue highlighting.

---

## Features

- **Authentication** — Register, login, logout with session management and Remember Me
- **Dashboard** — Task stats (Total, Pending, In Progress, Completed), due date widget, search and filter
- **CRUD Operations** — Create, Read, Update, and Delete tasks
- **Task Fields** — Title, description, priority (Low/Medium/High), status (Pending/In Progress/Completed), due date
- **Dynamic Filtering** — Filter tasks by status or priority from the sidebar
- **Search** — Search tasks by title or description
- **Quick Status Toggle** — Mark tasks complete directly from the dashboard or detail view
- **Form Validation** — Server-side input validation with user-friendly error messages
- **Security** — PDO prepared statements (SQL injection prevention), password hashing with bcrypt, session-based auth guards on all protected pages

---

## Technologies Used

| Layer | Technology |
|---|---|
| Backend | PHP 8.3 |
| Database | MySQL 8 |
| Frontend | Vanilla CSS, Vanilla JS, Inline SVG |
| Fonts | Space Grotesk, Bebas Neue (Google Fonts) |
| Deployment | Railway (app + database) |
| Version Control | Git + GitHub |

No external frameworks or libraries — everything is built from scratch.

---

## Project Structure

```
CST5L-TTS-OVERFLOW/
├── config/
│   └── database.php        # DB connection (PDO), session helpers, sanitize()
├── css/
│   └── style.css           # Custom dark design system
├── database/
│   └── project.sql         # Database schema and sample data
├── tasks/
│   ├── add.php             # Create a new task
│   ├── edit.php            # Update an existing task
│   ├── view.php            # View task details + quick status change
│   └── delete.php          # Delete a task
├── dashboard.php           # Main dashboard with stats, filters, search
├── login.php               # User login
├── register.php            # User registration
├── logout.php              # Session destroy
├── index.php               # Entry point (redirects to dashboard or login)
├── transition.php          # Login animation transition
├── logout-transition.php   # Logout animation transition
├── Dockerfile              # Docker config for Railway deployment
└── .htaccess               # Apache rewrite rules
```

---

## Database Structure

### `users` table
| Column | Type | Description |
|---|---|---|
| `id` | INT, AUTO_INCREMENT, PK | Unique user ID |
| `username` | VARCHAR(50), UNIQUE | Username |
| `email` | VARCHAR(100), UNIQUE | Email address |
| `password` | VARCHAR(255) | Bcrypt hashed password |
| `created_at` | TIMESTAMP | Registration date |

### `tasks` table
| Column | Type | Description |
|---|---|---|
| `id` | INT, AUTO_INCREMENT, PK | Unique task ID |
| `user_id` | INT, FK → users.id | Owner of the task |
| `title` | VARCHAR(200) | Task title |
| `description` | TEXT | Optional task details |
| `priority` | ENUM('low','medium','high') | Priority level |
| `status` | ENUM('pending','in_progress','completed') | Current status |
| `due_date` | DATE | Optional due date |
| `created_at` | TIMESTAMP | Creation date |
| `updated_at` | TIMESTAMP | Last modified date |

> `user_id` has `ON DELETE CASCADE` — deleting a user removes all their tasks.

---

## Setup Instructions

### Local Setup (XAMPP)

1. Clone the repository:
   ```bash
   git clone https://github.com/rynncode/CST5L-TTS-OVERFLOW.git
   ```
2. Move the folder to your XAMPP `htdocs` directory
3. Start Apache and MySQL in XAMPP
4. Open **phpMyAdmin** and create a database named `taskflow_db`
5. Import `database/project.sql` into the database
6. Open your browser and go to `http://localhost/CST5L-TTS-OVERFLOW`

### Deployed Version (Railway)

The app is deployed on Railway with a MySQL database. No local setup needed — just visit the live link above.

**Default demo account:**
- Username: `demo`
- Password: `password123`

---

## Dynamic Features

1. **Session-based Authentication** — Login state persists across pages; all protected routes check `$_SESSION['user_id']`
2. **Search & Filtering** — Dynamic WHERE clause built at runtime based on GET parameters
3. **Form Validation** — Server-side validation with field-specific error messages and form repopulation on failure
4. **Remember Me** — Extends session cookie lifetime to 30 days
5. **Quick Status Toggle** — Updates task status via POST without leaving the current page

---

## Screenshots

> *(Add screenshots here)*

---

## Video Presentation

> *(Add Google Drive link here)*
