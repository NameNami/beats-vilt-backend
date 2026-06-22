# BEATS Student App API Contract (Detailed)
# Base URL: /api
# Last Updated: June 22, 2026
# General Headers: 
#   Accept: application/json
#   Content-Type: application/json (for POST/PUT requests)

---

## Contents
- [1. Authentication](#1-authentication)
- [2. Attendance & Check-In](#2-attendance--check-in)
  - [POST /student/check-in-ble](#post-studentcheck-in-ble)
  - [POST /student/check-in-qr](#post-studentcheck-in-qr)
- [3. Student Data & Records](#3-student-data--records)
- [4. Hardware Heartbeat & Beacons](#4-hardware-heartbeat--beacons)

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
    "id": 5,
    "programme_id": 1,
    "name": "Muhammad Najmi",
    "username": "najmi",
    "email": "najmi@student.beats.namix.my",
    "student_id": "52101324169",
    "role": "student",
    "profile_photo_path": null,
    "is_active": true,
    "email_verified_at": null,
    "created_at": "2026-05-31T06:02:29.000000Z",
    "updated_at": "2026-05-31T06:02:29.000000Z",
    "deleted_at": null
  }
  ```
- **Error Response (401 Unauthorized)**: Token is missing or invalid.

---

## 2. Attendance & Check-In
All attendance endpoints require `Authorization: Bearer {token}` and `role:student`.

### POST /student/check-in-ble
- **Purpose**: Mark attendance using a BLE Beacon scan.
- **Headers**:
  - `Authorization: Bearer {token}`
  - `Content-Type: application/json`
  - `Accept: application/json`
- **Payload**:
  ```json
  {
    "timestamp": "1234567890",
    "class_session_id": 1,
    "uuid": "uuid-here",
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
- **Notes**:
  - Response shape is identical to `/student/check-in-qr`.
  - `timestamp` must be sent as a string numeric (e.g., "1234567890").
- **Error Responses**:
  - **403 Forbidden**: `{"status": "error", "message": "Invalid beacon scanned for this classroom"}`
  - **400 Bad Request (Weak Signal)**: `{"status": "error", "message": "Signal too weak"}`
  - **409 Conflict**: `{"status": "error", "message": "You have already checked in."}`
  - **409 Conflict**: `{"status": "error", "message": "Class is cancelled"}`
  - **400 Bad Request (Time)**: `{"status": "error", "message": "Invalid timestamp"}`

### POST /student/check-in-qr
- **Purpose**: Mark attendance using a scanned dynamic QR token.
- **Headers**:
  - `Authorization: Bearer {token}`
  - `Content-Type: application/json`
  - `Accept: application/json`
- **Payload**:
  ```json
  {
    "timestamp": "1234567890",
    "class_session_id": 1,
    "token": "qr_token_abc123"
  }
  ```
- **Success Response (200 OK)**:
  ```json
  {
    "status": "success",
    "message": "Successfully checked in.",
    "data": {
      "attendance_status": "present",
      "xp_earned": 50,
      "total_xp": 1500,
      "level": 3,
      "check_in_time": "2026-05-29 08:30:00"
    }
  }
  ```
- **Notes**:
  - Arrival status defaults to `present` for QR check-ins currently.
  - `timestamp` must be sent as a string numeric (e.g., "1234567890").
- **Error Responses**:
  - 400 Bad Request: `{"status":"error","message":"Invalid or expired QR token"}`
  - 401 Unauthorized: Missing or invalid bearer token
  - 403 Forbidden: Session not accessible to this user

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
        "id": 5,
        "programme_id": 1,
        "name": "Muhammad Najmi",
        "username": "najmi",
        "email": "najmi@student.beats.namix.my",
        "student_id": "52101324169",
        "role": "student",
        "profile_photo_path": null,
        "is_active": true,
        "email_verified_at": null,
        "created_at": "2026-05-31T06:02:29.000000Z",
        "updated_at": "2026-05-31T06:02:29.000000Z",
        "deleted_at": null,
        "gamification_profile": {
            "id": 5,
            "user_id": 5,
            "level_id": 9,
            "total_xp": 3655,
            "total_points": 3655,
            "current_streak": 3,
            "created_at": "2026-05-31T06:05:32.000000Z",
            "updated_at": "2026-05-31T06:05:32.000000Z",
            "level": {
                "id": 9,
                "level": 9,
                "xp_required": 310,
                "created_at": "2026-05-31T06:02:25.000000Z",
                "updated_at": "2026-05-31T06:02:25.000000Z"
            }
        },
        "badges": [],
        "programme": {
            "id": 1,
            "code": "DIT",
            "name": "Diploma in Information Technology",
            "created_at": "2026-05-31T06:02:24.000000Z",
            "updated_at": "2026-05-31T06:02:24.000000Z"
        }
    }
  }

  ```

### POST /student/profile
- **Purpose**: Update student's basic profile information.
- **Headers**:
  - `Authorization: Bearer {token}`
  - `Content-Type: application/json`
  - `Accept: application/json`
- **Payload**:
  ```json
  {
    "name": "Muhammad Najmi",
    "email": "najmi@student.beats.namix.my",
    "username": "najmi"
  }
  ```
- **Success Response (200 OK)**:
  ```json
  {
    "status": "success",
    "message": "Profile updated successfully.",
    "data": {}
  }
  ```
- **Error Response (422 Unprocessable Entity)**: Validation errors for duplicate email/username or invalid formats.
- **Notes**:
  - `data` returns the full user object with relations (same structure as `GET /student/profile`).

### POST /student/profile/gegephoto
- **Purpose**: Update student's profile photo.
- **Headers**:
  - `Authorization: Bearer {token}`
  - `Content-Type: multipart/form-data`
  - `Accept: application/json`
- **Payload (multipart/form-data)**:
  - `photo` (image, required, max 2MB)
- **Success Response (200 OK)**:
  ```json
  {
    "status": "success",
    "message": "Profile photo updated successfully.",
    "data": {
      "profile_photo_path": "profile-photos/abc123.jpg",
      "profile_photo_url": "https://your-domain/storage/profile-photos/abc123.jpg"
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
            "id": 4,
            "user_id": 5,
            "course_id": 1,
            "lab_id": 1,
            "role": "student",
            "created_at": "2026-05-31T06:02:50.000000Z",
            "updated_at": "2026-05-31T06:02:50.000000Z",
            "course": {
                "id": 1,
                "code": "IPD39806",
                "name": "Final Year Project",
                "faculty": "Information Technology",
                "deleted_at": null,
                "created_at": "2026-05-31T06:02:49.000000Z",
                "updated_at": "2026-05-31T06:02:49.000000Z"
            },
            "lab": {
                "id": 1,
                "course_id": 1,
                "lecturer_id": 2,
                "name": "L01",
                "capacity": 15,
                "deleted_at": null,
                "created_at": "2026-05-31T06:02:49.000000Z",
                "updated_at": "2026-05-31T06:02:49.000000Z"
            }
        },
        {
            "id": 44,
            "user_id": 5,
            "course_id": 2,
            "lab_id": 5,
            "role": "student",
            "created_at": "2026-05-31T06:02:52.000000Z",
            "updated_at": "2026-05-31T06:02:52.000000Z",
            "course": {
                "id": 2,
                "code": "ITD31403",
                "name": "Software Engineering",
                "faculty": "Information Technology",
                "deleted_at": null,
                "created_at": "2026-05-31T06:02:49.000000Z",
                "updated_at": "2026-05-31T06:02:49.000000Z"
            },
            "lab": {
                "id": 5,
                "course_id": 2,
                "lecturer_id": 3,
                "name": "L01",
                "capacity": 15,
                "deleted_at": null,
                "created_at": "2026-05-31T06:02:50.000000Z",
                "updated_at": "2026-05-31T06:02:50.000000Z"
            }
        },
        {
            "id": 84,
            "user_id": 5,
            "course_id": 3,
            "lab_id": 9,
            "role": "student",
            "created_at": "2026-05-31T06:02:54.000000Z",
            "updated_at": "2026-05-31T06:02:54.000000Z",
            "course": {
                "id": 3,
                "code": "ITD34103",
                "name": "IoT Data Analytic",
                "faculty": "Information Technology",
                "deleted_at": null,
                "created_at": "2026-05-31T06:02:49.000000Z",
                "updated_at": "2026-05-31T06:02:49.000000Z"
            },
            "lab": {
                "id": 9,
                "course_id": 3,
                "lecturer_id": 4,
                "name": "L01",
                "capacity": 15,
                "deleted_at": null,
                "created_at": "2026-05-31T06:02:50.000000Z",
                "updated_at": "2026-05-31T06:02:50.000000Z"
            }
        },
        {
            "id": 124,
            "user_id": 5,
            "course_id": 4,
            "lab_id": 13,
            "role": "student",
            "created_at": "2026-05-31T06:02:55.000000Z",
            "updated_at": "2026-05-31T06:02:55.000000Z",
            "course": {
                "id": 4,
                "code": "ITD10403",
                "name": "Statistics and Data Analytic",
                "faculty": "Information Technology",
                "deleted_at": null,
                "created_at": "2026-05-31T06:02:49.000000Z",
                "updated_at": "2026-05-31T06:02:49.000000Z"
            },
            "lab": {
                "id": 13,
                "course_id": 4,
                "lecturer_id": 2,
                "name": "L01",
                "capacity": 15,
                "deleted_at": null,
                "created_at": "2026-05-31T06:02:50.000000Z",
                "updated_at": "2026-05-31T06:02:50.000000Z"
            }
        },
        {
            "id": 164,
            "user_id": 5,
            "course_id": 5,
            "lab_id": 17,
            "role": "student",
            "created_at": "2026-05-31T06:02:57.000000Z",
            "updated_at": "2026-05-31T06:02:57.000000Z",
            "course": {
                "id": 5,
                "code": "ITD10604",
                "name": "Data Structure",
                "faculty": "Information Technology",
                "deleted_at": null,
                "created_at": "2026-05-31T06:02:49.000000Z",
                "updated_at": "2026-05-31T06:02:49.000000Z"
            },
            "lab": {
                "id": 17,
                "course_id": 5,
                "lecturer_id": 3,
                "name": "L01",
                "capacity": 15,
                "deleted_at": null,
                "created_at": "2026-05-31T06:02:50.000000Z",
                "updated_at": "2026-05-31T06:02:50.000000Z"
            }
        },
        {
            "id": 204,
            "user_id": 5,
            "course_id": 6,
            "lab_id": 21,
            "role": "student",
            "created_at": "2026-05-31T06:02:59.000000Z",
            "updated_at": "2026-05-31T06:02:59.000000Z",
            "course": {
                "id": 6,
                "code": "ITD20774",
                "name": "IT Project Management",
                "faculty": "Information Technology",
                "deleted_at": null,
                "created_at": "2026-05-31T06:02:49.000000Z",
                "updated_at": "2026-05-31T06:02:49.000000Z"
            },
            "lab": {
                "id": 21,
                "course_id": 6,
                "lecturer_id": 4,
                "name": "L01",
                "capacity": 15,
                "deleted_at": null,
                "created_at": "2026-05-31T06:02:50.000000Z",
                "updated_at": "2026-05-31T06:02:50.000000Z"
            }
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
    ]
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
            "id": 3294,
            "user_id": 5,
            "session_id": 384,
            "check_in_time": "2026-06-01T04:00:01.000000Z",
            "status": "absent",
            "checkin_method": "qr",
            "created_at": "2026-06-01T04:00:01.000000Z",
            "updated_at": "2026-06-01T04:00:01.000000Z",
            "class_session": {
                "id": 384,
                "course_id": 6,
                "lab_id": null,
                "lecturer_id": 4,
                "room_id": null,
                "start_time": "2026-06-01T02:00:00.000000Z",
                "end_time": "2026-06-01T04:00:00.000000Z",
                "mode": "online",
                "checkin_method": "qr",
                "is_display": false,
                "is_cancelled": false,
                "announce_cancelled": false,
                "is_completed": 1,
                "deleted_at": null,
                "created_at": "2026-05-31T06:03:16.000000Z",
                "updated_at": "2026-06-01T04:00:01.000000Z",
                "course": {
                    "id": 6,
                    "code": "ITD20774",
                    "name": "IT Project Management",
                    "faculty": "Information Technology",
                    "deleted_at": null,
                    "created_at": "2026-05-31T06:02:49.000000Z",
                    "updated_at": "2026-05-31T06:02:49.000000Z"
                },
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
                "room": null
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
        "id": 123,
        "title": "Attendance Warning",
        "body": "Your attendance is below 80%",
        "type": "warning",
        "is_read": false,
        "created_at": "2026-05-30 10:00:00"
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
- **Error Responses**:
  - 403 Forbidden: `{"status":"error","message":"Unauthorized"}`

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
            "id": 31,
            "user_id": 31,
            "level_id": 9,
            "total_xp": 4325,
            "total_points": 4325,
            "current_streak": 3,
            "created_at": "2026-05-31T06:05:33.000000Z",
            "updated_at": "2026-05-31T06:05:33.000000Z",
            "user": {
                "id": 31,
                "programme_id": 2,
                "name": "Anis Farhana",
                "username": "anis",
                "email": "anis@student.beats.namix.my",
                "student_id": "52101324208",
                "role": "student",
                "profile_photo_path": null,
                "is_active": true,
                "email_verified_at": null,
                "created_at": "2026-05-31T06:02:44.000000Z",
                "updated_at": "2026-05-31T06:02:44.000000Z",
                "deleted_at": null,
                "programme": {
                    "id": 2,
                    "code": "DIM",
                    "name": "Diploma in Multimedia",
                    "created_at": "2026-05-31T06:02:24.000000Z",
                    "updated_at": "2026-05-31T06:02:24.000000Z"
                }
            },
            "level": {
                "id": 9,
                "level": 9,
                "xp_required": 310,
                "created_at": "2026-05-31T06:02:25.000000Z",
                "updated_at": "2026-05-31T06:02:25.000000Z"
            }
        },
        {
            "id": 37,
            "user_id": 37,
            "level_id": 9,
            "total_xp": 4260,
            "total_points": 4260,
            "current_streak": 1,
            "created_at": "2026-05-31T06:05:33.000000Z",
            "updated_at": "2026-05-31T06:05:33.000000Z",
            "user": {
                "id": 37,
                "programme_id": 2,
                "name": "Nurul Atikah",
                "username": "atikah",
                "email": "atikah@student.beats.namix.my",
                "student_id": "52101324306",
                "role": "student",
                "profile_photo_path": null,
                "is_active": true,
                "email_verified_at": null,
                "created_at": "2026-05-31T06:02:47.000000Z",
                "updated_at": "2026-05-31T06:02:47.000000Z",
                "deleted_at": null,
                "programme": {
                    "id": 2,
                    "code": "DIM",
                    "name": "Diploma in Multimedia",
                    "created_at": "2026-05-31T06:02:24.000000Z",
                    "updated_at": "2026-05-31T06:02:24.000000Z"
                }
            },
            "level": {
                "id": 9,
                "level": 9,
                "xp_required": 310,
                "created_at": "2026-05-31T06:02:25.000000Z",
                "updated_at": "2026-05-31T06:02:25.000000Z"
            }
        },
        {
            "id": 8,
            "user_id": 8,
            "level_id": 9,
            "total_xp": 4215,
            "total_points": 4215,
            "current_streak": 3,
            "created_at": "2026-05-31T06:05:32.000000Z",
            "updated_at": "2026-05-31T06:05:32.000000Z",
            "user": {
                "id": 8,
                "programme_id": 1,
                "name": "Amirul Haziq",
                "username": "amirul",
                "email": "amirul@student.beats.namix.my",
                "student_id": "52101324101",
                "role": "student",
                "profile_photo_path": null,
                "is_active": true,
                "email_verified_at": null,
                "created_at": "2026-05-31T06:02:31.000000Z",
                "updated_at": "2026-05-31T06:02:31.000000Z",
                "deleted_at": null,
                "programme": {
                    "id": 1,
                    "code": "DIT",
                    "name": "Diploma in Information Technology",
                    "created_at": "2026-05-31T06:02:24.000000Z",
                    "updated_at": "2026-05-31T06:02:24.000000Z"
                }
            },
            "level": {
                "id": 9,
                "level": 9,
                "xp_required": 310,
                "created_at": "2026-05-31T06:02:25.000000Z",
                "updated_at": "2026-05-31T06:02:25.000000Z"
            }
        },
        {
            "id": 20,
            "user_id": 20,
            "level_id": 9,
            "total_xp": 4205,
            "total_points": 4205,
            "current_streak": 3,
            "created_at": "2026-05-31T06:05:33.000000Z",
            "updated_at": "2026-05-31T06:05:33.000000Z",
            "user": {
                "id": 20,
                "programme_id": 1,
                "name": "Syazwan Yusof",
                "username": "syazwan",
                "email": "syazwan@student.beats.namix.my",
                "student_id": "52101324113",
                "role": "student",
                "profile_photo_path": null,
                "is_active": true,
                "email_verified_at": null,
                "created_at": "2026-05-31T06:02:37.000000Z",
                "updated_at": "2026-05-31T06:02:37.000000Z",
                "deleted_at": null,
                "programme": {
                    "id": 1,
                    "code": "DIT",
                    "name": "Diploma in Information Technology",
                    "created_at": "2026-05-31T06:02:24.000000Z",
                    "updated_at": "2026-05-31T06:02:24.000000Z"
                }
            },
            "level": {
                "id": 9,
                "level": 9,
                "xp_required": 310,
                "created_at": "2026-05-31T06:02:25.000000Z",
                "updated_at": "2026-05-31T06:02:25.000000Z"
            }
        },
        {
            "id": 23,
            "user_id": 23,
            "level_id": 9,
            "total_xp": 4190,
            "total_points": 4190,
            "current_streak": 1,
            "created_at": "2026-05-31T06:05:33.000000Z",
            "updated_at": "2026-05-31T06:05:33.000000Z",
            "user": {
                "id": 23,
                "programme_id": 1,
                "name": "Siti Khadijah",
                "username": "khadijah",
                "email": "khadijah@student.beats.namix.my",
                "student_id": "52101324116",
                "role": "student",
                "profile_photo_path": null,
                "is_active": true,
                "email_verified_at": null,
                "created_at": "2026-05-31T06:02:39.000000Z",
                "updated_at": "2026-05-31T06:02:39.000000Z",
                "deleted_at": null,
                "programme": {
                    "id": 1,
                    "code": "DIT",
                    "name": "Diploma in Information Technology",
                    "created_at": "2026-05-31T06:02:24.000000Z",
                    "updated_at": "2026-05-31T06:02:24.000000Z"
                }
            },
            "level": {
                "id": 9,
                "level": 9,
                "xp_required": 310,
                "created_at": "2026-05-31T06:02:25.000000Z",
                "updated_at": "2026-05-31T06:02:25.000000Z"
            }
        },
        {
            "id": 22,
            "user_id": 22,
            "level_id": 9,
            "total_xp": 4110,
            "total_points": 4110,
            "current_streak": 2,
            "created_at": "2026-05-31T06:05:33.000000Z",
            "updated_at": "2026-05-31T06:05:33.000000Z",
            "user": {
                "id": 22,
                "programme_id": 1,
                "name": "Ahmad Fauzi",
                "username": "fauzi",
                "email": "fauzi@student.beats.namix.my",
                "student_id": "52101324115",
                "role": "student",
                "profile_photo_path": null,
                "is_active": true,
                "email_verified_at": null,
                "created_at": "2026-05-31T06:02:39.000000Z",
                "updated_at": "2026-05-31T06:02:39.000000Z",
                "deleted_at": null,
                "programme": {
                    "id": 1,
                    "code": "DIT",
                    "name": "Diploma in Information Technology",
                    "created_at": "2026-05-31T06:02:24.000000Z",
                    "updated_at": "2026-05-31T06:02:24.000000Z"
                }
            },
            "level": {
                "id": 9,
                "level": 9,
                "xp_required": 310,
                "created_at": "2026-05-31T06:02:25.000000Z",
                "updated_at": "2026-05-31T06:02:25.000000Z"
            }
        },
        {
            "id": 29,
            "user_id": 29,
            "level_id": 9,
            "total_xp": 4105,
            "total_points": 4105,
            "current_streak": 5,
            "created_at": "2026-05-31T06:05:33.000000Z",
            "updated_at": "2026-05-31T06:05:33.000000Z",
            "user": {
                "id": 29,
                "programme_id": 2,
                "name": "Balqis Sofia",
                "username": "balqis",
                "email": "balqis@student.beats.namix.my",
                "student_id": "52101324206",
                "role": "student",
                "profile_photo_path": null,
                "is_active": true,
                "email_verified_at": null,
                "created_at": "2026-05-31T06:02:42.000000Z",
                "updated_at": "2026-05-31T06:02:42.000000Z",
                "deleted_at": null,
                "programme": {
                    "id": 2,
                    "code": "DIM",
                    "name": "Diploma in Multimedia",
                    "created_at": "2026-05-31T06:02:24.000000Z",
                    "updated_at": "2026-05-31T06:02:24.000000Z"
                }
            },
            "level": {
                "id": 9,
                "level": 9,
                "xp_required": 310,
                "created_at": "2026-05-31T06:02:25.000000Z",
                "updated_at": "2026-05-31T06:02:25.000000Z"
            }
        },
        {
            "id": 16,
            "user_id": 16,
            "level_id": 9,
            "total_xp": 4040,
            "total_points": 4040,
            "current_streak": 1,
            "created_at": "2026-05-31T06:05:33.000000Z",
            "updated_at": "2026-05-31T06:05:33.000000Z",
            "user": {
                "id": 16,
                "programme_id": 1,
                "name": "Zulhilmi Azman",
                "username": "zulhilmi",
                "email": "zulhilmi@student.beats.namix.my",
                "student_id": "52101324109",
                "role": "student",
                "profile_photo_path": null,
                "is_active": true,
                "email_verified_at": null,
                "created_at": "2026-05-31T06:02:35.000000Z",
                "updated_at": "2026-05-31T06:02:35.000000Z",
                "deleted_at": null,
                "programme": {
                    "id": 1,
                    "code": "DIT",
                    "name": "Diploma in Information Technology",
                    "created_at": "2026-05-31T06:02:24.000000Z",
                    "updated_at": "2026-05-31T06:02:24.000000Z"
                }
            },
            "level": {
                "id": 9,
                "level": 9,
                "xp_required": 310,
                "created_at": "2026-05-31T06:02:25.000000Z",
                "updated_at": "2026-05-31T06:02:25.000000Z"
            }
        },
        {
            "id": 7,
            "user_id": 7,
            "level_id": 9,
            "total_xp": 4040,
            "total_points": 3990,
            "current_streak": 5,
            "created_at": "2026-05-31T06:05:32.000000Z",
            "updated_at": "2026-06-01T09:31:20.000000Z",
            "user": {
                "id": 7,
                "programme_id": 1,
                "name": "Muhammad Haikal",
                "username": "haikal",
                "email": "haikal@student.beats.namix.my",
                "student_id": "52101324316",
                "role": "student",
                "profile_photo_path": null,
                "is_active": true,
                "email_verified_at": null,
                "created_at": "2026-05-31T06:02:30.000000Z",
                "updated_at": "2026-05-31T06:02:30.000000Z",
                "deleted_at": null,
                "programme": {
                    "id": 1,
                    "code": "DIT",
                    "name": "Diploma in Information Technology",
                    "created_at": "2026-05-31T06:02:24.000000Z",
                    "updated_at": "2026-05-31T06:02:24.000000Z"
                }
            },
            "level": {
                "id": 9,
                "level": 9,
                "xp_required": 310,
                "created_at": "2026-05-31T06:02:25.000000Z",
                "updated_at": "2026-05-31T06:02:25.000000Z"
            }
        },
        {
            "id": 40,
            "user_id": 40,
            "level_id": 9,
            "total_xp": 4005,
            "total_points": 4005,
            "current_streak": 3,
            "created_at": "2026-05-31T06:05:34.000000Z",
            "updated_at": "2026-05-31T06:05:34.000000Z",
            "user": {
                "id": 40,
                "programme_id": 2,
                "name": "Megat Aris",
                "username": "megat",
                "email": "megat@student.beats.namix.my",
                "student_id": "52101324309",
                "role": "student",
                "profile_photo_path": null,
                "is_active": true,
                "email_verified_at": null,
                "created_at": "2026-05-31T06:02:48.000000Z",
                "updated_at": "2026-05-31T06:02:48.000000Z",
                "deleted_at": null,
                "programme": {
                    "id": 2,
                    "code": "DIM",
                    "name": "Diploma in Multimedia",
                    "created_at": "2026-05-31T06:02:24.000000Z",
                    "updated_at": "2026-05-31T06:02:24.000000Z"
                }
            },
            "level": {
                "id": 9,
                "level": 9,
                "xp_required": 310,
                "created_at": "2026-05-31T06:02:25.000000Z",
                "updated_at": "2026-05-31T06:02:25.000000Z"
            }
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
            "name": "Free Printing (10 pages)",
            "cost_points": 50,
            "stock": 99,
            "is_active": true,
            "created_at": "2026-05-31T06:02:26.000000Z",
            "updated_at": "2026-06-01T09:31:20.000000Z"
        },
        {
            "id": 2,
            "name": "Cafeteria Voucher (RM5)",
            "cost_points": 100,
            "stock": 50,
            "is_active": true,
            "created_at": "2026-05-31T06:02:26.000000Z",
            "updated_at": "2026-05-31T06:02:26.000000Z"
        },
        {
            "id": 3,
            "name": "Bookstore Voucher (RM10)",
            "cost_points": 200,
            "stock": 30,
            "is_active": true,
            "created_at": "2026-05-31T06:02:26.000000Z",
            "updated_at": "2026-05-31T06:02:26.000000Z"
        },
        {
            "id": 4,
            "name": "BEATS Exclusive Hoodie",
            "cost_points": 1000,
            "stock": 10,
            "is_active": true,
            "created_at": "2026-05-31T06:02:26.000000Z",
            "updated_at": "2026-05-31T06:02:26.000000Z"
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
  - `document` (file, optional): Image or PDF file
- **Success Response (201 Created)**:
  ```json
  {
    "status": "success",
    "message": "Leave application submitted successfully."
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
