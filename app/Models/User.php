<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    /**
     * Relasi yang akan selalu dimuat secara otomatis.
     *
     * @var list<string>
     */
    protected $with = ['roles'];

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     */
    protected $fillable = [
        'username',
        'email',
        'password_hash',
        'avatar_url',
        'bio',
        'reputation_points',
        'level',
        'is_banned',
    ];

    /**
     * Atribut yang harus disembunyikan saat serialisasi (misal: JSON).
     *
     * @var list<string>
     */
    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    /**
     * Mendapatkan password untuk autentikasi.
     * Laravel defaultnya mencari kolom 'password', karena kita menggunakan 'password_hash', 
     * maka kita perlu mendefinisikan method ini.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    /**
     * Konfigurasi casting tipe data untuk atribut tertentu.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password_hash' => 'hashed',
            'is_banned' => 'boolean',
            'reputation_points' => 'integer',
            'level' => 'integer',
        ];
    }

    // --- Relationships (Relasi Antar Tabel) ---

    /**
     * Relasi ke tabel roles (banyak-ke-banyak).
     * Menghubungkan user dengan peran mereka (admin, moderator, user).
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id')->withPivot('assigned_at');
    }

    /**
     * Relasi ke tabel posts (satu-ke-banyak).
     * Seorang user bisa memiliki banyak postingan.
     */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Relasi ke tabel comments (satu-ke-banyak).
     * Seorang user bisa memberikan banyak komentar.
     */
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Relasi ke tabel votes (satu-ke-banyak).
     * Tracking vote (upvote/downvote) yang diberikan user.
     */
    public function votes()
    {
        return $this->hasMany(Vote::class);
    }

    /**
     * Relasi ke tabel likes (satu-ke-banyak).
     * Postingan yang disukai oleh user.
     */
    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    /**
     * Relasi ke tabel bookmarks (satu-ke-banyak).
     * Postingan yang disimpan oleh user.
     */
    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class);
    }

    /**
     * Relasi ke user lain yang diikuti (following).
     */
    public function following()
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id')->withPivot('created_at');
    }

    /**
     * Relasi ke user lain yang mengikuti user ini (followers).
     */
    public function followers()
    {
        return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id')->withPivot('created_at');
    }

    /**
     * Log perubahan poin reputasi user.
     */
    public function pointsLogs()
    {
        return $this->hasMany(PointsLog::class);
    }

    /**
     * Badge atau lencana yang dimiliki oleh user.
     */
    public function badges()
    {
        return $this->belongsToMany(Badge::class, 'user_badges', 'user_id', 'badge_id')->withPivot('earned_at');
    }

    /**
     * Notifikasi yang diterima oleh user.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Laporan yang dibuat oleh user ini.
     */
    public function reports()
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    /**
     * Log moderasi di mana user ini menjadi target (misal: di-ban).
     */
    public function moderationLogs()
    {
        return $this->hasMany(ModerationLog::class, 'target_user_id');
    }

    /**
     * Log moderasi yang dilakukan oleh user ini (jika user adalah moderator/admin).
     */
    public function managedModerationLogs()
    {
        return $this->hasMany(ModerationLog::class, 'moderator_id');
    }

    // --- Role Helpers (Fungsi Pembantu Peran) ---

    /**
     * Cek apakah user memiliki role tertentu.
     */
    public function hasRole(string $roleName)
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    /**
     * Cek apakah user adalah Administrator.
     */
    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    /**
     * Cek apakah user memiliki hak akses moderasi (Admin atau Moderator).
     */
    public function isModerator()
    {
        return $this->hasRole('admin') || $this->hasRole('moderator');
    }
}
