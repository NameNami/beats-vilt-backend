# BEATS: Bluetooth Enabled Attendance Tracking System

BEATS is a modern, automated student attendance tracking platform designed to eliminate the friction of manual roll-calls. Built on the VILT stack (Vue, Inertia, Laravel, Tailwind), BEATS leverages a robust Bluetooth Low Energy (BLE) ecosystem and dynamic QR codes to provide seamless check-ins. A sophisticated built-in gamification engine motivates student participation through XP, streaks, levels, and badges.

This repository is the central backend API and administration web portal (`beats.namix.my`). 

### 🔗 Related Repositories
BEATS is a multi-component ecosystem. You can find the related projects here:
*   [**beats-android-app**](https://github.com/NameNami/beats-android-app) - The mobile application used by students to scan BLE beacons and QR codes.
*   [**beats-esp32-ble-beacon**](https://github.com/NameNami/beats-esp32-ble-beacon) - The C++ firmware for the ESP32 classroom beacons that broadcast attendance signals.

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

### Method 1: Docker (Recommended for Production/Staging)

The project includes a `docker-compose.yml` pre-configured for the backend, database, and an optional Cloudflare tunnel.

1.  **Clone the repository:**
    ```bash
    git clone https://github.com/NameNami/beats-vilt-backend.git
    cd beats-vilt-backend
    ```

2.  **Environment Setup:**
    ```bash
    cp .env.example .env
    ```
    *Ensure you populate your `.env` with the correct DB credentials and `CLOUDFLARE_TUNNEL_TOKEN` if exposing it.*

3.  **Deploy via Docker Compose:**
    ```bash
    docker compose up -d
    ```
    This will spin up `beats_api` (ghcr image), `beats_db` (MySQL), and `cloudflare_tunnel`.

### Method 2: Local Development

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
    Run the migrations and seed the database with initial testing data (users, courses, sessions, beacons, etc.):
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

7.  **Run the Scheduler (Optional but recommended):**
    To ensure background tasks (like beacon rotation and automated absences) fire:
    ```bash
    php artisan schedule:work
    ```

---

## 🔐 Testing Accounts

The seeder automatically provisions the database with several accounts across all roles. The primary domain configured for the app is `beats.namix.my`.

**Admin Account:**
*   Email: `admin@beats.namix.my`
*   Password: `password`

**Lecturer Accounts:**
*   Email: `azrai@beats.namix.my` / `hafiz@beats.namix.my` / `suraya@beats.namix.my`
*   Password: `password`

**Student Accounts: (Only in mobile app)**
*   Email: `najmi@student.beats.namix.my` / `khaizuran@student.beats.namix.my` (and various others generated by the seeder)
*   Password: `password`

---

## 🧪 Testing

The project is thoroughly tested using Pest. To run the test suite:

```bash
php artisan test
```
