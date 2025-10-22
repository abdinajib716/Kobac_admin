# Modern Admin Dashboard

A professional, production-ready admin dashboard built with Laravel 11, Filament 3, and modern UI/UX design.

## ✨ Features

### 🎨 **Beautiful UI**
- Modern split-screen login page
- Dark mode support
- Responsive design (mobile, tablet, desktop)
- Clean, professional interface
- Skeleton loaders on list pages

### 🔐 **Authentication & Security**
- Secure login system
- Remember me functionality
- Password reset via email (modal popup)
- Activity logging
- Role-based access control

### 👥 **User Management**
- User CRUD operations
- Avatar upload with image editor
- Role assignment
- Permission management
- Activity tracking

### 🛡️ **Access Control**
- Roles and permissions system (Spatie)
- Admin, User, and custom roles
- Granular permission control
- Activity log for all actions

### 📊 **Dashboard**
- Stats overview (Users, Roles, Permissions)
- User distribution chart
- Activity charts
- Responsive widgets

### ⚙️ **Settings Management**
- Dynamic site settings
- Email configuration (SMTP)
- Logo and branding upload
- Favicon management
- All settings editable via UI

### 📧 **Email System**
- SMTP configuration
- Password reset emails
- Custom notification system
- Queue-enabled for performance

---

## 🚀 Installation

### Requirements
- PHP 8.2+
- MySQL 8.0+
- Composer
- Node.js & NPM

### Steps

1. **Clone & Install Dependencies**
```bash
composer install
npm install && npm run build
```

2. **Environment Setup**
```bash
cp .env.example .env
php artisan key:generate
```

3. **Configure Database**
Edit `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

4. **Run Migrations**
```bash
php artisan migrate --seed
```

5. **Create Admin User**
```bash
php artisan admin:create
```

Follow the prompts:
- First Name
- Last Name
- Username
- Email
- Password

6. **Storage Link**
```bash
php artisan storage:link
```

7. **Start Queue Worker (Important!)**
```bash
php artisan queue:work
```

8. **Serve Application**
```bash
php artisan serve
```

Visit: `http://localhost:8000/admin/login`

---

## 📋 Post-Installation Setup

### 1. **Configure Email Settings**
After logging in:
1. Go to **Settings → Email Settings**
2. Configure SMTP:
   - SMTP Host (e.g., smtp.gmail.com)
   - Port (587 for TLS)
   - Username (your email)
   - Password (app password)
   - Encryption (TLS)
3. Click **Save**
4. Test email with "Send Test Email" button

### 2. **Upload Logos and Branding**
1. Go to **Settings → General Settings**
2. Upload:
   - Site Logo (Light version)
   - Site Logo (Dark version)
   - Favicon
3. Set Site Name
4. Click **Save**

### 3. **Create Additional Users**
1. Go to **Access Control → Users**
2. Click **New User**
3. Fill in details
4. Assign roles
5. Save

### 4. **Configure Roles & Permissions**
1. Go to **Access Control → Roles**
2. Create custom roles
3. Assign permissions
4. Manage access levels

---

## 👤 Admin User Best Practice

### Recommended Approach: **Interactive Command**

We use `php artisan admin:create` because:

✅ **Secure** - Password input is hidden  
✅ **Interactive** - Validates input in real-time  
✅ **Flexible** - No hardcoded credentials  
✅ **Professional** - Industry standard approach  
✅ **Error Handling** - Prevents duplicate users  

### Alternative Approaches (NOT Recommended):

❌ **Database Seeder** - Exposes credentials in code  
❌ **.env Variables** - Security risk  
❌ **Default Credentials** - Major security vulnerability  
❌ **Migration with User** - Hard to change  

### Production Deployment:

After deploying, run:
```bash
php artisan admin:create
```

Then secure it:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 🔧 Configuration

### Queue Configuration (Important!)

For emails and notifications to work:

**Development:**
```bash
php artisan queue:work
```

**Production (with Supervisor):**
```ini
[program:dashboard-worker]
command=php /path/to/artisan queue:work --tries=3
directory=/path/to/project
user=www-data
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/path/to/project/storage/logs/worker.log
```

