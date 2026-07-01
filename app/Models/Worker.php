<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('workers')]
#[Fillable(['phone', 'name', 'sector', 'skill_category', 'district', 'experience_years', 'age', 'is_available'])]
class Worker extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
            'experience_years' => 'integer',
            'age' => 'integer',
        ];
    }

    /**
     * Get the credit locks for this worker.
     */
    public function creditLocks(): HasMany
    {
        return $this->hasMany(CreditLock::class, 'worker_id');
    }
}
