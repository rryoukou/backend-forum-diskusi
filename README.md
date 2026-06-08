# 🏛️ Backend Forum Diskusi (Kelompok 5)

<p align="center">
  <img src="https://img.shields.io/badge/Main%20Level-Advanced%20%28Semua%20Level%29-blueviolet?style=for-the-badge&logo=github" alt="Main Level">
  <img src="https://img.shields.io/badge/Architecture-REST%20API-blue?style=for-the-badge" alt="Architecture">
  <img src="https://img.shields.io/badge/Database-Relational-green?style=for-the-badge" alt="Database">
</p>

Repositori ini berisi *source code* komponen **Back-End** untuk aplikasi Forum Diskusi Kelompok 5. Sistem dibangun menggunakan spesifikasi **Advanced (Semua Level)** yang mencakup arsitektur multi-role (*User, Moderator, Admin*), sistem diskusi hierarkis (*nested replies*), mekanisme gamifikasi terintegrasi, serta modul moderasi konten yang komprehensif.

---

## 👥 Detail Tim & Manajemen Proyek

### 👑 Project Leader & Front-End
* **Alfian** — **Ketua Tim & Front-End Developer (Admin Focus)**
  * 🎯 **Tanggung Jawab & Kontribusi:**
    * Mengorkestrasikan tim, memantau *timeline* pengerjaan, dan koordinasi antar-divisi.
    * Pengembangan komponen interaktif dan halaman **Dashboard Admin**.
    * Implementasi fitur administratif manajemen dan pemantauan level reputasi global pengguna.
    * Penyusunan modul visualisasi statistik internal dan kesehatan ekosistem forum.

### 🛡️ Front-End Specialists
* **Citra** — **Front-End Developer (Moderator Focus)**
  * 🎯 **Tanggung Jawab & Kontribusi:**
    * Pengembangan antarmuka khusus **Dashboard Moderator**.
    * Implementasi sistem penanganan pelaporan konten melanggar (*Report/Flag Handling*).
    * Implementasi fitur moderasi penangguhan akun dan pembatasan pengguna (*Ban & Warning User*).
    * Pembuatan komponen pelacakan tindakan pengawasan (*Moderator Action Log*).

* **Bagas** — **Front-End Developer (User Focus)**
  * 🎯 **Tanggung Jawab & Kontribusi:**
    * Pengembangan halaman utama publik (**Homepage**), Detail Postingan, dan Profil Publik Komunitas.
    * Implementasi alur penulisan konten, sistem komentar bersarang (*nested reply*), dan pustaka *Bookmark*.
    * Integrasi visual sistem interaksi (voting, likes, papan peringkat, dan pajangan *Badge/Achievement*).

### ⚙️ Back-End Engine
* **Arya** — **Back-End Developer**
  * 🎯 **Tanggung Jawab & Kontribusi:**
    * Perancangan arsitektur *Entity-Relationship Diagram* (ERD) dan skema database relasional.
    * Pembangunan seluruh *endpoint* **RESTful API** (cakupan tingkat Core, Medium, hingga Advanced).
    * Implementasi sistem autentikasi dan otorisasi berlapis (*Role-Based Access Control* / RBAC).
    * Pengujian performa API, *Quality Control* (QC) integrasi data, dan penanganan keamanan logika bisnis.

---

## 🔑 Matriks Otorisasi Multi-Role

Aplikasi ini mengimplementasikan pembatasan hak akses ketat berbasis peran (*roles*):

| Hak Akses / Kemampuan | User | Moderator | Admin |
| :--- | :---: | :---: | :---: |
| Posting Konten, Komentar, & Nested Reply |  ✓  |  ✓  |  ✓  |
| Voting, Likes, Bookmark, & Earn Points |  ✓  |  ✓  |  ✓  |
| Melaporkan Konten Melanggar (*Report/Flag*) |  ✓  |  ✓  |  ✓  |
| Meninjau Laporan Konten (*Moderation Dashboard*) |  ❌  |  ✓  |  ✓  |
| Manajemen Status Akun Pengguna (*Ban/Warning*) |  ❌  |  ✓  |  ✓  |
| Melihat Catatan Pengawasan (*Moderator Action Log*) |  ❌  |  ✓  |  ✓  |
| Konfigurasi Sistem Global & Statistik Internal |  ❌  |  ❌  |  ✓  |

---

## 📃 Struktur Halaman (Front-End)

Aplikasi terbagi menjadi tiga segmen area utama berdasarkan peruntukan pengguna:

* 🌐 **Situs Publik (User Area):**
  * **Halaman Autentikasi:** Gerbang masuk (Login) dan pendaftaran akun baru (Register).
  * **Halaman Utama (Homepage):** Feed interaktif menampilkan postingan terbaru/trending, pencarian, filter kategori/tag, serta komponen *Leaderboard*.
  * **Halaman Detail Postingan:** Eksplorasi penuh isi konten, penandaan solusi terbaik (*Accepted Answer*), serta pohon diskusi berstruktur (*Nested Comments*).
  * **Halaman Profil Pengguna:** Ruang personal berisi data bio, koleksi capaian (*Badges/Achievement*), total akumulasi poin reputasi, dan rekam jejak postingan.
  * **Halaman Pusat Notifikasi:** Hub informasi pembaruan interaksi *real-time* (like, reply, badge baru) dengan kontrol *Mark as Read*.

