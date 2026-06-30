<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property int         $id
 * @property string      $key
 * @property string|null $value
 *
 * @method static Builder whereIn(string $column, array $values)
 * @method static \Illuminate\Support\Collection pluck(string $value, string $key = null)
 * @method static \App\Models\Setting updateOrCreate(array $attributes, array $values = [])
 * @method static Builder where(string $column, mixed $value)
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Récupère la valeur d'une clé de configuration.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    /**
     * Enregistre ou met à jour une clé de configuration.
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
