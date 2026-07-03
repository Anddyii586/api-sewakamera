Project Idea

## Judul Proyek
API Sistem Rental Kamera dan Peralatan Fotografi

## Latar Belakang Masalah

Rental kamera dan peralatan fotografi membutuhkan pengelolaan data alat, kategori, ketersediaan stok, penyewaan, pembayaran, dan pencatatan aktivitas secara terstruktur. Jika dikelola secara manual, dapat terjadi kesalahan data stok, alat disewa melebihi jumlah tersedia, pembayaran tidak sesuai, serta sulitnya memantau aktivitas pengguna. Oleh karena itu, dibutuhkan REST API yang dapat digunakan sebagai backend untuk sistem rental kamera dan peralatan fotografi.

## Tujuan Proyek

Membangun layanan REST API untuk mengelola data kategori, kamera/peralatan fotografi, penyewaan, pembayaran, autentikasi pengguna, serta log aktivitas API. API ini dapat digunakan sebagai backend aplikasi rental dan diuji menggunakan Postman.

## Deskripsi Fitur Utama

- JWT authentication untuk register, login, me, refresh, dan logout.
- CRUD categories untuk mengelola kategori alat.
- CRUD items untuk mengelola kamera dan peralatan fotografi.
- Rental flow untuk membuat penyewaan dengan banyak detail item.
- Payment flow untuk mencatat pembayaran dan mengubah status rental.
- API logging untuk mencatat request dan response ke database.
- JSON response konsisten untuk success, error, dan validation error.

## Teknologi yang Digunakan

- Laravel 13
- PHP 8.4
- MySQL
- JWT / tymon/jwt-auth
- Postman
- GitHub
- JSON REST API
- Laravel Herd

## Peran Masing-Masing Anggota

- Anggota 1: JWT authentication, middleware, database schema, logging API,CRUD categories/items
- Anggota 2: rental flow, payment flow, dokumentasi, testing.
