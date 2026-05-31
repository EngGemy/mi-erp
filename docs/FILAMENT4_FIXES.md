# تصحيحات التوافق مع Filament 4

النسخة الأولى كانت بصياغة Filament 3. تم تحويل كل الملفات لصياغة Filament 4:

| التغيير | من (v3) | إلى (v4) |
|---------|---------|----------|
| نوع أيقونة التنقل | `protected static ?string $navigationIcon` | `protected static string\|\BackedEnum\|null $navigationIcon` |
| توقيع النموذج | `form(Form $form): Form` | `form(Schema $schema): Schema` |
| استدعاء النموذج | `$form->schema([...])` | `$schema->components([...])` |
| namespace النموذج | `Filament\Forms\Form` | `Filament\Schemas\Schema` |
| Section / Group | `Filament\Forms\Components\Section` | `Filament\Schemas\Components\Section` |
| Get utility | `Filament\Forms\Get` | `Filament\Schemas\Components\Utilities\Get` |
| الأكشنات | `Filament\Tables\Actions\*` | `Filament\Actions\*` |
| خاصية العرض في الصفحات | `protected static string $view` | `protected string $view` |
| العنوان | `protected static ?string $title` | `public function getTitle(): string` (للصفحات) |

ملاحظة: تأكد عند تشغيل `php artisan filament:install --panels`
أن تضغط Enter فقط عند سؤال "What is the panel's ID?" دون كتابة أي أمر.
