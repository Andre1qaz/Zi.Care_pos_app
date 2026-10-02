from odoo import fields, models, api


class PosDashboardConfig(models.Model):
    _name = 'pos.dashboard.config'
    _description = 'Konfigurasi Koneksi Database POS (MariaDB)'

    name = fields.Char(default='Koneksi Database POS', required=True)
    db_host = fields.Char(string='Host', default='localhost', required=True)
    db_port = fields.Char(string='Port', default='3306', required=True)
    db_user = fields.Char(string='User', required=True)
    db_password = fields.Char(string='Password')
    db_name = fields.Char(string='Nama Database', required=True)

    @api.model
    def get_config(self):
        """Ambil record konfigurasi tunggal, buat default kalau belum ada."""
        config = self.search([], limit=1)
        if not config:
            config = self.create({
                'name': 'Koneksi Database POS',
                'db_host': 'localhost',
                'db_port': '3306',
                'db_user': '',
                'db_password': '',
                'db_name': '',
            })
        return config
