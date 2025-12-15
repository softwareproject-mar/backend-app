# Project Rules  Laravel Backend

Dokumen ini berisi aturan teknis dan standar engineering untuk backend Laravel ini.  
Semua developer dan agent harus mematuhi aturan di bawah untuk menjaga konsistensi, keamanan, dan maintainability.

---

## 0. Runtime & Technology Versions

Project ini berjalan menggunakan stack berikut:

### PHP & Laravel
- PHP **8.2+** (LTS) atau **8.3+**.
- Framework: **Laravel 10.x** atau **11.x**.
- Seluruh project menggunakan **PHP dengan type hints** dan strict mode di composer.json.
- Development:
  - Laravel dev server (`php artisan serve`) dengan hot reload via file watcher.
  - Composer untuk dependency management.

### Database
- **MySQL 8.0+** atau MariaDB 10.6+.
- Menggunakan Eloquent ORM untuk query dan relationship.
- Migration untuk schema versioning.

### Dependencies Utama
- `laravel/sanctum` **v3.x**  API token authentication.
- `laravel/framework` **v10.x**  Core Laravel.
- `guzzlehttp/guzzle` **v7.x**  HTTP client untuk integrasi eksternal.
- `spatie/laravel-permission` **v5.x**  Role & permission management (opsional).

### Integrasi Eksternal
- Storage: Local filesystem (storage/app/public) atau cloud (S3/MinIO) via Laravel Filesystem.
- Queue: Database atau Redis untuk Jobs.

---

## 1. Project Structure & Module Organization

Seluruh source code berada di folder `app/`, `routes/`, `config/`, dll.

Struktur aktual project:

### Entry & Setup
- `routes/api.php`  Semua API routes (RESTful JSON).
- `app/Http/Kernel.php`  Middleware registration.
- `config/app.php`  App config, timezone, locale.
- `config/auth.php`  Sanctum config.
- `config/filesystems.php`  Storage config.

### HTTP Layer
Folder: `app/Http/`
- `Controllers/`  API Controllers (tipis, hanya routing & response).
  - `AuthController.php`  Login/logout.
  - `ResourceController.php`  CRUD untuk resource tertentu.
- `Requests/`  Form Request classes untuk validasi.
  - `StoreResourceRequest.php`, `UpdateResourceRequest.php`, dll.
- `Middleware/`  Custom middleware (auth, role check).
- `Resources/`  API Resource classes untuk shape response JSON.

### Business Logic Layer
Folder: `app/Services/` (Use Case layer)
- `ResourceService.php`  Logic untuk resource tertentu.
- `ExternalService.php`  Integrasi dengan API eksternal.

### Models & Database
Folder: `app/Models/`
- `User.php`  User model dengan Sanctum.
- `Resource.php`  Model untuk resource utama.

### Authorization
Folder: `app/Policies/`
- `ResourcePolicy.php`  Gates untuk access.

### Events & Jobs
Folder: `app/Events/` & `app/Listeners/`
- `ResourceUpdated.php`  Event untuk update.

Folder: `app/Jobs/`
- `SendNotification.php`  Queue job untuk notifikasi.

### Config & Helpers
Folder: `config/`
- `external.php`  Config untuk integrasi eksternal.

Folder: `app/Helpers/`
- `UtilityHelper.php`  Utility functions.

### Storage & Public
- `storage/app/public/`  File uploads.
- `public/storage/`  Symlink untuk access files.

---

## 2. Build, Run, and Development Commands

Gunakan `php artisan` dan `composer` secara konsisten.

Perintah yang direkomendasikan:

- **Install dependencies**:
  ```bash
  composer install
  ```

- **Development**:
  ```bash
  php artisan serve
  ```
  Menjalankan Laravel dev server di `http://localhost:8000`.

- **Migration & Seed**:
  ```bash
  php artisan migrate
  php artisan db:seed
  ```

- **Queue worker**:
  ```bash
  php artisan queue:work
  ```

- **Test**:
  ```bash
  php artisan test
  ```

- **Lint & Format**:
  ```bash
  ./vendor/bin/pint
  ```

### Environment Setup

1. Copy `.env.example`  `.env`:
   ```bash
   cp .env.example .env
   ```

2. Konfigurasi environment variables **wajib**:
   ```env
   APP_NAME="Laravel Backend"
   APP_ENV=local
   APP_KEY=base64:your-app-key
   APP_DEBUG=true
   APP_URL=http://localhost:8000

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=laravel_backend
   DB_USERNAME=root
   DB_PASSWORD=

   SANCTUM_STATEFUL_DOMAINS=localhost:3000
   ```

3. Environment variables **opsional**:
   ```env
   FILESYSTEM_DISK=public
   MAX_FILE_SIZE_MB=10
   QUEUE_CONNECTION=database
   ```

4. Generate app key:
   ```bash
   php artisan key:generate
   ```

5. Storage link:
   ```bash
   php artisan storage:link
   ```

---

## 3. Coding Style & Naming Conventions

- Bahasa utama: **PHP 8+** dengan type hints dan strict types.
- Penamaan:
  - **Classes & Interfaces**  `PascalCase`.
  - **Methods & Variables**  `camelCase`.
  - **Constants**  `UPPER_SNAKE_CASE`.
  - **Files**: PascalCase untuk classes, kebab-case untuk others.

### PHP Conventions

- **Strict types enabled**:
  ```php
  declare(strict_types=1);
  ```

- **Type hints wajib**:
  ```php
  public function method(array $data): Model
  ```

- **Use Eloquent relationships**:
  ```php
  $model->relation()->with('nested')->get();
  ```

- **Mass assignment safe**:
  ```php
  protected $fillable = ['field'];
  ```

- **Validation di Form Request**:
  ```php
  public function rules(): array
  {
      return ['field' => 'required|string'];
  }
  ```

### Import Patterns

- **Use statements** di atas class.
- Namespace konsisten.

### Struktur dan Layering

#### **1. Controller Layer**
- Tipis, call Service, return Resource.

#### **2. Service Layer**
- Business logic, orchestrate models/events.

#### **3. Model Layer**
- DB interaction, relationships, scopes.

---

## 4. API Conventions

### Base Path & Structure
- Base path: `/api/v1`.
- RESTful routes.

### Response Format
**Success**:
```json
{
  "data": { ... },
  "meta": { ... }
}
```

**Error**:
```json
{
  "error": "Message",
  "errors": { ... }
}
```

### HTTP Status Codes
- 200, 201, 400, 401, 403, 404, 422, 500.

### Authentication
- Laravel Sanctum.
- Middleware: `auth:sanctum`.

### File Upload
- Multipart, validasi MIME/size.
- Store di storage.

---

## 5. Error Handling & Logging

- Exception Handler.
- Logging via Log facade.
- Audit logs jika perlu.

---

## 6. Performance & Security

### Performance
- Eager loading.
- Indexing.
- Caching.
- Pagination.

### Security
- Mass assignment protected.
- File validation.
- Rate limiting.
- No sensitive logs.

---

## 7. Testing

- PHPUnit.
- Feature & unit tests.
- Coverage 70%+.

---

## 8. CLI & Scheduler

- Jobs & scheduler untuk tasks.

---

**Catatan Akhir**:

Fokus pada clean architecture Laravel. Jaga separation of concerns.
