# BEATS: Bluetooth Enabled Attendance Tracking System

BEATS is a modern, automated student attendance tracking platform designed to eliminate the friction of manual roll-calls. Built on the VILT stack (Vue, Inertia, Laravel, Tailwind), BEATS leverages a robust Bluetooth Low Energy (BLE) ecosystem and dynamic QR codes to provide seamless check-ins. A sophisticated built-in gamification engine motivates student participation through XP, streaks, levels, and badges.

---

## 🌟 Key Features

### Seamless Attendance Ecosystem
*   **Proximity-based BLE Check-ins:** Students automatically mark their attendance via mobile devices when in range of classroom BLE beacons.
*   **Dynamic QR Codes:** A fallback method utilizing time-sensitive, rotating QR codes projected by the lecturer.
*   **Automated Processing:** Background jobs automatically classify missing students as 'Absent' once a class session concludes.

### Gamification Engine
*   **XP & Leveling:** Students earn XP for on-time and present check-ins, leveling up their gamification profiles.
*   **Streaks:** Consecutive check-ins build streaks that award multiplier bonuses.
*   **Achievement Badges:** Custom badges (e.g., "Present Check-ins", "Total XP") are automatically awarded when requirements are met.
*   **Leaderboards:** Global and course-specific leaderboards with automated snapshots foster healthy competition.
*   **Reward Redemptions:** Students can exchange earned XP/currency for real-world rewards.

### Multi-Role Dashboards
*   **Administrator Portal:** Total system control including user management, course/session scheduling, BLE device assignments, gamification asset creation, and system-wide analytics.
*   **Lecturer Portal:** Tools to manage daily classroom attendance, generate QR codes, view course-specific reports, process student leave applications, and identify "at-risk" students.
*   **Student App (API):** A dedicated, secure API layer for the mobile app, allowing students to check in, view their timetables, track their attendance history, and monitor their gamification progress.

### System-Wide Audit Logging
*   **Full Accountability:** An automated `Auditable` trait tracks every creation, update, and deletion across all primary models.
*   **Transparent History:** An administrative interface provides a searchable, filterable ledger of exactly who changed what, complete with old and new values.

---

## 🛠️ Technology Stack

*   **Framework:** Laravel 11.x
*   **Frontend:** Vue.js 3 + Inertia.js
*   **Styling:** Tailwind CSS + Lucide Icons
*   **Database:** MySQL
*   **Authentication:** Laravel Sanctum (API) & Session based (Web)
*   **Task Scheduling:** Laravel Task Scheduler

---

## 🚀 Setup & Installation

### Prerequisites
*   PHP >= 8.2
*   Composer
*   Node.js & NPM
*   MySQL

### Local Development

1.  **Clone the repository:**
    ```bash
    git clone https://github.com/NameNami/beats-vilt-backend.git
    cd beats-vilt-backend
    ```

2.  **Install PHP dependencies:**
    ```bash
    composer install
    ```

3.  **Install Node dependencies:**
    ```bash
    npm install
    ```

4.  **Environment Setup:**
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```
    *Update the `.env` file with your local MySQL database credentials.*

5.  **Database Migration & Seeding:**
    Run the migrations and seed the database with initial testing data (users, courses, sessions, beacons, etc.):
    ```bash
    php artisan migrate:fresh --seed
    ```

6.  **Compile Frontend Assets:**
    ```bash
    npm run dev
    ```

7.  **Serve the Application:**
    ```bash
    php artisan serve
    ```

8.  **Run the Scheduler (Optional but recommended):**
    To ensure background tasks (like beacon rotation and automated absences) fire:
    ```bash
    php artisan schedule:work
    ```

---

## 🔐 Testing Accounts

The database seeder creates standard testing accounts:
*   **Admin:** `admin@beats.com` / `password`
*   **Lecturer:** `lecturer@beats.com` / `password`
*   **Student:** `student@beats.com` / `password`

---

## 🧪 Testing

The project is thoroughly tested using Pest. To run the test suite:

```bash
php artisan test
```