# مستند التصميم المعماري — نظام Crown ERP
## (Blueprint للبناء على مراحل)

> هذا المستند هو **مصدر الحقيقة المعماري**. يُبنى عليه (يدويًا أو عبر Cursor) دون تغيير الأساس كل مرة.
> الهدف: تحويل نظام الحصر الحالي إلى ERP مصنعي متكامل، بناءً على بنية بيانات صحيحة من البداية.

---

## 1. المبادئ الأساسية

1. **مصدر واحد للحقيقة (Single Source of Truth):** كتالوج مركزي للأقسام والأصناف والمعادلات.
2. **المعادلات موحّدة:** نفس المعادلة لكل المشاريع؛ المشروع يختلف فقط في قيم المتغيرات.
3. **التغيير محكوم:** تعديل الكتالوج يُطبَّق على مشروع محدد أو على الكل (باختيار المستخدم).
4. **WBS من الأوزان:** نسبة الإكتمال محسوبة Bottom-up (مرحلة ← صنف ← قسم ← مشروع).
5. **عدم المساس بالمحرك:** `BomEngine` (item/itemF/التقريب) يبقى كما هو — يُغلَّف لا يُستبدل.
6. **البناء على مراحل:** كل وحدة تعمل مستقلة وتتكامل مع سابقتها.

---

## 2. بنية قاعدة البيانات (الصحيحة من الأساس)

### الطبقة المركزية (Master Data)

**`catalog_sections`** — الأقسام المركزية

| العمود | النوع | الوصف |
|--------|------|-------|
| id | bigint PK | |
| name | string | اسم القسم (هيكل رئيسي، منظومة علف...) |
| sort | int | الترتيب |
| weight_default | decimal(8,4) | الوزن الافتراضي من المشروع (للـ WBS) |

**`catalog_items`** — الأصناف المركزية (التعريف + المعادلة)

| العمود | النوع | الوصف |
|--------|------|-------|
| id | bigint PK | |
| catalog_section_id | FK | القسم |
| code | string unique | الكود اللاتيني (leg_post...) |
| name | string | بيان الصنف |
| piece_length | decimal nullable | طول القطعة |
| unit | string nullable | الوحدة |
| formula | text | المعادلة (item/itemF + المتغيرات) |
| scrap_mode | enum | inherit/percent/fixed/formula/none |
| scrap_percent | decimal nullable | |
| scrap_fixed | decimal nullable | |
| rounding | enum | up/nearest/none |
| is_active | bool | |

> ملاحظة: هذه الطبقة تستبدل ملف `CrownTemplateData.php` — تنقله من كود إلى جدول قابل للتعديل من الواجهة.

### طبقة المشروع

**`projects`** (موجود) — يُضاف:

- `progress_cached` decimal(5,2) nullable — نسبة الإكتمال المحسوبة (cache)
- `status` enum: draft / in_progress / delivered

**`project_variables`** (موجود كما هو) — قيم المتغيرات لكل مشروع.

**`project_items`** — نسخة الأصناف داخل المشروع (تُسحب من الكتالوج)

| العمود | النوع | الوصف |
|--------|------|-------|
| id | bigint PK | |
| project_id | FK | |
| catalog_item_id | FK | الأصل في الكتالوج |
| section_id | FK | قسم المشروع (نسخة) |
| code | string | منسوخ وقت السحب |
| name | string | منسوخ (قابل للتعديل لهذا المشروع) |
| formula | text | منسوخ (يمكن تجاوزه لهذا المشروع) |
| scrap_* / rounding | | منسوخة |
| required_override | decimal nullable | تجاوز المطلوب |
| weight | decimal nullable | وزن الصنف داخل قسمه (WBS) |

> **قرار معماري:** ننسخ التعريف للمشروع وقت السحب (snapshot). هذا يسمح بتعديل مشروع واحد دون التأثير على الكتالوج، ويسمح بـ"تطبيق على الكل" عبر إعادة المزامنة.

### طبقة WBS (نسبة الإكتمال)

**`stages`** — المراحل (تصنيع/توريد/تركيب)

| العمود | النوع |
|--------|------|
| id, name, sort | |
| weight | decimal — وزن المرحلة من الصنف |

