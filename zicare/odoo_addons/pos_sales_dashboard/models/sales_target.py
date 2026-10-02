from odoo import fields, models, api
from odoo.exceptions import ValidationError


class PosSalesTarget(models.Model):
    _name = 'pos.sales.target'
    _description = 'Target Penjualan POS per Kategori'
    _order = 'period_start desc, category_name'

    name = fields.Char(compute='_compute_name', store=True)
    category_name = fields.Char(string='Kategori', required=True,
                                 help='Isi persis sama dengan nama kategori di aplikasi POS, '
                                      'misalnya: Obat, APD, Diagnostik, Alat Medis, dll.')
    period_start = fields.Date(string='Periode Mulai', required=True)
    period_end = fields.Date(string='Periode Selesai', required=True)
    target_amount = fields.Float(string='Target Penjualan (Rp)', required=True)
    note = fields.Text(string='Catatan')

    @api.depends('category_name', 'period_start', 'period_end')
    def _compute_name(self):
        for rec in self:
            if rec.category_name and rec.period_start and rec.period_end:
                rec.name = '%s (%s - %s)' % (
                    rec.category_name,
                    rec.period_start.strftime('%d/%m/%Y'),
                    rec.period_end.strftime('%d/%m/%Y'),
                )
            else:
                rec.name = 'Target Penjualan Baru'

    @api.constrains('period_start', 'period_end')
    def _check_period(self):
        for rec in self:
            if rec.period_start and rec.period_end and rec.period_start > rec.period_end:
                raise ValidationError('Periode Mulai tidak boleh lebih besar dari Periode Selesai.')

    @api.constrains('target_amount')
    def _check_target_amount(self):
        for rec in self:
            if rec.target_amount < 0:
                raise ValidationError('Target Penjualan tidak boleh bernilai negatif.')

    @api.model
    def get_targets_for_period(self, date_from, date_to):
        """Ambil total target per kategori yang periodenya overlap dengan
        rentang tanggal yang sedang dilihat di dashboard.

        Kalau ada lebih dari satu target yang overlap untuk kategori yang
        sama, nilainya dijumlahkan (mis. target per minggu yang totalnya
        dipakai untuk lihat pencapaian bulanan).
        """
        targets = self.search([
            ('period_start', '<=', date_to),
            ('period_end', '>=', date_from),
        ])
        result = {}
        for t in targets:
            result[t.category_name] = result.get(t.category_name, 0.0) + t.target_amount
        return result
