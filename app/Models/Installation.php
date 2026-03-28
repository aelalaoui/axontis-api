<?php

namespace App\Models;

use App\Enums\InstallationType;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Installation extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'client_uuid',
        'contract_uuid',
        'city_id',
        'address',
        'country',
        'type',
        'scheduled_date',
        'scheduled_time',
    ];

    protected $casts = [
        'type' => InstallationType::class,
        'scheduled_date' => 'date',
        'scheduled_time' => 'datetime:H:i',
    ];

    protected $attributes = [
        'type' => 'first_installation',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_uuid', 'uuid');
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class, 'contract_uuid', 'uuid');
    }

    /**
     * Toutes les tâches rattachées à cette installation (relation polymorphique).
     */
    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'taskable', 'taskable_type', 'taskable_uuid', 'uuid');
    }


    /**
     * Centrales d'alarme via la chaîne correcte Installation → Task → InstallationDevice.
     */
    public function alarmPanelInstallationDevices(): Collection
    {
        return $this->tasks()
            ->with('installationDevices.device')
            ->get()
            ->flatMap(fn (Task $task) => $task->installationDevices)
            ->filter(fn (InstallationDevice $id) => $id->device?->category === 'alarm_panel')
            ->values();
    }

    /**
     * Événements alarme de cette installation.
     */
    public function alarmEvents()
    {
        return $this->hasMany(AlarmEvent::class, 'installation_uuid', 'uuid');
    }

    public function getCityArAttribute()
    {
        return City::find($this->city_id)?->name_ar;
    }

    public function getCityFrAttribute()
    {
        return City::find($this->city_id)?->name_fr;
    }

    public function getCityEnAttribute()
    {
        return City::find($this->city_id)?->name_en;
    }
}
