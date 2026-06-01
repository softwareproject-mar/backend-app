# Plan: Flowchart Arsitektur Backend App, Web Admin, dan Kelompok Sahabat Obor Mas

## Context
- User meminta flowchart untuk tiga project: `backend-app`, `Web Admin`, dan `Kelompok Sahabat Obor Mas`.
- Output diminta dalam format Mermaid agar dapat di-copy ke draw.io.
- Hasil RESEARCH menunjukkan ada alur lintas aplikasi (dua frontend ke satu backend Laravel API) dan alur internal per aplikasi.

## Goal
- Menyediakan diagram Mermaid yang bisa langsung dipaste ke draw.io.
- Menampilkan relasi antar sistem (cross-project) secara jelas.
- Menyediakan detail alur inti masing-masing project tanpa membuat diagram terlalu padat.

## Detailed Specifications
- File yang akan dibuat/diperbarui:
  - Tidak ada perubahan source code aplikasi.
  - Output diberikan langsung di chat dalam blok Mermaid.
- Diagram yang akan disusun:
  1. `System Overview Flow` (lintas 3 project)
     - Node: User Actor, Web Admin, Kelompok Sahabat Obor Mas App, Backend API (Laravel), Middleware Auth/Role, Controller Layer, Database.
     - Edge: login/request data, bearer token, endpoint `/api/*`, response JSON/export, render UI.
  2. `Backend App Internal Flow`
     - Alur: request masuk -> route `api.php` -> middleware (`auth:sanctum`, role middleware) -> controller resource/auth -> model/service -> database -> response.
     - Cabang khusus: auth public endpoints, admin approval, super admin routes, export excel/pdf.
  3. `Web Admin Internal Flow`
     - Alur: browser -> React Router (`/login`, protected admin, super-admin area) -> service API wrapper -> attach bearer token -> call backend -> update page state.
     - Cabang role: admin vs super_admin untuk akses route.
  4. `Kelompok Sahabat Obor Mas Internal Flow`
     - Alur screen state: splash -> login/register/forgot-reset -> authenticated home/dashboard.
     - Alur auth store: simpan token, `me()` refresh user, idle timeout logout, session expired handler.
     - Integrasi network: `apiClient` -> backend endpoints auth dan data.
- Konvensi Mermaid:
  - Gunakan `flowchart TD` agar kompatibel dan terbaca di draw.io.
  - Gunakan subgraph per sistem untuk mengurangi clutter.
  - Label edge ringkas, fokus pada flow, bukan detail seluruh payload.

## Implementation Checklist
1. Susun struktur diagram overview lintas sistem dengan aktor, 2 frontend, backend API, dan database.
2. Tambahkan alur auth dan data utama pada diagram overview (login, bearer token, CRUD/export).
3. Susun diagram internal `backend-app` mulai dari route hingga response.
4. Tambahkan cabang middleware otorisasi (`member_approved`, `admin`, `super_admin`) pada diagram backend.
5. Tambahkan cabang endpoint auth public dan protected pada diagram backend.
6. Susun diagram internal `Web Admin` mencakup router, protected route, super admin route, dan service API.
7. Tambahkan flow token storage + header Authorization untuk request dari Web Admin.
8. Susun diagram internal `Kelompok Sahabat Obor Mas` berbasis state screen navigation.
9. Tambahkan flow `AuthProvider` (login, refresh user, idle timeout logout) pada diagram Kelompok Sahabat.
10. Validasi setiap diagram agar sintaks Mermaid valid dan siap paste di draw.io.
11. Kirim hasil akhir berupa 4 blok Mermaid terpisah (overview + 3 detail) di chat.

## Risks / Catatan
- Diagram bisa terlalu kompleks jika semua endpoint ditampilkan; perlu fokus pada flow inti.
- Nama node terlalu panjang menurunkan keterbacaan di draw.io; perlu ringkas namun tetap jelas.
- Perbedaan implementasi minor frontend (web vs mobile-webview) disajikan sebagai cabang flow, bukan detail low-level networking.
