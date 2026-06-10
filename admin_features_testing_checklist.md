# Admin Features Testing Checklist

This checklist covers all the administrative functionalities implemented in the backend controllers. Use this document to verify that each feature works as expected.

## 1. Dashboard (`AdminController`)
- [ ] View Dashboard statistics (Total Students, Lecturers, Courses, Active Rooms).

## 2. Course Management (`AdminController`)
- [ ] View list of courses and their enrollments/labs.
- [ ] Create a new course (validate unique code, name, faculty).
- [ ] Edit an existing course (validate updates, unique code).
- [ ] Delete a course.

## 3. Lab Management (`AdminController`)
- [ ] Create a new lab for a course (assign lecturer, set capacity).
- [ ] Delete a lab.

## 4. Class Session Management (`AdminController`)
- [ ] View class sessions mapped by week, with course and faculty filters.
- [ ] Verify visual representation (color coding, ongoing/cancelled status).
- [ ] Create a new class session (validate online/physical mode, check-in method).
- [ ] Verify scheduling conflict detection (Room, Lecturer, or Lab Group overlap).
- [ ] Edit an existing class session (verify conflict detection still works).
- [ ] Delete a class session.

## 5. Lecturer Management (`AdminController`)
- [ ] View list of lecturers and their course assignments.
- [ ] Assign a lecturer to a specific course.
- [ ] Remove a lecturer's assignment from a course.

## 6. Student Management (`AdminController`)
- [ ] View list of students and their course/lab enrollments.
- [ ] Enroll a student in a course and assign them to a lab.
- [ ] Verify lab capacity limits during student enrollment.

## 7. User Management (`AdminController`)
- [ ] View all users grouped by role and sorted by name.
- [ ] Create a new user account (Admin, Lecturer, or Student).
- [ ] Bulk import students via CSV (verify default password is set to student ID).
- [ ] Edit an existing user's details and role.
- [ ] Change a user's password.
- [ ] Delete a user account.
- [ ] Verify self-deletion protection (Admin cannot delete their own active account).

## 8. Audit Logs (`AdminAuditLogController`)
- [ ] View paginated audit logs.
- [ ] Search logs by model type, user name, or email.
- [ ] Filter logs by specific action.
- [ ] Filter logs by date range (today, last 7 days, last 30 days).
- [ ] Sort logs by allowed columns (`created_at`, `action`, `model_type`).

## 9. BLE Device Management (`AdminBleDeviceController`)
- [ ] View list of all BLE beacons with their assigned rooms and status.
- [ ] Update beacon details (assign room, set status, adjust RSSI threshold).
- [ ] Verify that assigning `null` room automatically forces status to "Unassigned".
- [ ] Unassign a beacon from a room.
- [ ] Trigger device scan (placeholder function).

## 10. Broadcast Notifications (`AdminBroadcastController`)
- [ ] View history of past broadcasts (grouped by title/body).
- [ ] Send a new broadcast to all students.
- [ ] Send a new broadcast to all lecturers.
- [ ] Send a new broadcast to all users.
- [ ] Send a new broadcast to a specific faculty.
- [ ] Verify chunked insertion of notifications works without memory issues.

## 11. Gamification Management (`AdminGamificationController`)
- **Badges**
  - [ ] View list of badges.
  - [ ] Create a new badge (validate requirement types: present/on-time check-ins, streaks, xp).
  - [ ] Edit an existing badge.
  - [ ] Delete a badge.
- **Rewards & Redemptions**
  - [ ] View list of rewards and past redemptions.
  - [ ] Create a new reward (set cost points, stock limit, active status).
  - [ ] Edit a reward.
  - [ ] Delete a reward.
  - [ ] Update redemption status (Pending, Approved, Rejected, Collected).

## 12. Leave Management (`AdminLeaveController`)
- [ ] Search for a student by name or ID.
- [ ] View recently approved leaves.
- [ ] Apply global admin override leave for a student (Select dates and reason).
- [ ] Verify leave application skips sessions not meant for the student (e.g., different lab).
- [ ] Verify that applying leave forcefully approves pending/rejected requests.
- [ ] Verify that applying leave creates or updates the attendance record to `leave`.

## 13. System Settings & Profile (`AdminSettingsController`)
- **System Settings**
  - [ ] View global system settings (App name, Semester data, Gamification XP values, etc.).
  - [ ] Update global settings.
  - [ ] Verify JSON encoding/decoding of non-teaching weeks array.
- **Profile Settings**
  - [ ] Update Admin profile details (Username, Name).
  - [ ] Update Admin password.
  - [ ] Upload/Update Admin profile photo.
  - [ ] Delete Admin profile photo.

## 14. System Administration (`AdminSystemController`)
- **System Health**
  - [ ] View Database size, connection driver, PHP/Laravel versions, and debug status.
- **Database Backup**
  - [ ] Download database backup (Verify it works for the active SQLite/MySQL driver).
- **Semester Rollover**
  - [ ] Execute a semester rollover (Verify require 'CONFIRM' string).
  - [ ] Verify class sessions, enrollments, and attendance records are moved to `archived_*` tables.
  - [ ] Verify active session/attendance data is cleared.
  - [ ] Verify gamification streaks are reset to 0.
  - [ ] Verify semester name and start date are updated in system settings.
