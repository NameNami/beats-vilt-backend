# User Acceptance Testing (UAT) Questionnaire: BEATS Platform

This document maps directly to the official project requirements and responsibility matrix to ensure all documented features are fully functional.

## 1. Administrator Role (System Administration & Academic Ops)

### User & Role Management
*   [ ] **Manual Account Control:** Can you create, edit, and deactivate user accounts for both students and lecturers?
*   [ ] **Bulk Import (CSV):** Can you provision student accounts via CSV and verify they are assigned to the correct Programme?
*   [ ] **Role Assignment:** Can you successfully change a user's system role (Student/Lecturer/Admin)?

### Course & Scheduling (Academic Ops)
*   [ ] **Roster Management:** Can you enroll multiple students into a course/lab at once?
*   [ ] **Lecturer Assignment:** Can you assign a single lecturer to multiple specific labs within a course?
*   [ ] **Recurring Scheduling:** Does the weekly recurrence feature auto-generate sessions with intelligent conflict checks for every week?
*   [ ] **Conflict Resolution:** Does the system prevent double-booking a room, lecturer, or lab group?

### Beacon & Device Registry (Hardware Layer)
*   [ ] **Beacon Registration:** Can you register a new ESP32 BLE beacon and map it to a specific room?
*   [ ] **Signal Calibration:** Are you able to configure the RSSI (signal strength) threshold per beacon to define the proximity zone?
*   [ ] **Status Monitoring:** Does the "System Health" or Beacon dashboard show the `last_seen` heartbeat timestamp from the physical beacons?
*   [ ] **Deactivation:** Can you deactivate a beacon without losing its historical check-in data?

### Gamification & Reward Administration
*   [ ] **Reward Catalog:** Can you add, edit, or remove items (e.g., Hoodie, Voucher) from the rewards catalogue?
*   [ ] **Inventory Control:** Can you manually update the stock levels and point costs for rewards?
*   [ ] **Badge Milestones:** Can you view/manage the automated requirements for badges (Total XP, Streaks)?

### Global Settings & Audit
*   [ ] **Attendance Rules:** Can you configure the early check-in window, late cutoff time, and the 80% attendance threshold?
*   [ ] **Audit Ledger:** Can you access the full system audit log and see a history of admin actions (e.g., who deleted which user)?
*   [ ] **QR Interval:** Can you set the refresh interval for dynamic QR codes?

---

## 2. Lecturer Role (Classroom Management & Intervention)

### Live Session Control
*   [ ] **Attendance Window:** Can you manually open and close the attendance window for a session?
*   [ ] **Live Monitoring:** During a session, does the student list update in real-time as they check in via BLE or QR?
*   [ ] **Manual Overrides:** Can you manually change a student's status (e.g., from Absent to Present) if they forget their phone?
*   [ ] **Dynamic QR:** Can you display a live, rotating QR code on your screen for student scanning?

### Leave & Absence Workflow
*   [ ] **Application Review:** Can you see a list of student leave applications with their attached supporting documents (MC/Letters)?
*   [ ] **Approval/Rejection:** Does approving a leave application automatically exclude that session from the student's attendance percentage calculation?
*   [ ] **Leave History:** Can you view a complete historical log of all leave requests for a specific student?

### Analytics & Proactive Intervention
*   [ ] **At-Risk Flagging:** Does the dashboard automatically highlight students who have fallen below the 80% threshold?
*   [ ] **Arrival Breakdown:** Can you see the ratio of Early vs. On-Time vs. Late arrivals for your classes?
*   [ ] **Historical Logs:** Can you pull a complete history of all sessions conducted for a specific course?
*   [ ] **Data Export:** Can you export your class attendance reports as a CSV or PDF file?

---

## 3. General Platform Experience (VILT Stack UX)

### Interactive Feedback
*   [ ] **Consistent Cursors:** Do all buttons, links, and `@click` elements across both portals show a `cursor-pointer`?
*   [ ] **Status Clarity:** Are session cards color-coded and labeled correctly (Passed, Active, Upcoming, Cancelled)?
*   [ ] **Responsiveness:** Does the dashboard maintain usability on different screen sizes?
*   [ ] **Navigation Flow:** Is the sidebar navigation logical and consistent with the documented features?
