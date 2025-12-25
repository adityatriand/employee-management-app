# Employee Management Application

A Laravel-based multi-tenant employee management system with workspace isolation, role-based access control, file management, and asset tracking.

## 📋 Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Technology Stack](#technology-stack)
- [Installation & Setup](#installation--setup)
- [Running with Docker](#running-with-docker)
- [Running without Docker](#running-without-docker)
- [Framework Version](#framework-version)
- [Database Structure](#database-structure)
- [API Documentation](#api-documentation)
- [Usage](#usage)

## ✨ Features

### Core Features
- **Multi-tenant Workspace System**: Each organization has its own isolated workspace
- **User Authentication**: Login, Register, Password Reset with workspace-specific URLs
- **Role-Based Access Control**: Admin (level 1) and Regular User (level 0) roles
- **Job Position Management**: Full CRUD operations for positions
- **Employee Management**: Complete employee profiles with photos
- **File Management**: Upload, organize, and track employee documents
- **Asset Management**: Track company assets, assignments, and maintenance
- **Activity Logging**: Comprehensive audit trail for all operations
- **User Profile Editing**: Regular users can edit their own profile (limited fields)

### Advanced Features
- **MinIO Integration**: Object storage for files, photos, and assets
- **RESTful API**: Laravel Sanctum-based API with token authentication
- **Soft Deletes**: Recoverable deletion for all major entities
- **Export Functionality**: PDF and Excel export for employee data
- **Responsive UI**: Bootstrap 5 with modern, clean interface
- **Workspace Branding**: Custom logos and names per workspace

## 📦 Requirements

### For Docker Setup:
- Docker Desktop (or Docker Engine + Docker Compose)
- Git

### For Local Setup:
- PHP >= 8.1
- Composer
- Node.js >= 14.x and npm
- MySQL >= 8.0
- Nginx or Apache

## 🛠 Technology Stack

- **Backend**: Laravel 9.52
- **Frontend**: Bootstrap 5, Laravel Mix, Open Iconic
- **Database**: MySQL 8.0
- **Storage**: MinIO (S3-compatible object storage)
- **API Authentication**: Laravel Sanctum
- **PHP**: 8.2
- **Web Server**: Nginx

## 🚀 Installation & Setup

### Running with Docker (Recommended - Single Image)

This setup uses a **single Docker image** that includes PHP, Nginx, and MySQL all in one container, making it simpler to run and deploy.

#### Quick Setup (Automated)

Run the setup script for automated installation:

```bash
./docker-setup.sh
```

This script will:
- Create `.env` file if it doesn't exist
- Build the single Docker image
- Start the container with all services
- Install PHP and Node dependencies
- Generate application key
- Run database migrations
- Build frontend assets
- Set proper permissions

#### Manual Setup

1. **Clone the repository** (if not already done):
   ```bash
   git clone <repository-url>
   cd employee-management-app
   ```

2. **Create `.env` file**:
   ```bash
   cp .env.example .env
   ```
   
   Or create manually with these values:
   ```env
   APP_NAME=EmployeeManagement
   APP_ENV=local
   APP_KEY=
   APP_DEBUG=true
   APP_URL=http://localhost:8000

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=employee_management
   DB_USERNAME=laravel_user
   DB_PASSWORD=laravel_password
   ```

3. **Build the Docker image**:
   ```bash
   docker-compose build
   ```

4. **Start the container**:
   ```bash
   docker-compose up -d
   ```

5. **Wait for services to start** (about 30 seconds):
   ```bash
   # Check logs to see when services are ready
   docker-compose logs -f
   ```

6. **Install PHP dependencies**:
   ```bash
   docker-compose exec app composer install
   ```

7. **Generate application key**:
   ```bash
   docker-compose exec app php artisan key:generate
   ```

8. **Run database migrations**:
   ```bash
   docker-compose exec app php artisan migrate
   ```

9. **Install Node dependencies and build assets**:
   ```bash
   docker-compose exec app npm install
   docker-compose exec app npm run production
   ```

10. **Access the application**:
    - Open your browser and navigate to: `http://localhost:8000`

#### Troubleshooting Network Timeout Errors

If you encounter network timeout errors when building the image:

1. **Check your internet connection**
2. **Configure Docker proxy** (if behind corporate firewall):
   - Edit `~/.docker/config.json`:
   ```json
   {
     "proxies": {
       "default": {
         "httpProxy": "http://proxy.example.com:3128",
         "httpsProxy": "http://proxy.example.com:3128"
       }
     }
   }
   ```
3. **Use alternative Dockerfile** (uses Debian base instead of PHP official image):
   ```bash
   # Edit docker-compose.yml and change:
   # dockerfile: Dockerfile
   # to:
   # dockerfile: Dockerfile.alternative
   # Then rebuild:
   docker-compose build --no-cache
   ```
4. **Use Docker build with retry**:
   ```bash
   docker-compose build --no-cache
   ```
5. **Try building during off-peak hours** (Docker Hub can be slow)
6. **Use a different Docker registry mirror** (if available in your region)

### Running without Docker

1. **Install PHP dependencies**:
   ```bash
   composer install
   ```

2. **Install Node dependencies**:
   ```bash
   npm install
   ```

3. **Build frontend assets**:
   ```bash
   npm run production
   ```

4. **Create `.env` file**:
   ```bash
   cp .env.example .env
   ```

5. **Configure database** in `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=employee_management
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

6. **Generate application key**:
   ```bash
   php artisan key:generate
   ```

7. **Create database**:
   ```sql
   CREATE DATABASE employee_management;
   ```

8. **Run migrations**:
   ```bash
   php artisan migrate
   ```

9. **Start development server**:
   ```bash
   php artisan serve
   ```

10. **Access the application**:
    - Open your browser and navigate to: `http://localhost:8000`

## 🐳 Docker Commands

### Using Makefile (Recommended)

We've included a `Makefile` for easy command execution. Just run:

```bash
make help          # Show all available commands
make up            # Start container
make down          # Stop container
make build         # Build image
make rebuild       # Rebuild from scratch
make logs          # View logs
make install       # Install all dependencies
make setup         # Full initial setup
make migrate       # Run migrations
make shell         # Open shell in container
make artisan CMD="migrate"  # Run artisan commands
```

### Manual Docker Commands

If you prefer using docker-compose directly:

#### Start container:
```bash
docker-compose up -d
```

#### Stop container:
```bash
docker-compose down
```

#### View logs:
```bash
docker-compose logs -f
```

#### Execute commands in container:
```bash
docker-compose exec app <command>
```

#### Example: Run artisan commands:
```bash
docker-compose exec app php artisan migrate
docker-compose exec app php artisan tinker
docker-compose exec app php artisan cache:clear
```

#### Rebuild container:
```bash
docker-compose build --no-cache
docker-compose up -d
```

#### Access MySQL:
```bash
docker-compose exec app mysql -u laravel_user -p employee_management
# Password: laravel_password
```

#### Check running services:
```bash
docker-compose exec app supervisorctl status
```

#### Restart services:
```bash
docker-compose exec app supervisorctl restart all
```

## 📊 Framework Version

**Current Version**: Laravel 9.52 (Updated from 9.11)

**PHP Requirement**: PHP 8.1 or higher

**Note**: This project uses Laravel 9, which is a stable LTS version. Laravel 11 is the latest version (released in 2024), but upgrading would require significant changes. The current setup is production-ready and well-supported.

### Upgrade Path to Laravel 11 (Optional)

If you want to upgrade to Laravel 11 in the future, you'll need to:
1. Update PHP to 8.2+
2. Update `composer.json` dependencies
3. Run `composer update`
4. Follow Laravel 11 upgrade guide: https://laravel.com/docs/11.x/upgrade

## 🗄 Database Structure

### Core Tables:
- `workspaces` - Multi-tenant workspace isolation
- `users` - User authentication with workspace association
- `positions` - Job positions (scoped to workspace)
- `employees` - Employee profiles (scoped to workspace)
- `files` - File uploads and documents (scoped to workspace)
- `assets` - Company assets (scoped to workspace)
- `asset_assignments` - Asset assignment history
- `activity_logs` - Audit trail for all operations

### Key Relationships:
- `users.workspace_id` → `workspaces.id`
- `employees.workspace_id` → `workspaces.id`
- `employees.position_id` → `positions.id`
- `employees.user_id` → `users.id` (for regular user accounts)
- `files.workspace_id` → `workspaces.id`
- `files.employee_id` → `employees.id`
- `assets.workspace_id` → `workspaces.id`
- `assets.assigned_to` → `employees.id`

## 📝 Usage

### For Administrators

1. **Register Workspace**: Create a new account and set up your workspace
2. **Manage Workspace**: Edit workspace name and logo from admin dashboard
3. **Manage Positions**: Navigate to `/{workspace}/positions` to manage job positions
4. **Manage Employees**: Navigate to `/{workspace}/employees` to manage employee profiles
5. **File Management**: Upload and organize employee documents at `/{workspace}/files`
6. **Asset Management**: Track and assign company assets at `/{workspace}/assets`
7. **Activity Logs**: View audit trail at `/{workspace}/activity-logs`
8. **Export Data**: Export employee data to PDF or Excel

### For Regular Users

1. **Login**: Use workspace-specific login URL provided by administrator
2. **View Dashboard**: See your profile, assigned files, and assets
3. **Edit Profile**: Update your name, gender, birthdate, and photo
4. **View Files**: Access your assigned documents
5. **View Assets**: See assets assigned to you

### Workspace URLs

- **Login**: `/{workspace-slug}/login`
- **Dashboard**: `/{workspace-slug}/dashboard`
- **Admin Dashboard**: `/{workspace-slug}/dashboard` (admin only)

## 🔌 API Documentation

### Authentication

The API uses Laravel Sanctum for token-based authentication.

#### Login
```http
POST /api/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password",
  "workspace_slug": "my-workspace"
}
```

#### Register
```http
POST /api/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "user@example.com",
  "password": "password",
  "password_confirmation": "password",
  "workspace_name": "My Workspace"
}
```

### Protected Routes

All protected routes require the `Authorization: Bearer {token}` header.

#### Get Authenticated User
```http
GET /api/user
Authorization: Bearer {token}
```

#### Workspace-Scoped Routes

All workspace routes are prefixed with `/api/workspaces/{workspace}`:

- `GET /api/workspaces/{workspace}/employees` - List employees
- `GET /api/workspaces/{workspace}/employees/{id}` - Get employee details
- `GET /api/workspaces/{workspace}/positions` - List positions
- `GET /api/workspaces/{workspace}/files` - List files
- `GET /api/workspaces/{workspace}/assets` - List assets

**Admin-only routes** (require admin level):
- `POST /api/workspaces/{workspace}/employees` - Create employee
- `PUT /api/workspaces/{workspace}/employees/{id}` - Update employee
- `DELETE /api/workspaces/{workspace}/employees/{id}` - Delete employee
- Similar patterns for positions, files, and assets

### Role-Based Access

- **Regular Users (level 0)**: Can only read their own data (automatically filtered)
- **Admins (level 1)**: Full CRUD access to all resources in their workspace

## 🔧 Troubleshooting

### Docker Issues:

**Port already in use**:
- Change the port in `docker-compose.yml` (nginx service ports section)

**Permission errors**:
```bash
docker-compose exec app chmod -R 755 storage bootstrap/cache
```

**Database connection errors**:
- Ensure the database container is running: `docker-compose ps`
- Check database credentials in `.env` match `docker-compose.yml`

### Local Setup Issues:

**Composer memory limit**:
```bash
php -d memory_limit=-1 /usr/local/bin/composer install
```

**Storage permissions**:
```bash
chmod -R 775 storage bootstrap/cache
```

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.
