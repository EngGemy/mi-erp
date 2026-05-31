# نظام الحصر الأوتوماتيكي — كراون (Laravel 12 + Filament 4)

> **التصميم المعماري (ERP):** [ARCHITECTURE.md](./ARCHITECTURE.md) · **الوضع الحالي:** [CURRENT_STATE.md](./CURRENT_STATE.md)

نظام حصر وتصنيع (BOM Engine) ديناميكي يعيد إنتاج منطق ملف "كراون" بشكل قوي:
معادلات قابلة للتعديل لكل صنف، ترابط تلقائي بين الأصناف، نِسَب هالك من الإعدادات،
حصر فوري يتعاقب عند تغيير أي مدخل، واستيراد/تصدير Excel.

---

## 1) المتطلبات
- PHP 8.2+
- Composer
- قاعدة بيانات (MySQL / PostgreSQL / SQLite)
- Node.js (لبناء أصول Filament)

---

## 2) التركيب من الصفر

```bash
# إنشاء مشروع Laravel 12 جديد
composer create-project laravel/laravel crown-bom
cd crown-bom

# تثبيت Filament 4
composer require filament/filament:"^4.0"
php artisan filament:install --panels

# الحزم الأساسية للنظام
composer require symfony/expression-language:"^7.0"
composer require maatwebsite/excel:"^3.1"
```

ثم **انسخ مجلدات هذا التسليم فوق المشروع** (نفس المسارات):
- `app/Models/*`
- `app/Services/BomEngine.php`
- `app/Filament/Resources/*`
- `app/Imports/*` و `app/Exports/*`
- `database/migrations/*`
- `database/seeders/CrownProjectSeeder.php`
- `config/bom.php`
- `resources/views/filament/pages/view-bom.blade.php`

---

## 3) الإعدادات

أضف في `.env`:
```
BOM_DEFAULT_SCRAP_PERCENT=1
```

طبّق مقتطف `app/Providers/Filament/PanelConfig-snippet.txt`:
- ألوان أبيض/أسود داخل `panel()` في `AdminPanelProvider`.
- تفعيل RTL والعربية في `AppServiceProvider::boot()`.

سجّل الـ Seeder في `database/seeders/DatabaseSeeder.php`:
```php
$this->call(\Database\Seeders\CrownProjectSeeder::class);
```

---

## 4) التشغيل

```bash
php artisan migrate
php artisan db:seed --class=Database\\Seeders\\CrownProjectSeeder
php artisan make:filament-user      # إنشاء مستخدم دخول
npm install && npm run build
php artisan serve
```

افتح: `http://127.0.0.1:8000/admin`

---

## 5) كيف يعمل النظام

### المتغيرات الأساسية (لكل مشروع، بحرية)
`tiers` (الأدوار) · `lines` (الخطوط) · `cages` (العشوش) · `cage` (العش).
أضف ما تشاء من متغيرات لأي مشروع جديد.

### المعادلات
تُكتب نصياً بأسماء المتغيرات أو بالإشارة لصنف آخر:
```
((cages + 2) * 2) * lines          ← معادلة رجل قايم
item('omega_3m') * 8               ← مسمار الأوميجا = عدد الأوميجا × 8
```
- العمليات: `+ - * / ( )`
- الدوال: `ceil floor round min max abs`
- `item('code')` تُرجع **الكمية بالزيادة (gross)** للصنف المُشار إليه.
- المحرك يرتّب الأصناف تلقائياً حسب الاعتماد، ويكشف الدوائر، ولا ينهار عند خطأ.

### الزيادة / الهالك (4 أوضاع لكل صنف)
| الوضع | الوصف |
|------|------|
| `inherit` | يرث نسبة المشروع، وإلا نسبة النظام (`config/bom.php`) |
| `percent` | نسبة مخصصة (1% / 2% / 5%...) |
| `fixed` | رقم ثابت (+12 قطعة) |
| `formula` | معادلة زيادة |
| `none` | بدون زيادة |

