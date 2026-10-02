<?php

/**
 * Script untuk mengecek database PostgreSQL di Docker container
 * Menghubungkan ke PostgreSQL yang berjalan di Docker
 */

echo "=== Mengecek Database PostgreSQL di Docker ===\n\n";

echo "Karena PostgreSQL berjalan di Docker, kita perlu mengecek melalui beberapa cara:\n\n";

echo "=== Opsi 1: Cek melalui pgAdmin ===\n";
echo "1. Buka pgAdmin di browser (biasanya http://localhost:5050 atau port lain)\n";
echo "2. Connect ke server PostgreSQL\n";
echo "3. Buka Databases > lihat list database yang ada\n";
echo "4. Cari database yang berhubungan dengan Odoo\n\n";

echo "=== Opsi 2: Cek melalui Docker CLI ===\n";
echo "Jalankan perintah berikut untuk melihat container PostgreSQL yang berjalan:\n";
echo "docker ps\n\n";

echo "Kemudian jalankan perintah ini untuk masuk ke container PostgreSQL:\n";
echo "docker exec -it <nama_container_postgres> psql -U <username_postgres> -l\n\n";

echo "Contoh (sesuaikan dengan nama container yang muncul):\n";
echo "docker exec -it postgres psql -U postgres -l\n";
echo "atau\n";
echo "docker exec -it odoo_db psql -U odoo -l\n\n";

echo "=== Opsi 3: Cek melalui Portainer ===\n";
echo "1. Buka Portainer di browser\n";
echo "2. Pilih container PostgreSQL\n";
echo "3. Buka console/container terminal\n";
echo "4. Jalankan: psql -U <username> -l\n\n";

echo "=== Opsi 4: Cek konfigurasi Odoo container ===\n";
echo "1. Di Portainer, buka container Odoo\n";
echo "2. Cek environment variables atau command\n";
echo "3. Lihat parameter -d atau --database untuk nama database\n\n";

echo "Setelah menemukan nama database Odoo, update file .env backend:\n";
echo "ODOO_DB=<nama_database_yang_ditemukan>\n\n";

echo "Jika sudah menemukan nama database, beritahu saya dan saya akan update konfigurasi.\n";
