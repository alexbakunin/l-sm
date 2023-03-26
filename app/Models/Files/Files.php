<?php

namespace App\Models\Files;

use App\Models\Directory\FileIcon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Files extends Model
{
    use HasFactory;

    protected $appends    = ['file_type', 'url', 'ext', 'icon'];
    public    $timestamps = false;
    /**
     * The table associated with the model.
     * @var string
     */
    protected $table = 'files';

    /**
     * The database connection that should be used by the model.
     * @var string
     */
    protected $connection = 'crm';


    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', 1);
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeReadyForClient(Builder $query): Builder
    {
        return $query->whereIn('view', [2, 4]);
    }

    /**
     * @param Builder $query
     * @param int $userId
     * @return Builder
     */
    public function scopeFromUser(Builder $query, int $userId): Builder
    {
        return $query->where('from', 'users.' . $userId);
    }


    /**
     * @param $value
     * @return string
     */
    public function getSizeAttribute($value): string
    {
        return \NumberHelper::filesizeFormat($value);
    }

    /**
     * @return string
     */
    public function getUrlAttribute(): string
    {
        $path = explode('.', $this->from);
        return env('CRM_URL') . "temp/upload/files/{$path[0]}/{$path[1]}/" . $this->file;
    }

    /**
     * @return string
     */
    public function getExtAttribute(): string
    {
        return substr(strrchr($this->file, "."), 1);
    }

    /**
     * @return string|null
     */
    public function getIconAttribute(): ?string
    {
        return self::getFileIcon($this->ext);
    }

    /**
     * @return array|string
     */
    public function getFileTypeAttribute(): string
    {
        if ($this->type == 10) {
            return 'done';
        }
        $from = explode('.', $this->from);
        return $from[0] === 'users' ? 'my' : 'manager';
    }

    /**
     * @param string $ext
     * @return string|null
     */
    public static function getFileIcon(string $ext): string|null
    {
        $icon = FileIcon::where('ext', $ext)->get(['icon'])->first();
        return $icon ? (env('CRM_URL') . "temp/upload/directory_files_icons/" . $icon->icon) : null;
    }

}
