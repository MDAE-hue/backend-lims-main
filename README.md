# LIMS Backend

**Laboratory Information Management System (LIMS)**.  
Backend ini menyediakan API dan logika bisnis untuk manajemen laboratorium, laporan, persetujuan, penerbitan COA, dan audit trail.

---

## Fitur Utama

Backend menyediakan endpoint dan fungsionalitas untuk mendukung sistem LIMS:

- **Manajemen User:** CRUD user dan role-based access control untuk admin dan staff laboratorium.
- **Manajemen Report:** Input, update, tracking, dan status approval laporan laboratorium.
- **Approval Workflow:** Persetujuan laporan oleh user yang berwenang.
- **Penerbitan COA (Certificate of Analysis):** Pembuatan, penyimpanan, dan distribusi COA.
- **Audit Trail:** Mencatat aktivitas pengguna untuk keperluan keamanan dan compliance.
- **Dashboard API:** Menyediakan data statistik dan ringkasan laboratorium untuk frontend.

---

## Tech Stack

Backend dibangun menggunakan:

- **Framework:** [Laravel Lumen](https://lumen.laravel.com/)  
- **Language:** PHP  
- **Database:** PostgreSQL  
- **Authentication:** JWT

Frontend akan terhubung ke backend ini melalui REST API.

---

## Dokumentasi API

Dokumentasi dapat diakses melalui Live Server:
```bash
swagger-docs/index.html
```
