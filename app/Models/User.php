<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['programme_id', 'name', 'username', 'email', 'password', 'role', 'student_id', 'profile_photo_path', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }
    public function programme()
    {
        return $this->belongsTo(Programme::class);
    }
    public function gamificationProfile()
    {
        return $this->hasOne(GamificationProfile::class);
    }
    public function fcmTokens()
    {
        return $this->hasMany(FcmToken::class);
    }
    public function redemptions()
    {
        return $this->hasMany(Redemption::class);
    }
    public function badges()
    {
        return $this->belongsToMany(Badge::class, 'user_badges')->withTimestamps();
    }
    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }
    public function courseEnrollments()
    {
        return $this->hasMany(CourseEnrollment::class);
    }
    public function appNotifications()
    {
        return $this->hasMany(Notification::class);
    }
    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }
    public function leaderboardSnapshots()
    {
        return $this->hasMany(LeaderboardSnapshot::class);
    }
    public function reviewedLeaveApplications()
    {
        return $this->hasMany(LeaveApplication::class, 'reviewed_by');
    }
    public function leaveApplications()
    {
        return $this->hasMany(LeaveApplication::class);
    }
    public function conductedSessions()
    {
        return $this->hasMany(ClassSession::class, 'lecturer_id');
    }
    public function labs()
    {
        return $this->hasMany(Lab::class, 'lecturer_id');
    }
    public function isStudent(): bool
    {
        return $this->role === 'student';
    }
    public function isLecturer(): bool
    {
        return $this->role === 'lecturer';
    }
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
