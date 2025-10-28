<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeroGallery extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'image_path',
        'title',
        'is_active',
        'order',
    ];

    /**
     * Get the full URL for the image
     */
    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        if ($this->image_path) {
            // Environment-based URL configuration
            if (config('app.env') === 'production') {
                // Production: Files stored in API server
                return 'https://api.dpmdbogorkab.id/uploads/' . $this->image_path;
            } else {
                // Development: Use local Laravel server
                return config('app.url') . '/uploads/' . $this->image_path;
            }
        }
        return null;
    }
}
