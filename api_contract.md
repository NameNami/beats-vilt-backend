# BEATS Student App API Contract (Detailed)
# Base URL: /api
# Last Updated: May 31, 2026
# General Headers: 
#   Accept: application/json
#   Content-Type: application/json (for POST/PUT requests)

---

## 1. Authentication

### POST /student/login
- **Purpose**: Authenticates student and creates a Sanctum session token.
- **Headers**: None required.
- **Payload**:
  ```json
  {
    "student_id": "52101324169",
    "password": "secretpassword",
    "device": {
        "name": "iPhone 15 Pro"
    }
  }
  ```
- **Success Response (200 OK)**:
  ```json
  {
    "token": "1|abcdef...",
    "user": {
      "id": 1,
      "name": "Muhammad Najmi",
      "username": "najmi",
      "email": "najmi@student.beats.namix.my",
      "student_id": "52101324169",
      "role": "student",
      "programme_id": 1,
      "profile_photo_path": null,
      "is_active": 1,
      "created_at": "2026-05-27T17:01:02.000000Z",
      "updated_at": "2026-05-27T17:01:02.000000Z"
    }
  }
  ```
- **Error Response (401 Unauthorized)**: 
  ```json
  {"message": "Invalid credentials"}
  ```
- **Error Response (422 Unprocessable Entity)**: Validation failed (e.g. missing fields).

### POST /student/logout
- **Purpose**: Revokes the current access token.
- **Headers**: `Authorization: Bearer {token}`
- **Payload**: None
- **Success Response (200 OK)**: 
  ```json
  {"message": "Logged out"}
  ```

### POST /student/forgot-password
- **Purpose**: Sends a password reset link to the student's email.
- **Headers**: None required.
- **Payload**:
  ```json
  {
    "email": "najmi@student.beats.namix.my"
  }
  ```
- **Success Response (200 OK)**: 
  ```json
  {"message": "We have emailed your password reset link."}
  ```
- **Error Response (422 Unprocessable Entity)**: 
  ```json
  {"message": "We can't find a user with that email address."}
  ```

### GET /user
- **Purpose**: Get current authenticated user basic details.
- **Headers**: `Authorization: Bearer {token}`
- **Success Response (200 OK)**:
  ```json
  {
    "id": 1,
    "name": "Muhammad Najmi",
    "email": "najmi@student.beats.namix.my",
    ...
  }
  ```
- **Error Response (401 Unauthorized)**: Token is missing or invalid.

---

## 2. Attendance & Check-In
All attendance endpoints require `Authorization: Bearer {token}` and `role:student`.

### POST /student/check-in-ble
- **Purpose**: Mark attendance using a BLE Beacon scan.
- **Headers**: `Authorization: Bearer {token}`
- **Payload**:
  ```json
  {
    "timestamp": "1717140000",
    "class_session_id": 12,
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "rssi": -65
  }
  ```
- **Success Response (200 OK)**:
  ```json
  {
    "status": "success",
    "message": "Successfully checked in.",
    "data": {
      "attendance_status": "on-time",
      "xp_earned": 50,
      "total_xp": 1500,
      "level": 3,
      "check_in_time": "2026-05-29 08:30:00"
    }
  }
  ```
- **Error Responses**:
  - **403 Forbidden**: `{"status": "error", "message": "Invalid beacon scanned for this classroom"}`
  - **400 Bad Request (Weak Signal)**: `{"status": "error", "message": "Signal too weak"}`
  - **409 Conflict**: `{"status": "error", "message": "You have already checked in."}`
  - **409 Conflict**: `{"status": "error", "message": "Class is cancelled"}`
  - **400 Bad Request (Time)**: `{"status": "error", "message": "Invalid timestamp"}`

### POST /student/check-in-qr
- **Purpose**: Mark attendance using a scanned dynamic QR token.
- **Headers**: `Authorization: Bearer {token}`
- **Payload**:
  ```json
  {
    "timestamp": "1717140000",
    "class_session_id": 12,
    "token": "qr_token_abc123"
  }
  ```
- **Success Response (200 OK)**: Returns the same structure as BLE check-in.
- **Note**: Arrival status defaults to `present` for QR check-ins currently.
- **Error Response (400 Bad Request)**: `{"status": "error", "message": "Invalid or expired QR token"}`

---

## 3. Student Data & Records
All endpoints require `Authorization: Bearer {token}` and `role:student`.

