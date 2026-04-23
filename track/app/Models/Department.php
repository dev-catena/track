<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Organization;
use App\Models\Dock;
use App\Models\Device;
use Carbon\Carbon;
use App\Helpers\SystemConfigHelper;
use Illuminate\Support\Collection;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'internal_id',
        'location',
        'operating_start',
        'operating_end',
        'description',
        'status',
        'organization_id',
        'parent_id',
        'created_by',
        'updated_by',
        'mqtt_department_id',
    ];


    private function formatDateTime($value)
    {
        if (!$value) return null;

        $config = SystemConfigHelper::getCurrentConfig();

        $timezone = \App\Helpers\SystemConfigHelper::getSafeTimezone($config->time_zone ?? null);
        $format   = $config->date_format ?? 'd/m/Y';

        return Carbon::parse($value)->timezone($timezone)->format($format);
    }
    public function getCreatedAtAttribute($value)
    {
        return $this->formatDateTime($value);
    }

    public function organization() {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function parent()
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Department::class, 'parent_id');
    }

    public function docks()
    {
        return $this->hasMany(Dock::class);
    }

    public function devices()
    {
        return $this->hasManyThrough(Device::class, Dock::class);
    }

    /**
     * Departamentos ativos em ordem de árvore (pré-ordem) para <select> / JSON, com rótulo indentado.
     *
     * @return Collection<int, array{id:int, name:string, depth:int}>
     */
    public static function forSelectHierarchical(int $organizationId): Collection
    {
        $all = self::query()
            ->where('organization_id', $organizationId)
            ->where('status', 'active')
            ->get(['id', 'name', 'parent_id']);
        if ($all->isEmpty()) {
            return collect();
        }

        $byParent = $all->whereNotNull('parent_id')->groupBy('parent_id');
        $out = collect();
        $visit = function ($dept, int $depth) use (&$visit, $byParent, &$out) {
            $indent = $depth > 0 ? str_repeat('  ', $depth) : '';
            $label = $indent . ($depth > 0 ? '↳ ' : '') . $dept->name;
            $out->push([
                'id' => (int) $dept->id,
                'name' => $label,
                'depth' => $depth,
            ]);
            $children = $byParent->get($dept->id, collect())->sortBy('name', SORT_NATURAL);
            foreach ($children as $child) {
                $visit($child, $depth + 1);
            }
        };

        foreach ($all->whereNull('parent_id')->sortBy('name', SORT_NATURAL) as $root) {
            $visit($root, 0);
        }

        $used = $out->pluck('id')->all();
        foreach ($all as $d) {
            if (!in_array($d->id, $used, true)) {
                $visit($d, 0);
            }
        }

        return $out;
    }
}
