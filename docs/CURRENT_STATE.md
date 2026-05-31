# الوضع الحالي vs الهدف المعماري

مرجع المعمارية: [ARCHITECTURE.md](./ARCHITECTURE.md)

## ما يعمل اليوم (crown-bom)

| المكوّن | الحالة | ملاحظة |
|---------|--------|--------|
| `BomEngine` | ✅ مستقر | item/itemF، تقريب، D–H — **لا يُستبدل** |
| `projects` | ✅ | + `project_variables` |
| `sections` / `items` | ✅ | **لكل مشروع** (ليست مركزية بعد) |
| `shipments` / `shipment_items` | ✅ | النواقص + الحمولة |
| `CrownTemplateData` + `CrownTemplateService` | ✅ | 10 أقسام، 78 صنف — من كود PHP |
| ViewBom / ViewShortage / تقرير الحمولات | ✅ | Livewire + Filament 4 |
| معاينة المتغيرات قبل الحفظ | ✅ | confirm / discard |
| مشروع كراون جاهز / تحميل قالب | ✅ | ListProjects + EditProject |

## المرحلة 1 — مكتملة ✅

| المكوّن | الحالة |
|---------|--------|
| `catalog_sections` + `catalog_items` | ✅ 10 أقسام، 78 صنف |
| `items.catalog_item_id` | ✅ snapshot من الكتالوج |
| `CatalogApplyService` (replace / sync) | ✅ |
| Filament: الكتالوج المركزي | ✅ `CatalogSectionResource` + `CatalogItemResource` |
| `CrownTemplateData` | مصدر أولي للـ `CatalogSeeder` فقط |
| `CrownTemplateService` | deprecated → يوجّه لـ `CatalogApplyService` |

## اختبار القبول الثابت (كل مرحلة)

```
متغيرات: tiers=4, lines=5, cages=118, cage=1, units_multiplier=2
رجل قايم (leg_post) عمود H = 2424 (±2)
مسمار تجميع اوميجا (bolt_omega) ≈ 26698 (±2) — بعد ترحيل snapshot
```

## الملفات الحرجة (لا تكسرها عند الترحيل)

- `app/Services/BomEngine.php`
- `app/Services/ShortageService.php` — يقرأ `items` + `BomEngine::calculate`
- `database/seeders/CrownProjectSeeder.php` — يُستبدل تدريجياً بـ catalog seeder

## الخطوة التالية المقترحة

**برومبت المرحلة 1 فقط:** migrations الكتالوج + seeder من `CrownTemplateData` + `catalog_item_id` على `items` + `CatalogApplyService` + Filament CRUD للكتالوج + ربط «مشروع كراون جاهز» بالكتالوج بدل المصفوفة الثابتة.