### GET /student/profile
- **Purpose**: Fetch complete user details including gamification stats.
- **Headers**: `Authorization: Bearer {token}`
- **Success Response (200 OK)**:
  ```json
  {
    "status": "success",
    "data": {
      "id": 1,
      "name": "Muhammad Najmi",
      "gamification_profile": {
        "total_xp": 1000,
        "total_points": 200,
        "current_streak": 5,
        "level": { "level": 2, "xp_required": 500 }
      },
      "badges": [
        { "id": 1, "name": "Perfect Attendance", "icon_path": "badges/perfect.png" }
      ],
      "programme": { "id": 1, "code": "DIT", "name": "Diploma in IT" }
    }
  }
  ```

### GET /student/courses
- **Purpose**: Retrieve a list of courses the student is enrolled in.
- **Headers**: `Authorization: Bearer {token}`
- **Success Response (200 OK)**:
  ```json
  {
    "status": "success",
    "data": [
      {
        "id": 1,
        "code": "SWE3012",
        "name": "Software Engineering"
      }
    ]
  }
  ```

### GET /student/schedule
- **Purpose**: Fetch upcoming and past class sessions for the student.
- **Headers**: `Authorization: Bearer {token}`
- **Success Response (200 OK)**:
  ```json
  {
    "status": "success",
    "data": [
        {
            "id": 1,
            "course_id": 1,
            "lab_id": null,
            "lecturer_id": 2,
            "room_id": null,
            "start_time": "2026-04-13T00:00:00.000000Z",
            "end_time": "2026-04-13T02:00:00.000000Z",
            "mode": "online",
            "checkin_method": "qr",
            "is_display": false,
            "is_cancelled": false,
            "announce_cancelled": false,
            "is_completed": 1,
            "deleted_at": null,
            "created_at": "2026-05-31T06:03:00.000000Z",
            "updated_at": "2026-05-31T06:03:00.000000Z",
            "course": {
                "id": 1,
                "code": "IPD39806",
                "name": "Final Year Project",
                "faculty": "Information Technology",
                "deleted_at": null,
                "created_at": "2026-05-31T06:02:49.000000Z",
                "updated_at": "2026-05-31T06:02:49.000000Z"
            },
            "room": null
        },
        {
            "id": 2,
            "course_id": 3,
            "lab_id": null,
            "lecturer_id": 4,
            "room_id": 1,
            "start_time": "2026-04-13T00:00:00.000000Z",
            "end_time": "2026-04-13T02:00:00.000000Z",
            "mode": "physical",
            "checkin_method": "qr",
            "is_display": false,
            "is_cancelled": false,
            "announce_cancelled": false,
            "is_completed": 1,
            "deleted_at": null,
            "created_at": "2026-05-31T06:03:00.000000Z",
            "updated_at": "2026-05-31T06:03:00.000000Z",
            "course": {
                "id": 3,
                "code": "ITD34103",
                "name": "IoT Data Analytic",
                "faculty": "Information Technology",
                "deleted_at": null,
                "created_at": "2026-05-31T06:02:49.000000Z",
                "updated_at": "2026-05-31T06:02:49.000000Z"
            },
            "lab": null,
            "lecturer": {
                "id": 4,
                "programme_id": null,
                "name": "Ms. Suraya Kamaruddin",
                "username": "suraya",
                "email": "suraya@beats.namix.my",
                "student_id": null,
                "role": "lecturer",
                "profile_photo_path": null,
                "is_active": true,
                "email_verified_at": null,
                "created_at": "2026-05-31T06:02:29.000000Z",
                "updated_at": "2026-05-31T06:02:29.000000Z",
                "deleted_at": null
            },
            "room": {
                "id": 1,
                "name": "Lab 1",
                "capacity": 30,
                "location": "Block A, Level 1",
                "created_at": "2026-05-31T06:02:26.000000Z",
                "updated_at": "2026-05-31T06:02:26.000000Z"
            }
        }
    }
  ```

### GET /student/attendance
- **Purpose**: Fetch personal attendance history.
- **Headers**: `Authorization: Bearer {token}`
- **Success Response (200 OK)**:
  ```json
  {
    "status": "success",
    "data": [
      {
        "id": 145,
        "session_id": 12,
        "status": "on-time",
        "check_in_time": "2026-05-25 07:55:00",
        "session": {
            "course": { "code": "SWE3012" }
        }
      }
    ]
  }
  ```

### GET /student/notifications
- **Purpose**: Retrieve student notifications.
- **Headers**: `Authorization: Bearer {token}`
- **Success Response (200 OK)**:
  ```json
  {
    "status": "success",
    "data": [
      {
        "id": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d",
        "type": "App\\Notifications\\WarningNotification",
        "data": { "message": "Your attendance is below 80%" },
        "read_at": null,
        "created_at": "2026-05-30T10:00:00.000000Z"
      }
    ]
  }
  ```

