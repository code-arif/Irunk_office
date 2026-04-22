<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Stripe\Plan;
use Stripe\Product;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected $guard_name = ['api', 'web'];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'last_name',
        'username',
        'address',
        'email',
        'password',
        'otp',
        'otp_expires_at',
        'last_activity_at',
        'slug',
        'avatar',
        'balance',
        'otp_verified_at',
        'bio',
        'sex',
        'country',
        'age',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        // 'role',
        'is_online',
        'balance'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'otp_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


    public function getAvatarAttribute($value): string | null
    {
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }
        // Check if the request is an API request
        if (request()->is('api/*') && !empty($value)) {
            // Return the full URL for API requests
            return url($value);
        }

        // Return only the path for web requests
        return $value;
    }

    public function getIsOnlineAttribute()
    {
        return $this->last_activity_at > now()->subMinutes(5);
    }

    public function getBalanceAttribute()
    {
        $increment = $this->transactions()->where('type', 'increment')->sum('amount');
        $decrement = $this->transactions()->where('type', 'decrement')->sum('amount');
        return $increment - $decrement;
    }

    public function getRoleAttribute()
    {
        return  $this->getRoleNames()->first();
    }

    public function firebaseTokens()
    {
        return $this->hasMany(FirebaseTokens::class);
    }

    //chat model relation
    public function senders()
    {
        return $this->hasMany(Chat::class, 'sender_id');
    }

    public function receivers()
    {
        return $this->hasMany(Chat::class, 'receiver_id');
    }

    public function roomsAsUserOne()
    {
        return $this->hasMany(Room::class, 'user_one_id');
    }

    public function roomsAsUserTwo()
    {
        return $this->hasMany(Room::class, 'user_two_id');
    }

    public function allRooms()
    {
        return Room::where('user_one_id', $this->id)->orWhere('user_two_id', $this->id);
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // product like
    public function likedProducts()
    {
        return $this->belongsToMany(Product::class, 'product_likes')->withTimestamps();
    }

    // Friend requests
    public function sentRequests()
    {
        return $this->hasMany(FriendRequest::class, 'sender_id');
    }

    // Receive requests
    public function receivedRequests()
    {
        return $this->hasMany(FriendRequest::class, 'receiver_id');
    }

    // User Model
    public function friends()
    {
        // where user_id is me
        $friends1 = $this->belongsToMany(
            User::class,
            'friends',
            'user_id',
            'friend_id'
        )->withTimestamps()->withPivot('became_friends_at');

        // Where friend_id is me
        $friends2 = $this->belongsToMany(
            User::class,
            'friends',
            'friend_id',
            'user_id'
        )->withTimestamps()->withPivot('became_friends_at');

        // When do Union then the same columns specify
        return $friends1->union($friends2->getQuery());
    }

    public function friendOf()
    {
        return $this->belongsToMany(User::class, 'friends', 'friend_id', 'user_id')
            ->withTimestamps();
    }

    // Optional: Combined friends (both directions)
    public function allFriends()
    {
        return $this->friends()->orWhere(function ($query) {
            $query->whereIn('friend_id', $this->friendOf()->pluck('user_id'));
        });
    }

    // Festive Albums relation
    public function festiveAlbums()
    {
        return $this->hasMany(FestiveAlbum::class);
    }

    public function artists()
    {
        return $this->hasMany(Artist::class, 'user_id'); // adjust foreign key if needed
    }

    public function whishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    /**
     * Users blocked by this user
     */
    public function blockedUsers()
    {
        return $this->belongsToMany(User::class, 'blocked_users', 'user_id', 'blocked_user_id')
            ->withTimestamps();
    }
}
