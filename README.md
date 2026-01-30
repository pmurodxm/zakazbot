# ZakazBot

ZakazBot — Telegram orqali mahsulot buyurtmalarini qabul qilish uchun oddiy bot. Bu repo asosiy PHP skriptlari va JSON fayllar (users.json, products.json, admins.json) bilan ishlaydi.

## Asosiy xususiyatlar
- Mahsulotlarni ro‘yxatlash (products.json)
- Foydalanuvchilarni saqlash (users.json)
- Adminlar ro‘yxati (admins.json)
- Telegram bot orqali buyurtma qabul qilish

## Talablar
- PHP 8.0+
- Composer (agar qo‘shimcha kutubxonalar qo‘shilsa)
- HTTPS server (agar webhook ishlatilsa)

## O‘rnatish
1. Repo klonlash:

   git clone https://github.com/pmurodxm/zakazbot.git
   cd zakazbot

2. Config sozlash:
- Hozirgi loyihada `config.php` ishlatiladi. Unda TELEGRAM token va boshqa sozlamalar joylashgan bo‘lishi mumkin.
- Maxfiy ma’lumotlarni (tokenlar) GitHub'ga push qilmaslik uchun `.env` fayl yoki GitHub Secrets foydalanishni tavsiya qilamiz.

Misol `.env` (ixtiyoriy):

TELEGRAM_TOKEN=your_telegram_bot_token_here
WEBHOOK_URL=https://yourdomain.com/your-webhook-path
DB_PATH=./data/database.sqlite

3. Dependensiyalar (agar composer kerak bo‘lsa):

   composer install

## Botni ishga tushirish
- Agar skript webhook emas, long-polling orqali ishlasa, quyidagicha ishga tushirish mumkin:

   php -S 0.0.0.0:8080 -t .

- Agar webhook ishlatilsa, HTTPS endpoint sozlang va Telegramga webhook URL ni yuboring:

   curl -F "url=https://yourdomain.com/your-webhook-path" https://api.telegram.org/bot<TELEGRAM_TOKEN>/setWebhook

Eslatma: botni doimiy ishga tushirish uchun Docker, systemd yoki process manager (supervisor) dan foydalaning.

## Tavsiya etilgan yaxshilanishlar
- JSON fayllar o‘rniga SQLite yoki MySQL ga o‘tkazish (ma’lumotlar yo‘qolishini oldini olish va concurrency uchun).
- README, .gitignore, va `.env.example` qo‘shish.
- Dockerfile va docker-compose.yml yaratish (oson deploy uchun).
- Web admin panel yoki bot ichida admin komandalarini kengaytirish.
- Xavfsizlik: tokenlarni GitHub Secrets yoki server environment variables da saqlash.

## Kontribyutsiya
Agar o‘zgartirish kiritmoqchi bo‘lsangiz — fork qiling, o‘zgartirish kiriting va pull request yuboring. Iltimos, maxfiy tokenlarni push qilmang.

## Litsenziya
MIT License

---

Agar README matnida qayta ishlash yoki qo‘shimcha bo‘limlar kerak bo‘lsa, qaysi tillarda (uz/ru/en) va qanday misollar kiritishni xohlayotganingizni ayting, men yangilab qo‘yaman.