<?php

namespace BWibrew\SiteSettings\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use BWibrew\SiteSettings\Traits\ManagesSettings;
use BWibrew\SiteSettings\Interfaces\Setting as SettingInterface;

class Setting extends Model implements SettingInterface
{
    use ManagesSettings;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'value',
        'updated_by',
    ];

    /**
     * Get the scope that owns the setting.
     */
    public function scope()
    {
        return $this->belongsTo(Scope::class);
    }
}
