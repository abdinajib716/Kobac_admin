# Quick Installation Guide

## 🚀 Fast Setup (5 Minutes)

### 1. Install Dependencies
```bash
composer install
npm install && npm run build
```

### 2. Configure Environment
```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` - Set your database:
```env
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 3. Setup Database
```bash
php artisan migrate --seed
```

### 4. Create Admin User
```bash
php artisan admin:create
```

Fill in the prompts:
```
First Name: John
Last Name: Doe
Username: admin
Email: admin@example.com
Password: ********
Confirm Password: ********
```

### 5. Start Application
```bash
php artisan storage:link
php artisan queue:work &
php artisan serve
```

### 6. Login
Visit: **http://localhost:8000/admin/login**

Use the credentials you just created!

---

## 📋 Post-Install (Optional)

### Configure Email (for password reset)
1. Login to admin panel
2. Go to **Settings → Email Settings**
3. Add SMTP details
4. Save & Test

### Upload Branding
1. Go to **Settings → General Settings**
2. Upload logos and favicon
3. Set site name
4. Save

---

## ✅ That's It!

Your dashboard is ready to use!

**Default Login:** Use the credentials from `php artisan admin:create`

Need help? Check **README.md** for full documentation.
