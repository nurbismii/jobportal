<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssessmentDocument extends Model
{
    use SoftDeletes;
    public const CATEGORIES = [
        'attendance' => 'Daftar Hadir',
        'mcu_detail' => 'Hasil MCU - Detail',
        'mcu_recap' => 'Hasil MCU - Rekap',
    ];

    protected $guarded = [];

    public static function stageStatus(AssessmentLink $link, ?int $folderId = null): array
    {
        $categories = static::where('assessment_link_id', $link->id)
            ->when($link->isDocumentOnly(), fn ($query) => $query->where('assessment_document_folder_id', $folderId))
            ->distinct()->pluck('category')->all();

        return [
            'attendance' => in_array('attendance', $categories, true),
            'mcu_detail' => in_array('mcu_detail', $categories, true),
            'mcu_recap' => in_array('mcu_recap', $categories, true),
        ];
    }

    public function folder()
    {
        return $this->belongsTo(AssessmentDocumentFolder::class, 'assessment_document_folder_id');
    }
}
