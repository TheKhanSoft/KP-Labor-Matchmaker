<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('job_posts')]
#[Fillable(['employer_id', 'title', 'trade', 'district', 'salary', 'duration', 'phone', 'description'])]
class JobPost extends Model
{
    /**
     * Get the employer who posted this job.
     */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employer_id');
    }
}