تغيير النسبة العامة من `BOM_DEFAULT_SCRAP_PERCENT` أو من حقل المشروع → ينعكس فوراً على كل صنف بوضع `inherit`.

### الترابط التلقائي
عدّل قيمة `cages` من 118 إلى 120 → كل المعادلات المعتمدة عليها (مباشرة أو عبر `item()`) تُعاد حسابتها فوراً عند فتح صفحة "الحصر". **لا يُفلت أي صنف.**

### الأعمدة (مطابقة لإكسل)
`الصافي (D)` → `الزيادة (E)` → `بالزيادة (F)` → `نسبة % (G)` → `الإجمالي (H = F × مضاعف العنابر)`.

---

## 6) استيراد / تصدير Excel

**تصدير:** زر "تصدير Excel" في صفحة الحصر → ملف بنفس ترتيب الأعمدة.

**استيراد:** زر "استيراد Excel". الأعمدة المطلوبة (صف العناوين):
```
code | name | section | length | formula | scrap_mode | scrap_percent | scrap_fixed | rounding | sort | unit | notes
```
- المطابقة على `code` داخل المشروع (Upsert: يحدّث أو ينشئ).
- الأقسام تُنشأ تلقائياً من عمود `section`.

---

## 7) مشروع جديد (غير كراون)
1. أنشئ مشروعاً جديداً + عرّف متغيراته.
2. أضف الأقسام والأصناف ومعادلاتها (أو استورد Excel).
3. افتح "الحصر" — يُحسب فوراً بنفس الشكل.

النظام عام بالكامل: لا شيء مربوط بكراون في الكود — كراون مجرد بيانات في الـ Seeder.

---

## 8) ملاحظات تقنية
- محرك المعادلات يستخدم `symfony/expression-language` (آمن، لا `eval`).
- كل الكميات تُقرّب لأعلى افتراضياً (`rounding=up`) لأن كسر القطعة = نقص فعلي في التصنيع.
- تم التحقق من تطابق المخرجات مع قيم إكسل الأصلية (مثال: رجل قايم = 2424، مسمار أوميجا ≈ 26712).

---

## 9) متابعة النواقص (Shortage Tracking) — تصميم SAP/Oracle

### المنطق
```
المطلوب  = required_override (يدوي) أو الإجمالي المحسوب H (تلقائي)
المُسلّم = مجموع كميات الصنف عبر كل الحمولات
الناقص  = المطلوب − المُسلّم
```

### الشاشات
- **صفحة النواقص** (زر "النواقص" في قائمة المشاريع): شبكة كثيفة برأس أسود ثابت،
  بطاقات ملخص (المطلوب/المُسلّم/الناقص/%)، شريط إنجاز وحالة لكل صنف:
  مكتمل · جزئي · لم يبدأ · زائد · بلا مرجع. زر "إظهار أعمدة الحمولات" يفرد عموداً لكل حمولة (مثل إكسل).
- **الحمولات** (تبويب داخل المشروع): إضافة/تعديل الحمولات وتواريخها.
- **إدخال أصناف الحمولة** (شاشة منفصلة لكل حمولة): اختيار الأصناف وكمياتها (Repeater).
- **تصدير Excel**: يصدّر النواقص بأعمدة الحمولات + الحالة.

### التجاوز اليدوي للمطلوب
في تعديل أي صنف، حقل "تجاوز المطلوب (للنواقص)":
- فارغ → يُستخدم الإجمالي المحسوب تلقائياً.
- بقيمة → تُستخدم كمرجع للنواقص (يظهر بجانبه ✎).

### تشغيل الميجريشن الإضافي
بعد نسخ الملفات شغّل من جديد:
```bash
php artisan migrate
```
(يضيف عمود required_override + جداول الحمولات الموجودة أصلاً في الميجريشن 000005).
