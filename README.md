# Laravel 11 Multi-Authentication Admin Dashboard

A complete Laravel 11 project with multi-authentication system featuring Admin and User roles, custom authentication (no Breeze), and a responsive admin dashboard.

## Features

-   **Custom Authentication System** (no Laravel Breeze or UI)
-   **Multi-Role Authentication** (Admin & User)
-   **Role-based Access Control** with middleware
-   **User Management CRUD** (Admin only)
-   **Responsive Design** with Tailwind CSS
-   **Toast Notifications** with Toastr.js
-   **Search & Filter** functionality
-   **Database Seeders** with demo data
-   **Form Validation** with custom requests
-   **Soft Deletes** for users

## Installation

1. **Clone and Install Dependencies**

```bash
git clone https://github.com/Bensappliances/laraveladminpanel
cd laraveladminpanel
composer install
```

2. **Environment Setup**

```bash
cp .env.example .env
php artisan key:generate
```

3. **Database Configuration**
   Update your `.env` file with database credentials:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=laravel_admin
DB_USERNAME=root
DB_PASSWORD=
```

4. **Run Migrations and Seeders**

```bash
php artisan migrate --seed
```

5. **Start Development Server**

```bash
php artisan serve
```

## Demo Credentials

-   **Admin**: admin@yopmail.com / admin@123
-   **User**: user@example.com / password

## Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── Admin/
│   │   │   ├── DashboardController.php
│   │   │   └── UserController.php
│   │   └── User/
│   │       └── DashboardController.php
│   ├── Middleware/
│   │   ├── AdminMiddleware.php
│   │   └── UserMiddleware.php
│   └── Requests/
│       ├── StoreUserRequest.php
│       └── UpdateUserRequest.php
├── Models/
│   └── User.php
database/
├── factories/
│   └── UserFactory.php
├── migrations/
│   └── create_users_table.php
└── seeders/
    ├── DatabaseSeeder.php
    └── UserSeeder.php
resources/
├── views/
│   ├── layouts/
│   │   ├── admin.blade.php
│   │   └── user.blade.php
│   ├── components/
│   │   ├── admin/
│   │   ├── user/
│   │   └── form/
│   ├── auth/
│   │   └── login.blade.php
│   ├── admin/
│   │   ├── dashboard.blade.php
│   │   └── users/
│   └── user/
│       └── dashboard.blade.php
routes/
└── web.php
```

## Key Features Explained

### Authentication System

-   Custom login system using `Auth::attempt()`
-   Role-based redirection after login
-   Session management and CSRF protection
-   Remember me functionality

### User Management

-   Full CRUD operations for users
-   Search by name/email
-   Filter by role and status
-   Prevent self-deletion
-   Soft delete implementation

### Security Features

-   Role-based middleware protection
-   Form validation with custom requests
-   CSRF protection on all forms
-   Password hashing with bcrypt
-   Prevention of role escalation

### UI/UX Features

-   Responsive design with Tailwind CSS
-   Toast notifications for user feedback
-   Interactive components with Alpine.js
-   Professional admin dashboard layout
-   User-friendly forms with validation errors

## Customization

### Adding New Roles

1. Update the enum in the migration
2. Add role check methods in User model
3. Create corresponding middleware
4. Update form validation rules

### Extending User Fields

1. Add columns to migration
2. Update User model fillable array
3. Modify form requests validation
4. Update views and forms

### Custom Styling

-   Modify Tailwind classes in Blade templates
-   Add custom CSS in layout files
-   Update component styling in `/components`

## Security Considerations

-   Always validate user input
-   Use CSRF tokens on forms
-   Implement proper authorization checks
-   Hash passwords before storage
-   Sanitize database queries
-   Use HTTPS in production

## Production Deployment

1. Set `APP_ENV=production` in `.env`
2. Set `APP_DEBUG=false`
3. Configure proper database credentials
4. Set up SSL certificate
5. Configure web server (Apache/Nginx)
6. Set up proper file permissions
7. Configure caching and queue drivers

## Installation Commands Summary

```bash
# 1. Composer update
composer update

# 2. Generate application key
php artisan key:generate

# 3. Run migrations and seeders
php artisan migrate --seed

# 4. Start development server
php artisan serve



composer require spatie/laravel-permission doctrine/dbal
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
php artisan db:seed
php artisan make:module-permission Product
