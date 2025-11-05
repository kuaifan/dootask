<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportAnalysis extends AbstractModel
{
    protected $table = 'report_ai_analyses';

    protected $fillable = [
        'rid',
        'userid',
        'model',
        'analysis_text',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class, 'rid');
    }
}
