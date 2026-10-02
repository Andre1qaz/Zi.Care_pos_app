import csv
import io
import logging
from datetime import date, datetime, timedelta

try:
    import pymysql
    import pymysql.cursors
except ImportError:
    pymysql = None

from odoo import http
from odoo.http import content_disposition, request

_logger = logging.getLogger(__name__)


class PosSalesDashboardController(http.Controller):

    # ------------------------------------------------------------------
    # Koneksi ke database POS eksternal (MariaDB, dipakai backend Phalcon)
    # ------------------------------------------------------------------
    def _get_external_connection(self):
        if pymysql is None:
            _logger.error("Library pymysql belum terinstall. Jalankan: pip install pymysql")
            return None

        config = request.env['pos.dashboard.config'].sudo().get_config()
        host = config.db_host or 'localhost'
        port = int(config.db_port or 3306)
        user = config.db_user or ''
        password = config.db_password or ''
        dbname = config.db_name or ''

        try:
            return pymysql.connect(
                host=host,
                port=port,
                user=user,
                password=password,
                database=dbname,
                cursorclass=pymysql.cursors.DictCursor,
                connect_timeout=5,
            )
        except Exception as e:
            _logger.error("Gagal konek ke MariaDB POS (%s@%s:%s/%s): %s", user, host, port, dbname, e)
            return None

    def _fetch_external_sales_by_category(self, date_from, date_to):
        """Ambil total penjualan per kategori langsung dari MariaDB POS.

        Skema asli (dikonfirmasi dari database pos_db):
        - invoices(id, payment_status, created_at, ...)
        - invoice_details(id, invoice_id, product_id, quantity, price)
        - products(id, category_id, ...)
        - categories(id, category_name, ...)
        """
        query = """
            SELECT
                c.category_name AS category_name,
                SUM(idt.quantity) AS total_items,
                SUM(idt.quantity * idt.price) AS total_sales
            FROM invoice_details idt
            JOIN invoices i ON i.id = idt.invoice_id
            JOIN products p ON p.id = idt.product_id
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE i.created_at BETWEEN %s AND %s
              AND i.payment_status = 'paid'
            GROUP BY c.category_name
            ORDER BY total_sales DESC
        """
        conn = self._get_external_connection()
        if conn is None:
            return []
        try:
            with conn.cursor() as cursor:
                cursor.execute(query, (date_from, date_to))
                rows = cursor.fetchall()
                for row in rows:
                    if not row.get('category_name'):
                        row['category_name'] = 'Tanpa Kategori'
                return rows
        except Exception as e:
            _logger.error("Query MariaDB POS gagal: %s", e)
            return []
        finally:
            conn.close()

    # ------------------------------------------------------------------
    # Data yang sudah tersinkron di sisi Odoo (Accounting)
    # ------------------------------------------------------------------
    def _fetch_odoo_sales_by_category(self, date_from, date_to):
        """Ambil data yang sudah tersinkron di Odoo (Accounting).

        Catatan: baris invoice dengan produk yang TIDAK punya kategori
        di-skip. Produk tanpa kategori di sisi Odoo biasanya bukan hasil
        sync dari POS kamu (bisa data demo/testing bawaan Odoo atau
        produk lain yang tidak relevan), jadi tidak ikut dihitung supaya
        tidak mencemari laporan dengan angka yang tidak representatif.
        """
        AccountMove = request.env['account.move'].sudo()
        moves = AccountMove.search([
            ('invoice_date', '>=', date_from),
            ('invoice_date', '<=', date_to),
            ('move_type', '=', 'out_invoice'),
            ('state', '=', 'posted'),
        ])
        category_totals = {}
        for move in moves:
            for line in move.invoice_line_ids:
                if not line.product_id or not line.product_id.categ_id:
                    continue
                category = line.product_id.categ_id.name
                bucket = category_totals.setdefault(category, {'total_items': 0.0, 'total_sales': 0.0})
                bucket['total_items'] += line.quantity
                bucket['total_sales'] += line.price_subtotal
        return [
            {'category_name': k, 'total_items': v['total_items'], 'total_sales': v['total_sales']}
            for k, v in category_totals.items()
        ]

    # ------------------------------------------------------------------
    # Helper: bangun data laporan lengkap (dipakai bersama oleh endpoint
    # JSON untuk dashboard OWL, dan endpoint export CSV/PDF)
    # ------------------------------------------------------------------
    def _build_report_data(self, date_from=None, date_to=None):
        if not date_to:
            date_to = str(date.today())
        if not date_from:
            date_from = str(date.today() - timedelta(days=30))

        external_data = self._fetch_external_sales_by_category(date_from, date_to + ' 23:59:59')
        odoo_data = self._fetch_odoo_sales_by_category(date_from, date_to)

        combined = {}
        for row in external_data:
            combined[row['category_name']] = {
                'category_name': row['category_name'],
                'total_items': float(row['total_items'] or 0),
                'total_sales': float(row['total_sales'] or 0),
                'source': 'pos_mariadb',
            }
        for row in odoo_data:
            if row['category_name'] in combined:
                combined[row['category_name']]['total_items'] += row['total_items']
                combined[row['category_name']]['total_sales'] += row['total_sales']
                combined[row['category_name']]['source'] = 'gabungan'
            else:
                combined[row['category_name']] = {
                    'category_name': row['category_name'],
                    'total_items': row['total_items'],
                    'total_sales': row['total_sales'],
                    'source': 'odoo',
                }

        # Proses target penjualan: gabungkan target yang di-input user
        # (model pos.sales.target) dengan realisasi penjualan aktual,
        # lalu hitung persentase pencapaian per kategori.
        Target = request.env['pos.sales.target'].sudo()
        targets = Target.get_targets_for_period(date_from, date_to)

        for category_name, target_amount in targets.items():
            if category_name not in combined:
                combined[category_name] = {
                    'category_name': category_name,
                    'total_items': 0.0,
                    'total_sales': 0.0,
                    'source': 'tidak ada penjualan',
                }
            combined[category_name]['target_amount'] = target_amount

        for row in combined.values():
            target_amount = row.get('target_amount')
            if target_amount:
                row['achievement_percent'] = round((row['total_sales'] / target_amount) * 100, 1)
            else:
                row['target_amount'] = None
                row['achievement_percent'] = None

        categories = sorted(combined.values(), key=lambda x: x['total_sales'], reverse=True)
        total_sales_all = sum(c['total_sales'] for c in categories)
        total_items_all = sum(c['total_items'] for c in categories)

        return {
            'date_from': date_from,
            'date_to': date_to,
            'categories': categories,
            'total_sales_all': total_sales_all,
            'total_items_all': total_items_all,
            'generated_at': datetime.now().strftime('%d-%m-%Y %H:%M:%S'),
        }

    # ------------------------------------------------------------------
    # Endpoint publik untuk dashboard OWL maupun frontend Vue POS
    # ------------------------------------------------------------------
    @http.route('/pos_sales_dashboard/data', type='json', auth='user')
    def get_dashboard_data(self, date_from=None, date_to=None, **kwargs):
        return self._build_report_data(date_from, date_to)

    # ------------------------------------------------------------------
    # Export CSV
    # ------------------------------------------------------------------
    @http.route('/pos_sales_dashboard/export/csv', type='http', auth='user')
    def export_csv(self, date_from=None, date_to=None, **kwargs):
        data = self._build_report_data(date_from, date_to)

        output = io.StringIO()
        writer = csv.writer(output, delimiter=';')
        writer.writerow(['Laporan Penjualan POS per Kategori'])
        writer.writerow(['Periode', '%s s/d %s' % (data['date_from'], data['date_to'])])
        writer.writerow(['Dicetak pada', data['generated_at']])
        writer.writerow([])
        writer.writerow([
            'Kategori', 'Jumlah Item Terjual', 'Total Penjualan (Rp)',
            'Target (Rp)', 'Pencapaian (%)', 'Sumber Data',
        ])
        for row in data['categories']:
            writer.writerow([
                row['category_name'],
                row['total_items'],
                row['total_sales'],
                row.get('target_amount') if row.get('target_amount') else '',
                row.get('achievement_percent') if row.get('achievement_percent') is not None else '',
                row['source'],
            ])
        writer.writerow([])
        writer.writerow(['TOTAL', data['total_items_all'], data['total_sales_all'], '', '', ''])

        # utf-8-sig supaya Excel di Windows baca karakter non-ASCII dengan benar
        csv_bytes = output.getvalue().encode('utf-8-sig')

        filename = 'laporan-penjualan-pos_%s_sd_%s.csv' % (data['date_from'], data['date_to'])
        headers = [
            ('Content-Type', 'text/csv; charset=utf-8'),
            ('Content-Disposition', content_disposition(filename)),
        ]
        return request.make_response(csv_bytes, headers=headers)

    # ------------------------------------------------------------------
    # Export PDF
    # ------------------------------------------------------------------
    @http.route('/pos_sales_dashboard/export/pdf', type='http', auth='user')
    def export_pdf(self, date_from=None, date_to=None, **kwargs):
        data = self._build_report_data(date_from, date_to)

        html = request.env['ir.qweb']._render('pos_sales_dashboard.report_pdf_template', {
            'date_from': data['date_from'],
            'date_to': data['date_to'],
            'categories': data['categories'],
            'total_sales_all': data['total_sales_all'],
            'total_items_all': data['total_items_all'],
            'generated_at': data['generated_at'],
        })

        pdf_content = request.env['ir.actions.report'].sudo()._run_wkhtmltopdf([html])

        filename = 'laporan-penjualan-pos_%s_sd_%s.pdf' % (data['date_from'], data['date_to'])
        headers = [
            ('Content-Type', 'application/pdf'),
            ('Content-Length', len(pdf_content)),
            ('Content-Disposition', content_disposition(filename)),
        ]
        return request.make_response(pdf_content, headers=headers)
