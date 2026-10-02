{
    'name': 'POS Sales Dashboard',
    'version': '19.0.1.0.0',
    'category': 'Sales/Point of Sale',
    'summary': 'Dashboard BI untuk laporan penjualan POS (gabungan MariaDB + Odoo)',
    'description': """
POS Sales Dashboard
====================
Menambahkan dashboard interaktif (OWL) pada Odoo yang menggabungkan data
penjualan dari database POS eksternal (MariaDB, backend Phalcon) dengan
data yang sudah tersinkron di Odoo (Accounting), menampilkan grafik
kategori terlaris (Obat, APD, Alat Medis, Perawatan Luka, dst).

Fitur:
- Endpoint JSON /pos_sales_dashboard/data untuk data gabungan MariaDB + Odoo
- Dashboard OWL dengan chart batang + tabel rincian kategori
- Filter rentang tanggal
- Konfigurasi koneksi MariaDB via Settings > POS Sales Dashboard
- Endpoint yang sama bisa dikonsumsi dari frontend Vue.js POS
    """,
    'author': 'Andre Christian Saragih',
    'depends': ['base', 'web', 'account'],
    'external_dependencies': {'python': ['pymysql']},
    'data': [
        'security/ir.model.access.csv',
        'views/dashboard_views.xml',
        'views/report_templates.xml',
    ],
    'assets': {
        'web.assets_backend': [
            'pos_sales_dashboard/static/src/js/dashboard.js',
            'pos_sales_dashboard/static/src/xml/dashboard.xml',
            'pos_sales_dashboard/static/src/scss/dashboard.scss',
        ],
    },
    'installable': True,
    'application': False,
    'license': 'LGPL-3',
}
