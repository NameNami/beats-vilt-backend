# BEATS: Bluetooth Enabled Attendance Tracking System

BEATS is a modern, automated student attendance tracking platform designed to eliminate the friction of manual roll-calls. Built on the VILT stack (Vue, Inertia, Laravel, Tailwind), BEATS leverages a robust Bluetooth Low Energy (BLE) ecosystem and dynamic QR codes to provide seamless check-ins. A sophisticated built-in gamification engine motivates student participation through XP, streaks, levels, and badges.

This repository is the central backend API and administration web portal (`beats.namix.my`). 
https://beats.namix.my

### 🔗 Related Repositories
BEATS is a multi-component ecosystem. You can find the related projects here:
*   [**beats-android-app**](https://github.com/NameNami/beats-android-app) - The mobile application used by students to scan BLE beacons and QR codes.
*   [**beats-esp32-ble-beacon**](https://github.com/NameNami/beats-esp32-ble-beacon) - The C++ firmware for the ESP32 classroom beacons that broadcast attendance signals.

---

## 🌟 Key Features

### Seamless Attendance Ecosystem
*   **Proximity-based BLE Check-ins:** Students automatically mark their attendance via mobile devices when in range of classroom BLE beacons.
*   **Dynamic Rotating QR Codes:** A fallback method utilizing time-sensitive QR codes that automatically rotate every 15-30 seconds with a 5-second grace period to prevent unauthorized sharing.
*   **Automated Processing:** Background jobs automatically classify missing students as 'Absent' once a class session concludes.
*   **Weekly At-Risk Automation:** An automated cronjob runs every Sunday to calculate attendance percentages and notify students who fall below the required threshold (e.g., 80%).

### Gamification Engine
*   **XP & Leveling:** Students earn XP for on-time and present check-ins, leveling up their gamification profiles.
*   **Streaks:** Consecutive check-ins build streaks that award multiplier bonuses.
*   **Achievement Badges:** Custom badges (e.g., "Present Check-ins", "Total XP") are automatically awarded when requirements are met.
*   **Leaderboards:** Global and course-specific leaderboards with automated snapshots foster healthy competition.
*   **Reward Redemptions:** Students can exchange earned XP/currency for real-world rewards.

### Multi-Role Dashboards
*   **Administrator Portal:** Total system control including user management, course/session scheduling, and BLE device assignments.
    *   **Refactored Lecturer Assignments:** Supports assigning a single lecturer to multiple specific labs within a course via direct database ownership.
    *   **Weekly Recurrence Scheduling:** Effortlessly auto-generate class sessions for the entire semester with intelligent conflict detection for every recurring instance.
    *   **Robust Bulk Import:** Provision student accounts via CSV with improved parsing for various file encodings and automated validation.
    *   **System-Wide Analytics:** Visual dashboards for monitoring attendance trends and system performance.
    *   **UX Refinements:** Consistent interactive feedback across the portal with optimized pointer cursors and responsive elements.
*   **Lecturer Portal:** Tools to manage daily classroom attendance, generate rotating QR codes, view course-specific reports, and process student leave applications.
    *   **Enhanced Session Visibility:** Improved session card styling with clear indicators for 'Cancelled', 'Active', 'Upcoming', and 'Passed' statuses.
*   **Student App (API):** A dedicated, secure API layer for the mobile app, allowing students to check in, view their timetables, and monitor their gamification progress.

### System-Wide Audit Logging
*   **Full Accountability:** An automated `Auditable` trait tracks every creation, update, and deletion across all primary models.
*   **Mass Broadcast Logging:** Manual audit logging for global announcement broadcasts ensures administrative transparency.
*   **Transparent History:** A filterable ledger providing a detailed record of exactly who changed what, complete with old and new values.

---

## 🛠️ Technology Stack

*   **Framework:** Laravel 13.x
*   **Frontend:** Vue.js 3 + Inertia.js
*   **Styling:** Tailwind CSS + Lucide Icons
*   **Database:** MySQL 8.0
*   **Authentication:** Laravel Sanctum (API) & Session based (Web)
*   **Task Scheduling:** Laravel Task Scheduler

---

## 🚀 Setup & Installation

### Prerequisites
*   **PHP >= 8.3 (Mandatory)**
*   Composer
*   Node.js & NPM
*   MySQL 8.0
*   *Alternatively, Docker & Docker Compose*

### Local Development

1.  **Clone the repository:**
    ```bash
    git clone https://github.com/NameNami/beats-vilt-backend.git
    cd beats-vilt-backend
    ```

2.  **Install dependencies:**
    ```bash
    composer install
    npm install
    ```

3.  **Environment Setup:**
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```
    *Update the `.env` file with your local MySQL database credentials.*

4.  **Database Migration & Seeding:**
    Run the migrations and seed the database with initial testing data (users, courses, sessions, beacons, etc.) for a consistent 14-week timeline:
    ```bash
    php artisan migrate:fresh --seed
    ```

5.  **Compile Frontend Assets:**
    ```bash
    npm run dev
    ```

6.  **Serve the Application:**
    ```bash
    php artisan serve
    ```

7.  **Run the Scheduler:**
    To ensure background tasks (like dynamic QR rotation, beacon monitoring, and weekly notifications) fire:
    ```bash
    php artisan schedule:work
    ```

---

## 🔐 Testing Accounts

The seeder automatically provisions the database with several accounts across all roles.

**Admin Account:**
*   Email: `admin@beats.namix.my`
*   Password: `password`

**Lecturer Accounts:**
*   Email: `azrai@beats.namix.my` / `hafiz@beats.namix.my` / `suraya@beats.namix.my`
*   Password: `password`

**Student Accounts: in Mobile App ONLY**
*   Username: Uses Student IDs (`52101324169`)
*   Password: password
*   Seeded Students: Check the `users` table after seeding.

---

## 🧪 Testing

The project is thoroughly tested using Pest. To run the test suite:

```bash
php artisan test
```