**`project_item_progress`** — تقدّم كل صنف في كل مرحلة

| العمود | النوع | الوصف |
|--------|------|-------|
| project_item_id | FK | |
| stage_id | FK | |
| done_qty | decimal | الكمية المنجزة في هذه المرحلة |

> **معادلة نسبة الإكتمال (Roll-up):**
>
> - نسبة الصنف = Σ (وزن المرحلة × (done_qty / المطلوب))
> - نسبة القسم = Σ (وزن الصنف × نسبة الصنف) ÷ Σ الأوزان
> - نسبة المشروع = Σ (وزن القسم × نسبة القسم) ÷ Σ الأوزان

### طبقة المخزون والإنتاج (Inventory + Manufacturing) — ✅ مُنفَّذة

**`warehouses`** — نوعان: `raw` (خام) | `finished` (تام)

**`raw_materials`** — مواد خام (حديد، سلك...) — كود فريد + وحدة

**`stock_balances`** / **`stock_movements`** — polymorphic `stockable` على:
- `App\Models\RawMaterial` (مخزن الخام)
- `App\Models\CatalogItem` (مخزن التام)

**`item_recipes`** — وصفة BOM خام: `catalog_item_id` + `raw_material_id` + `qty_per_unit`

**`work_orders`** + **`work_order_items`** — إذن إنتاج مرتبط بمشروع، `qty_ordered` / `qty_produced`

**`material_requests`** + **`material_request_items`** — طلب صرف خام:
`pending` → `approved` → `issued` (أو `rejected`)

**`finished_receipts`** + **`finished_receipt_items`** — طلب استلام تام:
`pending` → `approved` → `received` (أو `rejected`)

**دورة العمل:**

1. إصدار إذن إنتاج → `WorkOrderService::issue()` → `MaterialRequestService::createFromWorkOrder()` → إشعار `warehouse_manager`
2. مدير المخازن: موافقة → صرف (يخصم خام + `stock_movements` out) — يمنع الصرف إن الرصيد غير كافٍ
3. تسجيل إنتاج (`qty_produced`) → `FinishedReceiptService` → إشعار مدير المخازن
4. موافقة استلام → إدخال تام للمخزن + تحديث مرحلة «تصنيع» في WBS عبر `ProgressService::rollup()`

**الخدمات:** `StockService`, `MaterialRequestService`, `FinishedReceiptService`, `WorkOrderService`, `InventoryNotifier`

**الدور `warehouse_manager`:** طلبات الصرف/الاستلام، أرصدة، حركات، لوحة `WarehouseDashboard`

**اختبار:** `php artisan crown:verify-inventory`

### طبقة المشتريات (Purchasing) — المرحلة القادمة

**`suppliers`** — الموردون (id, name, phone, notes)

**`purchase_orders`** — أوامر الشراء

| id | supplier_id | po_number | status (draft/sent/received) | created_at |

**`purchase_order_items`**

| purchase_order_id | catalog_item_id | qty_ordered | qty_received | unit_price |

---

## 3. العلاقات (ERD مختصر)

```
catalog_sections 1───* catalog_items
catalog_items    1───* project_items   (snapshot عند السحب)
projects         1───* project_items
projects         1───* project_variables
project_items    1───* project_item_progress *───1 stages
catalog_items    1───* stock_balances *───1 warehouses
catalog_items    1───* stock_movements
catalog_items    1───* purchase_order_items *───1 purchase_orders *───1 suppliers
```

---

## 4. خريطة الترحيل الآمن (لا يكسر الـ78 صنف)

**الخطوة 1 — إنشاء الطبقة المركزية:**

1. migration لإنشاء `catalog_sections` و `catalog_items`.
2. Seeder ينقل بيانات `CrownTemplateData` الحالية إلى الجدولين (نفس الأكواد والمعادلات).
3. تحقق: عدد الأصناف = 78، عدد الأقسام = 10.

**الخطوة 2 — تحويل أصناف المشاريع لـ snapshot:**

1. migration: جدول `project_items` (أو إعادة استخدام `items` الحالي بإضافة `catalog_item_id`).
2. خدمة `applyFromCatalog(Project, mode)`:
   - mode=replace: يحذف ويعيد السحب.
   - mode=sync: يحدّث المعرّفة من الكتالوج ويبقي التجاوزات المحلية.