* 🛡️ **Dashboard Pengawasan (Moderator Dashboard):**
  * Antarmuka resolusi sengketa konten dan pengelolaan antrean laporan (*report queue*).
  * Panel kontrol penegakan disiplin komunitas untuk tindakan penangguhan (*ban/warning* akun).
  * Panel riwayat aksi moderasi internal forum (*moderation logs*).

* 👑 **Dashboard Manajemen Utama (Admin Dashboard):**
  * Konsol kontrol manajemen sistem forum berskala global.
  * Panel analitik ringkasan grafik performa aktivitas harian forum.

---

## 🚀 Cakupan Fitur Terimplementasi (Advanced Spec)

Berdasarkan kesepakatan cetak biru perencanaan, arsitektur backend menyediakan fungsionalitas komprehensif berikut:

### 1. Autentikasi & Manajemen Pengguna
* **Sistem Autentikasi Aman:** Registrasi enkripsi kata sandi dan manajemen sesi masuk.
* **Role-Based Access Control (RBAC):** Proteksi *middleware* untuk tiga tingkatan hak akses (`admin`, `mod`, `user`).
* **Profil Dinamis:** Penyimpanan data personalia kustomisasi gambar profil (avatar) dan deskripsi diri (bio).
* **Social Graph:** Hubungan relasional antarpengguna via mekanisme *Follow/Unfollow* pengguna lain.
* **Reputasi Dinamis:** Sistem penentuan peringkat otomatis level akun (`users.level`).

### 2. Postingan & Mesin Diskusi
* **CRUD Konten Utama:** Operasi pembuatan, pembacaan, pembaruan, dan penghapusan postingan (`posts`).
* **Taksonomi Forum:** Pengelompokan data menggunakan relasi banyak-ke-banyak untuk Tag dan Kategori (`post_tags`, `categories`).
* **Mekanisme Solusi Terbaik:** Kemampuan menandai satu komentar spesifik sebagai jawaban tervalid (`posts.accepted_answer_id`).
* **Penyimpanan Pustaka:** Fitur penanda postingan favorit (*Bookmark*) ke dalam koleksi personal pengguna (`bookmarks`).
* **Audit Trail Postingan:** Pencatatan riwayat setiap perubahan isi konten postingan (`post_edit_history`).
* **Algoritma Tren Data:** Formula penentuan konten populer secara dinamis berbasis metrik jumlah penayangan dan skor agregat interaksi (`view_count`, `vote_score`).

### 3. Komentar & Interaksi Komunitas
* **Struktur Komentar Bersarang:** Sistem diskusi hierarkis tak terbatas menggunakan relasi *self-referencing table* (`comments.parent_id`).
* **Modifikasi & Jejak Komentar:** CRUD komentar dilengkapi pencatatan riwayat perubahan teks (`comment_edit_history`).
* **Sistem Evaluasi Konten (Upvote/Downvote):** Manajemen penilaian konten dua arah pada postingan maupun komentar menggunakan tabel transaksi (`votes`).
* **Sistem Apresiasi Ringan (Likes):** Alternatif interaksi ekspresif satu arah pada postingan serta komentar (`likes`).
* **Mesin Pencarian & Filter:** Kemampuan pencarian teks penuh (*full-text search*) serta penyaringan berlapis berdasarkan tag, kategori, atau kontributor tertentu.

### 4. Gamifikasi & Komunikasi
* **Sistem Log Poin Otomatis:** Pencatatan mutasi penambahan/pengurangan poin berdasarkan interaksi pengguna di dalam forum (`points_log`).
* **Penghargaan Komunitas (Badges):** Modul pemberian lencana apresiasi otomatis/manual beserta tabel relasi kepemilikan (`badges`, `user_badges`).
* **Papan Peringkat Publik (Leaderboard):** Agregasi data pengguna dengan reputasi tertinggi secara *real-time* (`users.reputation_points`).
* **Hub Notifikasi Terpusat:** Pengiriman pemberitahuan aktivitas terarah kepada pengguna target, dilengkapi penanda status baca (`notifications.is_read`).

### 5. Keamanan & Moderasi Konten
* **Sistem Pelaporan Pelanggaran:** Mekanisme penampungan keluhan pengguna terhadap konten yang tidak pantas (`reports`).
* **Transparansi Tindakan Pengawasan:** Dokumentasi otomatis setiap keputusan moderasi yang diambil oleh staf pengawas (`moderation_logs`).
* **Penegakan Disiplin Komunitas:** Sistem pemblokiran akun pengguna yang terbukti melanggar aturan forum (`users.is_banned`).
