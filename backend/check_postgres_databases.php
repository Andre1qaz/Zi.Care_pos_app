<?php

/**
 * Script untuk mengecek database PostgreSQL yang tersedia
 * Ini membutuhkan akses ke PostgreSQL
 */

echo "=== Mengecek Database PostgreSQL ===\n\n";

echo "Silakan jalankan perintah berikut di terminal untuk melihat database PostgreSQL:\n\n";

echo "1. Jika PostgreSQL dijalankan sebagai service:\n";
echo "   psql -U postgres -l\n\n";

echo "2. Atau jika menggunakan Windows Authentication:\n";
echo "   psql -l\n\n";

echo "3. Atau connect ke PostgreSQL dan jalankan:\n";
echo "   SELECT datname FROM pg_database WHERE datistemplate = false;\n\n";

echo "Setelah menemukan nama database Odoo, update file .env dengan:\n";
echo "ODOO_DB=<nama_database_yang_ditemukan>\n\n";

echo "=== Alternatif: Cek proses Odoo yang berjalan ===\n";
echo "Jalankan perintah ini di PowerShell untuk melihat bagaimana Odoo dijalankan:\n";
echo "Get-Process python\n\n";

echo "Lihat parameter --db_host dan --database pada perintah yang menjalankan Odoo\n";