3. تحقق: رجل قايم = 2424، مسمار أوميجا ≈ 26698 بعد الترحيل.

**الخطوة 3 — WBS:**

1. migration: stages + project_item_progress + أعمدة الوزن.
2. خدمة `ProgressService::rollup(Project)` تحسب النسب Bottom-up.
3. عرض نسبة الإكتمال في قائمة المشاريع وداخل المشروع (شريط تقدّم).

**الخطوة 4 — المخزون والإنتاج:** ✅ (انظر الطبقة أعلاه).

**الخطوة 5 — المشتريات** (قادمة).

---

## 5. التطبيق "على مشروع أو الكل"

عند تعديل صنف/قسم في الكتالوج المركزي، يظهر خيار:

- **هذا المشروع فقط:** يعدّل `project_items` للمشروع الحالي.
- **كل المشاريع:** يعدّل `catalog_items` ثم يستدعي `applyFromCatalog(mode=sync)` على كل مشروع نشط.
- **الكتالوج فقط (للمشاريع الجديدة):** يعدّل المركزي دون لمس المشاريع القائمة.

---

## 6. شاشات الواجهة (Filament)

| الشاشة | الوصف |
|--------|-------|
| الكتالوج المركزي | إدارة الأقسام والأصناف والمعادلات (CRUD) + أوزان |
| المشاريع | قائمة + نسبة إكتمال لكل مشروع (شريط تقدّم) |
| الحصر | المتغيرات الحية + جدول D–H (موجود) |
| النواقص | إضافة صنف بصنف للحمولات (موجود) |
| WBS / التقدّم | شجرة الأقسام → الأصناف → المراحل مع نسب الإكتمال |
| المخازن | الأرصدة + الحركات |
| المشتريات | الموردون + أوامر الشراء |
| لوحة المؤشرات | إجمالي المشاريع، نِسَب الإكتمال، النواقص الكلية |
| المستخدمون والأدوار | إدارة المستخدمين + Shield للأدوار والصلاحيات |
| المخزون والإنتاج | مخازن خام/تام، وصفات خام، أذونات إنتاج، طلبات صرف/استلام، إشعارات |

---

## 7. قواعد إلزامية للبناء

- صياغة Filament 4 (Schema لا Form).
- RTL + ألوان الثيم (فاتح/داكن).
- عدم المساس بـ BomEngine.
- كل migration قابل للتراجع (down).
- بعد كل مرحلة: التحقق من أن رجل قايم = 2424 (ضمان عدم كسر المحرك).
- البناء التدريجي: لا تبني كل الجداول مرة واحدة — مرحلة مرحلة مع اختبار.

---

## 8. المستخدمون والصلاحيات (Filament Shield)

**الحزمة:** `bezhansalleh/filament-shield` + `spatie/laravel-permission`

### الأدوار الجاهزة

| الدور | الاسم في النظام | الصلاحيات |
|--------|-----------------|-----------|
| مدير | `admin` | كل الصلاحيات (دور super admin في Shield) |
| مسؤول إنتاج/حصر | `production` | مشاريع (عرض/إنشاء/تعديل)، الحصر، WBS، كتالوج **عرض فقط** |
| مسؤول توريد/حمولات | `logistics` | مشاريع (عرض/تعديل للحمولات)، النواقص، تقرير الحمولات — **بدون** حصر/WBS/كتالوج |
| مشاهد | `viewer` | كل صلاحيات `View` و `ViewAny` فقط — بدون إنشاء/تعديل/حذف |

### صلاحيات الصفحات المخصصة

| الصلاحية | الصفحة |
|----------|--------|
| `View:ViewBom` | الحصر الأوتوماتيكي |
| `View:ViewShortage` | متابعة النواقص |
| `View:ViewWbs` | هيكل الإكتمال WBS |
| `View:ViewShipmentReport` | تقرير الحمولات |

تُفحص عبر `AuthorizesCrownPage` في `canAccess()` لكل صفحة.

### الموارد

- `UserResource` — إدارة المستخدمين (مدير فقط): اسم، إيميل، كلمة مرور، دور، تفعيل.
- `RoleResource` (Shield) — إدارة الأدوار والصلاحيات (مدير فقط).
- سياسات Laravel تلقائية لـ `Project`, `CatalogItem`, `CatalogSection`, `User`, `Role`.

