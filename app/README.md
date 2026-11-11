# اپ Flutter — سلف دانشگاه

بخش موبایل پروژه. برای معرفی کامل و اجرای API، [README اصلی](../README.md) را ببین.

```bash
flutter pub get
flutter run
```

ساختار کد feature-first است: هر قابلیت پوشهٔ خودش را دارد
(`Presentation` + `Controllers` + `Bindings`) و `Core/` تم، مسیرها و
ثابت‌های مشترک را نگه می‌دارد. مدیریت state با `get` انجام می‌شود.
