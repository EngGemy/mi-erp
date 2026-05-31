<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Project;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * محرك الحصر (BOM Engine)
 * --------------------------------------------------------------
 * - يقرأ متغيرات المشروع (tiers, lines, cages ...)
 * - يحسب كل صنف من معادلته النصية
 * - يدعم الإشارة لأصناف أخرى عبر item('code')
 * - يرتب الأصناف تلقائياً حسب الاعتماد (Topological Sort)
 * - يكشف الدوائر (Circular References) ويمنع الانهيار
 * - يطبّق الزيادة (نسبة / رقم ثابت / معادلة) ثم التقريب
 *
 * النتيجة لكل صنف:
 *   net      => الكمية الصافية (عمود D في إكسل)
 *   scrap    => قيمة الزيادة   (عمود E)
 *   gross    => net + scrap    (عمود F)
 *   scrap_pct=> نسبة الزيادة % (عمود G)
 *   total    => gross * units_multiplier (عمود H = عنبرين)
 */
class BomEngine
{
    protected ExpressionLanguage $el;

    /** @var array<string, float> متغيرات المشروع */
    protected array $vars = [];

    /** @var array<string, array> نتائج الأصناف المحسوبة (code => result) */
    protected array $computed = [];

    /** @var array<string, Item> فهرس الأصناف بالكود */
    protected array $itemsByCode = [];

    /** @var array<string,bool> أعلام الكشف عن الدوائر */
    protected array $visiting = [];

    protected Project $project;

    public function __construct()
    {
        $this->el = new ExpressionLanguage();
        $this->registerFunctions();
    }

    /**
     * يحسب الحصر الكامل لمشروع ويعيد مصفوفة النتائج مرتبة.
     * @return array<int, array>
     */
    public function calculate(Project $project): array
    {
        $this->project = $project;
        $this->vars = [];
        $this->computed = [];
        $this->itemsByCode = [];
        $this->visiting = [];

        // 1) تحميل المتغيرات
        foreach ($project->variables as $v) {
            $this->vars[$v->key] = (float) $v->value;
        }

        // 2) فهرسة الأصناف
        $items = $project->items()->where('is_active', true)->orderBy('sort')->get();
        foreach ($items as $item) {
            $this->itemsByCode[$item->code] = $item;
        }

        // 3) حساب كل صنف (مع حل الاعتماديات تلقائياً)
        foreach ($items as $item) {
            $this->resolve($item->code);
        }

        // 4) إخراج مرتب بترتيب الأصناف الأصلي (sort)
        $out = [];
        foreach ($items as $item) {
            $out[] = $this->computed[$item->code];
        }

        return $out;
    }

    /**
     * يحسب صنفاً واحداً ويعالج اعتماده على أصناف أخرى عبر العودية.
     */
    protected function resolve(string $code): array
    {
        if (isset($this->computed[$code])) {
            return $this->computed[$code];
        }

        if (! isset($this->itemsByCode[$code])) {
            // إشارة لصنف غير موجود → صفر مع رسالة بدل الانهيار
            return $this->errorResult($code, "صنف غير موجود: {$code}");
        }

        // كشف الدوائر
        if (isset($this->visiting[$code])) {
            return $this->errorResult($code, "دائرة في المعادلات (Circular): {$code}");
        }
        $this->visiting[$code] = true;

        $item = $this->itemsByCode[$code];

        try {
            // الكمية الصافية الخام (D) - تُحسب بدون تقريب
            $net = $this->evaluate($item->formula, $item);
            if (! is_numeric($net)) {
                $net = 0.0;
            }
            $net = (float) $net;

            // الزيادة على القيمة الخام (E)
            $scrap = $this->computeScrap($item, $net);

            // بالزيادة الخام (F) - يُستخدم في الإشارات بين الأصناف (مطابق للإكسل)
            $grossRaw = $net + $scrap;

            // القيمة المعروضة فقط (مقرّبة للتصنيع) - لا تدخل في حساب أصناف أخرى
            $grossDisplay = $this->applyRounding($item, $grossRaw);

            $scrapPct = $net > 0 ? round(($scrap / $net) * 100, 4) : 0.0;
            $total = $grossDisplay * (float) $this->project->units_multiplier;

            $result = [
                'id'         => $item->id,
                'code'       => $item->code,
                'name'       => $item->name,
                'section'    => $item->section?->name,
                'length'     => $item->piece_length,
                'net'        => round($net, 4),       // D خام
                'net_raw'    => $net,                 // للإشارات item()
                'scrap'      => round($scrap, 4),     // E
                'gross_raw'  => $grossRaw,            // F خام - للإشارات itemF()
                'gross'      => $grossDisplay,        // F معروض (مقرّب)
                'scrap_pct'  => $scrapPct,            // G
                'total'      => round($total, 4),     // H = F_معروض × المضاعف
                'error'      => null,
            ];
        } catch (\Throwable $e) {
            $result = $this->errorResult($code, $e->getMessage(), $item);
        }

        unset($this->visiting[$code]);
        $this->computed[$code] = $result;

        return $result;
    }

