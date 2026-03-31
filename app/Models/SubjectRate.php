<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;


#[Fillable(['student_id', 'subject', 'rate'])]
class SubjectRate extends Model
{
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