### المستخدمون الافتراضيون (Seeder)

| البريد | الدور | كلمة المرور |
|--------|-------|-------------|
| `admin@crown-bom.test` | admin | `password` (أو `ADMIN_PASSWORD`) |
| `production@crown-bom.test` | production | `password` |
| `logistics@crown-bom.test` | logistics | `password` |
| `viewer@crown-bom.test` | viewer | `password` |
| `warehouse@crown-bom.test` | warehouse_manager | `password` |

**أوامر الإعداد:**

```bash
composer require bezhansalleh/filament-shield
php artisan shield:install admin
php artisan shield:generate --all
php artisan shield:generate --resource=UserResource --panel=admin --option=policies_and_permissions
php artisan db:seed --class=CrownRolesSeeder
php artisan crown:verify-roles
```

---

## 9. ترتيب التنفيذ الموصى به

1. **المرحلة 1 (الأساس):** الكتالوج المركزي + ترحيل الـ78 صنف + التطبيق على مشروع/الكل. ✅
2. **المرحلة 2:** WBS ونسب الإكتمال. ✅
3. **المرحلة 3:** المستخدمون والصلاحيات (Shield). ✅
4. **المرحلة 4:** المخزون ودورة التصنيع بالموافقات والإشعارات. ✅
5. **المرحلة 5:** المشتريات والموردون وأوامر الشراء. ✅
6. **المرحلة 6:** لوحة المؤشرات الموحّدة. ✅

كل مرحلة = برومبت منفصل + اختبار قبول قبل الانتقال للتالية.

---

## 10. الإعدادات العامة (المرحلة 5أ)

**جدول `settings`:** مفتاح/قيمة JSON عبر `App\Services\CrownSettings`.

| المفتاح | الوصف |
|---------|--------|
| `factory_name` | اسم المصنع |
| `logo_path` | مسار الشعار (رفع ملف) |
| `default_scrap_percent` | هالك افتراضي للمشاريع الجديدة |
| `default_units_multiplier` | مضاعف H الافتراضي |
| `currency` | العملة |
| `default_rounding` | up / nearest / none |

**الواجهة:** `ManageGeneralSettings` — مدير (`admin`) فقط.

**مراحل WBS:** تُعدَّل من نفس الشاشة (Repeater) وتُزامَن إلى جدول `stages`.

**الاستخدام:** `ListProjects` (قالب كراون)، `CreateProject`، `ProjectResource` يقرؤون `CrownSettings::projectDefaults()` — **بدون تعديل BomEngine**.

---

## 11. المشتريات (المرحلة 5ب)

### الجداول

- `suppliers` — name, phone, email, address, notes, is_active
- `purchase_orders` — supplier_id, po_number (فريد), status, created_by, order_date, notes
- `purchase_order_items` — raw_material_id, qty_ordered, qty_received, unit_price

### الحالات

`draft` → `sent` → `partially_received` → `received` | `cancelled`

### `PurchaseOrderService`

- `createFromShortages()` — مواد خام حيث الطلب المعتمد > الرصيد
- `markSent()` — إشعار `warehouse_manager` و `admin`
- `receiveItem()` — `StockService::receiveIn()` على مخزن الخام، مرجع = `PurchaseOrderItem`

### Filament

- `SupplierResource`, `PurchaseOrderResource` (Repeater أصناف)
- `ReceivePurchaseOrder` — صفحة استلام
- زر «أمر شراء من النواقص» في قائمة الأوامر
- دور `purchasing` + صلاحيات Shield للموارد

---

## 12. لوحة المؤشرات (المرحلة 6)

**الصفحة:** `CrownDashboard` (بديل الصفحة الافتراضية) + Widgets في `app/Filament/Widgets/`:

