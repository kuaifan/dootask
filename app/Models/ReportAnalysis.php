<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\ReportAnalysis
 *
 * @property int $id
 * @property int $rid 报告ID
 * @property int $userid 生成分析的会员ID
 * @property string $model 使用的模型名称
 * @property string $analysis_text AI 分析的原始文本（Markdown）
 * @property array|null $meta 额外的上下文信息
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Report|null $report
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|ReportAnalysis newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ReportAnalysis newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ReportAnalysis query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|ReportAnalysis whereAnalysisText($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportAnalysis whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportAnalysis whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportAnalysis whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportAnalysis whereModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportAnalysis whereRid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportAnalysis whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportAnalysis whereUserid($value)
 * @mixin \Eloquent
 */
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
