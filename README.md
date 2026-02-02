# Sistem Informasi Penggajian & Kepegawaian (Payroll System)

![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-005C84?style=for-the-badge&logo=mysql&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)

## 📖 Deskripsi Proyek

Aplikasi ini adalah **Sistem Informasi Penggajian dan Kepegawaian Berbasis Web** yang dirancang untuk mengotomatisasi proses manajemen sumber daya manusia, pencatatan kehadiran (absensi), serta perhitungan gaji karyawan secara akurat dan transparan.

Sistem ini dikembangkan secara spesifik untuk menangani kompleksitas tunjangan, potongan, dan pelaporan keuangan di lingkungan yayasan pendidikan (Studi kasus: Yayasan Al-Azhar), namun dapat diadaptasi untuk instansi lainnya. Aplikasi mendukung manajemen multi-role mulai dari Super Admin hingga Tenaga Kerja.

## 🚀 Fitur Utama

Sistem ini memiliki fitur lengkap yang mencakup seluruh siklus penggajian dan kepegawaian:

### 1. Manajemen Kepegawaian (HR)
* **Database Karyawan:** Pengelolaan data lengkap karyawan termasuk biodata, jabatan, dan foto profil.
* **Manajemen Jabatan:** Pengaturan hierarki dan struktur jabatan dalam organisasi.
* **Riwayat Kinerja:** Penilaian kinerja berbasis indikator yang dapat disesuaikan.

### 2. Sistem Absensi Cerdas
* **Geolocation Check:** Validasi lokasi absensi berdasarkan radius (Latitude/Longitude) kantor/sekolah untuk memastikan kehadiran fisik.
* **Sesi Absensi:** Pengaturan jam masuk dan pulang yang fleksibel.
* **Rekapitulasi Otomatis:** Perhitungan otomatis jumlah kehadiran, izin, sakit, dan alpa.

### 3. Manajemen Penggajian (Payroll)
* **Kalkulasi Gaji Otomatis:** Menghitung gaji pokok berdasarkan jabatan dan kehadiran.
* **Manajemen Tunjangan Kompleks:**
    * Tunjangan Kehadiran
    * Tunjangan Anak
    * Tunjangan Pengabdian (Masa Kerja)
    * Tunjangan Komunikasi
* **Manajemen Potongan:** Pengaturan potongan gaji (koperasi, kasbon, dll).
* **Slip Gaji Digital:** Pembuatan slip gaji otomatis dalam format PDF.

### 4. Laporan & Distribusi
* **Export Laporan:** Laporan gaji bulanan, rekap absensi, dan laporan per karyawan (Mendukung PDF & Excel).
* **Email Notification:** Fitur kirim slip gaji dan laporan langsung ke email karyawan terkait.
* **Tanda Tangan Digital:** Pengaturan tanda tangan digital untuk validasi dokumen laporan.

### 5. Portal Tenaga Kerja (Employee Self-Service)
* **Dashboard Personal:** Statistik kehadiran dan ringkasan pendapatan.
* **Simulasi Gaji:** Fitur bagi karyawan untuk melakukan estimasi penerimaan gaji.
* **Unduh Slip Gaji:** Akses mandiri untuk mengunduh riwayat slip gaji.

## 🛠️ Teknologi yang Digunakan

* **Framework:** Laravel 12 (PHP ^8.2)
* **Database:** MySQL
* **Frontend:** Blade Templating, Tailwind CSS
* **Authentication:** Laravel Breeze / Sanctum
* **Libraries:**
    * `barryvdh/laravel-dompdf`: Generasi laporan PDF (Slip Gaji/Laporan).
    * `maatwebsite/excel`: Export data ke Excel.
    * `guzzlehttp/guzzle`: HTTP Client.

## 🔐 Hak Akses (Role & Permissions)

Sistem menggunakan *Role-based Access Control* (RBAC) dengan tingkatan sebagai berikut:

1.  **Superadmin / Admin:** Mengelola data master (User, Jabatan, Potongan, Indikator Kinerja).
2.  **Bendahara:** Fokus pada operasional penggajian, validasi absensi, cetak laporan, dan pengiriman email slip gaji.
3.  **Tenaga Kerja:** Akses terbatas untuk melakukan absensi, melihat riwayat gaji, dan mengedit profil pribadi.

## 💻 Instalasi & Penggunaan

Ikuti langkah-langkah berikut untuk menjalankan proyek di komputer lokal:

1.  **Clone Repositori**
    ```bash
    git clone [https://github.com/username/project-penggajian.git](https://github.com/username/project-penggajian.git)
    cd project-penggajian
    ```

2.  **Install Dependencies**
    ```bash
    composer install
    npm install
    ```

3.  **Konfigurasi Environment**
    Salin file `.env.example` menjadi `.env` dan sesuaikan konfigurasi database.
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

4.  **Setup Database**
    Pastikan database MySQL sudah dibuat, lalu jalankan migrasi dan seeder.
    ```bash
    php artisan migrate --seed
    ```

5.  **Jalankan Aplikasi**
    ```bash
    npm run dev
    php artisan serve
    ```

6.  **Akses Aplikasi**
    Buka browser dan kunjungi `http://localhost:8000`.

## 📂 Struktur Folder Penting

* `app/Http/Controllers`: Logika utama aplikasi (Gaji, Absensi, Laporan).
* `app/Models`: Model Eloquent (Karyawan, Jabatan, Tunjangan).
* `resources/views`: Tampilan antarmuka (Blade templates).
* `routes/web.php`: Definisi rute dan grup middleware berdasarkan role.
* `database/migrations`: Skema database.

## 🤝 Kontribusi

Kontribusi selalu diterima! Silakan buat *Pull Request* atau laporkan *Issue* jika menemukan bug atau memiliki saran fitur baru.

## 📄 Lisensi

Proyek ini dilisensikan di bawah [MIT License](https://opensource.org/licenses/MIT).

---
*Dibuat dengan ❤️ oleh Rafli Ananda Rizkillah Gobel*