| Widget | المحتوى | الصلاحية |
|--------|---------|----------|
| ProjectsOverviewWidget | عدد المشاريع حسب الحالة + متوسط إكتمال | ViewAny:Project |
| ProjectProgressWidget | شريط لكل مشروع | ViewAny:Project |
| GlobalShortagesWidget | إجمالي النواقص + أعلى 5 | View:ViewShortage / logistics |
| LowStockWidget | أقل 5 خام | مخزون |
| MaterialRequestsPendingWidget | طلبات صرف معلّقة | مخازن |
| FinishedReceiptsPendingWidget | استلام تام معلّق | مخازن |
| PurchaseOrdersPendingWidget | أوامر للاستلام | مشتريات/مخازن |
| RecentActivityWidget | حركات + أذونات | مخزون / إنتاج |

**خدمة:** `DashboardStatsService` — تجميع الأرقام من `ShortageService` و`StockService`.

---

## 13. اختبارات القبول

```bash
php artisan crown:verify-wbs
php artisan crown:verify-inventory
php artisan crown:verify-remaining
```

`crown:verify-remaining` يتحقق من: leg_post H≈2424، انعكاس الهالك على مشروع جديد، استلام PO 500 كجم حديد، أرقام لوحة المؤشرات.

### مستخدم إضافي

| البريد | الدور |
|--------|-------|
| `purchasing@crown-bom.test` | purchasing |

---

## 14. الهوية البصرية (عرض فقط — لا منطق)

**الملفات:**

| الملف | الدور |
|--------|------|
| `public/css/crown-theme.css` | ثيم Filament: شريط فحمي، خلفية `#f4f5f7`، Primary أحمر |
| `public/images/mi_logo.svg` | شعار افتراضي (دجاجة/ترس) — يُستبدل من الإعدادات العامة |
| `resources/views/filament/components/crown-theme-global.blade.php` | متغيرات CSS + جداول + KPI + أزرار |
| `resources/views/filament/components/crown-theme-head.blade.php` | RTL + Cairo + تحميل الثيم |
| `AdminPanelProvider` | `Color::hex('#e02424')`, `brandLogo`, `brandName` |

**لوحة الألوان (CSS variables):**

- Primary: `#e02424` / داكن `#b81414`
- Sidebar: `#2b2d33` / `#3a3d44`
- خلفية: `#f4f5f7` — بطاقات `#ffffff` — حدود `#e6e8ec`
- نص: `#1f2937` / `#6b7280`
- حالات: نجاح `#15803d`، نقص `#e02424`، تنبيه `#b45309`

**جدول الحصر:** شبكة `#eef0f2`، zebra، رؤوس أقسام `#fbe9e9`، thead/tfoot فحمي، H والمتبقي بخط عريض.

**قيود:** لا تعديل على `BomEngine` أو Services — CSS/Blade فقط. الوضع الداكن عبر `.dark` على `<html>`.

---

## 15. تخصيص الهوية والمظهر (متعدد المستويات)

### المستوى 1 — المؤسسة (`admin` فقط)

**الإعدادات العامة → قسم «الهوية والمظهر»:**

| المفتاح | الوصف |
|---------|--------|
| `factory_name` | اسم النظام |
| `logo_path` | الشعار |
| `default_theme_color` | مفتاح من `CrownThemePalettes` |

**المجموعات الجاهزة:** `calm_red`, `calm_blue`, `teal`, `warm_gray`, `amber`, `calm_purple` — كل واحدة بدرجات Filament 50→950 عبر `Color::generatePalette()`.

### المستوى 2 — المستخدم

**جدول `users`:** `theme_color` (nullable), `theme_mode` (`light` \| `dark` \| `system`, افتراضي `system`).

**الصفحة:** `EditCrownProfile` (بروفايل Filament) — لون من المجموعة أو «افتراضي النظام» + وضع العرض.

### التطبيق

| المكوّن | الدور |
|---------|------|
| `CrownThemeResolver` | يحل اللون/الوضع: مستخدم → مؤسسة |
| `ApplyCrownTheme` middleware | `FilamentColor::register()` لكل طلب |
| `crown-theme-boot.blade.php` | `localStorage.theme` من تفضيل المستخدم |
| `crown-theme-variables.blade.php` | متغيرات CSS للجداول المخصصة |
| `AdminPanelProvider` | `darkMode()`, `profile()`, ألوان ديناميكية |

**تنبيهات النقص:** `--crown-danger` ثابت `#e02424` (لا يتبع اللون الأساسي).

```bash
php artisan crown:verify-theme
php artisan crown:verify-bom-delivery
```
