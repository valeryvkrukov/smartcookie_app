<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'pdf_path'])]
class Agreement extends Model
{
    // ── Relation: all signature requests for this document
    public function agreementRequests(): HasMany
    {
        return $this->hasMany(AgreementRequest::class);
    }
}