### Cron Jobs

Add to crontab for scheduled tasks:
```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

---

## 📁 Project Structure

```
app/
├── Console/Commands/
│   └── CreateAdminUser.php  # Admin creation command
├── Filament/
│   ├── Pages/
│   │   ├── Auth/Login.php   # Custom login
│   │   └── Settings.php     # Settings page
│   ├── Resources/           # CRUD resources
│   └── Widgets/             # Dashboard widgets
├── Models/
│   ├── User.php            # User model
│   ├── Customer.php        # Customer model
│   └── Setting.php         # Settings model
├── Notifications/
│   └── Auth/
│       └── ResetPasswordNotification.php
└── Services/
    └── NotificationService.php

resources/
├── views/
│   ├── components/skeleton/ # Loading skeletons
│   └── filament/
│       └── pages/auth/
│           └── login.blade.php  # Custom login UI

database/
├── migrations/             # All migrations
└── seeders/
    └── DatabaseSeeder.php  # Basic roles/permissions
```

---

## 🎯 Key Features Explained

### 1. **Settings System**
- All settings stored in database
- Editable via UI
- Automatically syncs to `.env` file
- File uploads (logos, favicon)
- Email configuration

### 2. **Activity Log**
- Tracks all user actions
- 90-day retention
- Automatic cleanup
- Visible in Access Control

### 3. **Skeleton Loaders**
- Only on list pages
- 300ms delay
- Improves perceived performance
- Alpine.js powered

### 4. **Password Reset**
- Modal popup (no page redirect)
- Email with reset link
- 60-minute expiration
- Secure token system

### 5. **Avatar System**
- Image upload
- Built-in editor (crop, resize)
- Max 2MB
- Displayed in header and tables

---

## 🔒 Security Features

✅ CSRF Protection  
✅ XSS Prevention  
✅ SQL Injection Protection  
✅ Password Hashing (bcrypt)  
✅ Rate Limiting  
✅ Session Security  
✅ Role-Based Access  
✅ Activity Logging  

---

## 📊 Tech Stack

- **Backend:** Laravel 11
- **Admin Panel:** Filament 3
- **Database:** MySQL
- **Auth:** Laravel Breeze + Spatie Permissions
- **Styling:** TailwindCSS
- **Icons:** Heroicons
- **Charts:** Chart.js
- **Queue:** Redis/Database

---

## 🚀 Deployment

### Production Checklist

1. ✅ Set `APP_ENV=production`
2. ✅ Set `APP_DEBUG=false`
3. ✅ Generate app key
4. ✅ Cache config/routes/views
5. ✅ Set up queue worker
6. ✅ Configure cron jobs
7. ✅ Set file permissions
8. ✅ Enable HTTPS
9. ✅ Create admin user
10. ✅ Configure email settings

### Optimization Commands

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

---

## 📞 Support & Documentation

### Commands Quick Reference

```bash
# Create admin user
php artisan admin:create

# Clear caches
php artisan optimize:clear

# Run migrations
php artisan migrate

# Run queue worker
php artisan queue:work

# Create storage link
php artisan storage:link
```

---

## 📝 License

This project is open-sourced software.

---

## 🎨 Customization

### Change Primary Color
Edit `app/Providers/Filament/AdminPanelProvider.php`:
```php
->colors([
    'primary' => Color::Blue,  // Change to any color
])
```

### Add New Widgets
```bash
php artisan make:filament-widget YourWidget
```

### Create New Resources
```bash
php artisan make:filament-resource YourModel
```

---

## ✅ What's Included

- ✅ Complete authentication system
- ✅ User management with avatars
- ✅ Role & permission system
- ✅ Activity logging
- ✅ Settings management
- ✅ Email configuration
- ✅ Password reset
- ✅ Dashboard with charts
- ✅ Modern UI/UX
- ✅ Dark mode
- ✅ Responsive design
- ✅ Production-ready

---

**Built with ❤️ using Laravel & Filament**
