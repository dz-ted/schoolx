# SchoolX Node.js + React Setup

## المتطلبات
- Node.js 18+
- MySQL يعمل بقاعدة البيانات الحالية `employees_db`

## تشغيل السيرفر
```bash
cd server
npm install
npm run dev
```

يستخدم السيرفر المتغيرات التالية (اختيارية):

- `DB_HOST`
- `DB_USER`
- `DB_PASSWORD`
- `DB_NAME`
- `SESSION_SECRET`
- `CLIENT_ORIGIN`

## تشغيل الواجهة
```bash
cd client
npm install
npm run dev
```

> الافتراضي أن الواجهة تعمل على `http://localhost:5173` والسيرفر على `http://localhost:4000`.

## ملاحظات
- يتم الاعتماد على نفس جدول المستخدمين الحالي للتحقق من الدخول.
- الصفحات المنفذة حالياً: تسجيل الدخول، لوحة المدير التنفيذي، لوحة المرحلة، قائمة المعلمين.