    /**
     * تقييم معادلة نصية ضمن سياق المتغيرات + دالة item().
     */
    protected function evaluate(?string $formula, Item $item): float
    {
        if (blank($formula)) {
            return 0.0;
        }

        $values = $this->vars;
        $values['self'] = $item;            // للوصول لطول القطعة self.piece_length إن لزم

        $result = $this->el->evaluate($formula, $values);

        return is_numeric($result) ? (float) $result : 0.0;
    }

    /**
     * حساب الزيادة/الهالك حسب وضع الصنف.
     */
    protected function computeScrap(Item $item, float $net): float
    {
        $mode = $item->scrap_mode;

        // وراثة من المشروع ثم من إعدادات النظام
        if ($mode === 'inherit') {
            $pct = $this->project->default_scrap_percent
                ?? (float) config('bom.default_scrap_percent', 1);
            return $net * ($pct / 100);
        }

        return match ($mode) {
            'percent' => $net * (((float) $item->scrap_percent) / 100),
            'fixed'   => (float) $item->scrap_fixed,
            'formula' => (float) $this->evaluate($item->scrap_formula, $item),
            'none'    => 0.0,
            default   => 0.0,
        };
    }

    protected function applyRounding(Item $item, float $value): float
    {
        return match ($item->rounding) {
            'up'      => ceil($value),
            'nearest' => round($value),
            default   => $value,
        };
    }

    /**
     * تسجيل دوال الإشارة للأصناف داخل لغة التعبير:
     *   item('code')  → الكمية الصافية الخام (D) للصنف المُشار إليه
     *   itemF('code') → الكمية بالزيادة الخام (F) للصنف المُشار إليه
     * كلاهما يستخدم القيم الخام (غير المقرّبة) ليطابق منطق الإكسل تماماً،
     * فالتقريب لا يحدث إلا في القيمة المعروضة النهائية لكل صنف.
     */
    protected function registerFunctions(): void
    {
        // item('code') = الصافي الخام D  (مثل =D6 في الإكسل)
        $this->el->register(
            'item',
            fn ($code) => sprintf('item(%s)', $code),
            function ($args, $code) {
                $r = $this->resolve($code);
                return $r['net_raw'] ?? ($r['net'] ?? 0.0);
            }
        );

        // itemF('code') = بالزيادة الخام F  (مثل =F9 في الإكسل)
        $this->el->register(
            'itemF',
            fn ($code) => sprintf('itemF(%s)', $code),
            function ($args, $code) {
                $r = $this->resolve($code);
                return $r['gross_raw'] ?? ($r['gross'] ?? 0.0);
            }
        );

        // دوال مساعدة شائعة
        foreach (['ceil', 'floor', 'round', 'abs', 'min', 'max'] as $fn) {
            $this->el->register(
                $fn,
                fn (...$a) => sprintf('%s(%s)', $fn, implode(',', $a)),
                fn ($args, ...$a) => $fn(...$a)
            );
        }
    }

    protected function errorResult(string $code, string $msg, ?Item $item = null): array
    {
        return [
            'id'        => $item?->id,
            'code'      => $code,
            'name'      => $item?->name ?? $code,
            'section'   => $item?->section?->name,
            'length'    => $item?->piece_length,
            'net'       => 0.0,
            'net_raw'   => 0.0,
            'scrap'     => 0.0,
            'gross_raw' => 0.0,
            'gross'     => 0.0,
            'scrap_pct' => 0.0,
            'total'     => 0.0,
            'error'     => $msg,
        ];
    }

    /**
     * يتحقق من صحة معادلة قبل الحفظ (للتحقق في Filament).
     * يقيّمها فعلياً بقيم وهمية بدل parse، لأن parse لا يعرف الدوال وitem().
     */
    public function validateFormula(string $formula, Project $project): ?string
    {
        if (blank($formula)) {
            return null;
        }

        try {
            // سياق وهمي: كل المتغيرات = 1، وitem() يرجّع 1
            $values = [];
            foreach ($project->variables as $v) {
                $values[$v->key] = 1.0;
            }
            $values['self'] = null;

            $el = new ExpressionLanguage();
            $el->register('item', fn ($c) => sprintf('item(%s)', $c), fn ($a, $c) => 1.0);
            $el->register('itemF', fn ($c) => sprintf('itemF(%s)', $c), fn ($a, $c) => 1.0);
            foreach (['ceil', 'floor', 'round', 'abs', 'min', 'max'] as $fn) {
                $el->register(
                    $fn,
                    fn (...$x) => sprintf('%s(%s)', $fn, implode(',', $x)),
                    fn ($a, ...$x) => $fn(...$x)
                );
            }

            $el->evaluate($formula, $values);
            return null;
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }
}
