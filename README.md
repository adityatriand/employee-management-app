# Employee Management Application

A Laravel-based employee management system with authentication, job positions (Jabatan), and employee (Pegawai) management.

## 📋 Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Technology Stack](#technology-stack)
- [Installation & Setup](#installation--setup)
- [Running with Docker](#running-with-docker)
- [Running without Docker](#running-without-docker)
- [Framework Version](#framework-version)
- [Database Structure](#database-structure)
- [Usage](#usage)

## ✨ Features

- User authentication (Login, Register, Password Reset)
- Role-based access control (Admin level checking)
- Job Position (Jabatan) management (CRUD)
- Employee (Pegawai) management (CRUD)
- Employee photo upload
- Bootstrap 5 UI

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
- **Frontend**: Bootstrap 5, Laravel Mix
- **Database**: MySQL 8.0
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

### Tables:
- `users` - User authentication
- `jabatan` - Job positions
- `pegawai` - Employees
- `password_resets` - Password reset tokens
- `failed_jobs` - Failed queue jobs
- `personal_access_tokens` - API tokens

### Key Relationships:
- `pegawai.id_jabatan` → `jabatan.id_jabatan`

## 📝 Usage

1. **Register/Login**: Create an account or login with existing credentials
2. **Manage Job Positions**: Navigate to `/jabatan` to add, edit, or delete job positions
3. **Manage Employees**: Navigate to `/pegawai` to add, edit, or delete employees
4. **Admin Access**: Access `/admin` route (requires admin level)

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
