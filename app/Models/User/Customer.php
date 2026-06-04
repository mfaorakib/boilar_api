<?php

namespace App\Models\User;

use App\Notifications\SendCustomerPasswordResetEmail;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User;
use App\Models\Child\Child;
use App\Models\Order\Order;
use App\Models\User\Company;
use App\Models\Shipping\Area;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Customer extends User implements HasMedia
{
    use InteractsWithMedia, HasApiTokens, Notifiable, LogsActivity, Searchable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'avatar', 'email_verified_at',
        'status', 'mobile', 'mobile_verified_at', 'gender', 'newsletter',
        'parent_type', 'shipping_email', 'religion', 'primary_language', 
        'date_of_birth', 'profession', 'company_id', 'position',
        'area_id', 'education', 'blood_group', 'current_latitude', 'current_longitude',
        'current_location_name'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'mobile_verified_at' => 'datetime',
        'newsletter' => 'boolean'
    ];

    /**
     * @param $value
     * @return string
     */
    public function getAvatarAttribute($value): string
    {
        if ($value) {
            return $value;
        } else {
            return asset('assets/images/user-default.png');
        }
    }

    /**
     * Encrypt user password
     *
     * @param $value
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }

    /**
     * @return HasMany
     */
    public function providers(): HasMany
    {
        return $this->hasMany(CustomerSocialProvider::class);
    }

    /**
     * @return HasMany
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    /**
     * Register media collection name.
     *
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile();
    }

    /**
     * Send a password reset notification to the user.
     *
     * @param  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new SendCustomerPasswordResetEmail($token));
    }

    /**
     * @return HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(Child::class, 'parent_id');
    }

    /**
     * @return HasMany
     */
    public function order(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }
    
    /**
     * @return BelongsTo
     */
    public function company():BelongsTo
    {
        return $this->BelongsTo(Company::class, 'company_id');
    }

    /**
    * @return BelongsTo
    */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    /**
     * @return HasMany
     */
    public function device(): HasMany
    {
        return $this->hasMany(CustomerDevice::class, 'customer_id');
    }
    

}
