<?php

return [
    // نسبة الهالك/الزيادة الافتراضية العامة (%) — تُستخدم عند scrap_mode = inherit
    // ويمكن لكل مشروع تجاوزها عبر default_scrap_percent
    'default_scrap_percent' => env('BOM_DEFAULT_SCRAP_PERCENT', 1),

    // التقريب الافتراضي للأصناف الجديدة: up | nearest | none
    'default_rounding' => 'up',
];