### POST /student/notifications/{notification}/read
- **Purpose**: Mark a specific notification as read.
- **Headers**: `Authorization: Bearer {token}`
- **Payload**: None
- **Success Response (200 OK)**: 
  ```json
  {"status": "success", "message": "Notification marked as read"}
  ```

### POST /student/notifications/mark-all-read
- **Purpose**: Mark all unread notifications as read.
- **Headers**: `Authorization: Bearer {token}`
- **Payload**: None
- **Success Response (200 OK)**: 
  ```json
  {"status": "success", "message": "All notifications marked as read"}
  ```

### GET /student/leaderboard
- **Purpose**: Fetch top students by XP.
- **Headers**: `Authorization: Bearer {token}`
- **Success Response (200 OK)**:
  ```json
  {
    "status": "success",
    "data": [
      {
        "rank": 1,
        "user": { "name": "Ahmad", "programme": { "code": "DIT" } },
        "total_xp": 5000,
        "level": { "level": 10 }
      }
    ]
  }
  ```

### GET /student/rewards
- **Purpose**: Fetch list of active rewards available for redemption.
- **Headers**: `Authorization: Bearer {token}`
- **Success Response (200 OK)**:
  ```json
  {
    "status": "success",
    "data": [
      {
        "id": 1,
        "name": "Cafeteria Voucher RM5",
        "cost_points": 500,
        "stock": 20
      }
    ]
  }
  ```

### POST /student/rewards/redeem
- **Purpose**: Redeem a reward using points.
- **Headers**: `Authorization: Bearer {token}`
- **Payload**:
  ```json
  {
    "reward_id": 1
  }
  ```
- **Success Response (200 OK)**:
  ```json
  {
    "status": "success",
    "message": "Reward successfully redeemed."
  }
  ```
- **Error Response (400 Bad Request)**: Insufficient points or Out of Stock.

### GET /student/redemptions
- **Purpose**: View history of redeemed rewards.
- **Headers**: `Authorization: Bearer {token}`
- **Success Response (200 OK)**:
  ```json
  {
    "status": "success",
    "data": [
      {
        "id": 1,
        "reward": { "name": "Cafeteria Voucher RM5" },
        "status": "pending",
        "created_at": "2026-05-28T14:00:00.000000Z"
      }
    ]
  }
  ```

### GET /student/leaves
- **Purpose**: Get history of submitted leave applications.
- **Headers**: `Authorization: Bearer {token}`
- **Success Response (200 OK)**:
  ```json
  {
    "status": "success",
    "data": [
      {
        "id": 5,
        "class_session": { "course": { "name": "Software Engineering" } },
        "type": "medical",
        "status": "approved",
        "created_at": "2026-05-20T09:00:00.000000Z"
      }
    ]
  }
  ```

### POST /student/leaves
- **Purpose**: Submit a new leave application.
- **Headers**: 
  - `Authorization: Bearer {token}`
  - `Content-Type: multipart/form-data`
- **Payload**:
  - `session_id` (integer, required)
  - `type` (string, required): e.g., 'medical', 'emergency', 'other'
  - `reason` (string, required)
  - `document` (file, required): Image or PDF file
- **Success Response (201 Created)**:
  ```json
  {
    "status": "success",
    "message": "Leave application submitted."
  }
  ```

---

## 4. Hardware Heartbeat & Beacons

### POST /beacon/heartbeat
- **Purpose**: Hardware endpoint for physical beacons to ping the server.
- **Headers**: None required.
- **Payload**:
  ```json
  {
    "mac_address": "00:1B:44:11:3A:B7"
  }
  ```
- **Success Response (200 OK)**:
  ```json
  {
    "uuid": "550e8400-e29b-41d4-a716-446655440000"
  }
  ```

### GET /student/rooms/{roomId}/beacons
- **Purpose**: Retrieves a list of active beacons configured for a specific classroom. This confirms the app can retrieve multiple UUIDs for a room with overlapping beacon coverage.
- **Headers**: `Authorization: Bearer {token}`
- **URL Parameters**: `roomId` (integer, required)
- **Success Response (200 OK)**:
  ```json
  {
    "status": "success",
    "data": [
      {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440001",
        "mac_address": "00:1B:44:11:3A:B7",
        "rssi_threshold": -70
      },
      {
        "id": 2,
        "uuid": "550e8400-e29b-41d4-a716-446655440002",
        "mac_address": "00:1B:44:11:3A:B8",
        "rssi_threshold": -65
      }
    ]
  }
  ```
- **Error Response (404 Not Found)**:
  ```json
  {
    "status": "error",
    "message": "No active beacons found for this room"
  }
  ```
