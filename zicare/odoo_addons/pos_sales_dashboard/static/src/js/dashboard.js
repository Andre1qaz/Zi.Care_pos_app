/** @odoo-module **/

import {
    Component,
    useState,
    useEffect,
    onWillStart,
    onMounted,
    onWillUnmount,
    useRef,
} from "@odoo/owl";
import { registry } from "@web/core/registry";
import { rpc } from "@web/core/network/rpc";
import { loadJS } from "@web/core/assets";

const AUTO_REFRESH_INTERVAL_MS = 30000; // 30 detik

export class PosSalesDashboard extends Component {
    static template = "pos_sales_dashboard.Dashboard";

    setup() {
        this.chartRef = useRef("categoryChart");
        this.chart = null;
        this.autoRefreshTimer = null;

        this.state = useState({
            categories: [],
            dateFrom: this._defaultDateFrom(),
            dateTo: this._defaultDateTo(),
            loading: true,
            error: null,
            autoRefresh: true,
            lastUpdated: null,
        });

        onWillStart(async () => {
            await loadJS("/web/static/lib/Chart/Chart.js");
        });

        onMounted(() => {
            this.loadData();
            this._setupAutoRefresh();
        });

        onWillUnmount(() => {
            this._clearAutoRefresh();
        });

        // Gambar ulang chart setiap kali canvas SUDAH ada di DOM
        // (loading selesai) dan ada data kategori untuk ditampilkan.
        // Ini menghindari race condition: renderChart() dipanggil
        // sebelum <canvas> ter-mount karena masih dalam kondisi loading.
        useEffect(
            () => {
                if (!this.state.loading && this.state.categories.length && this.chartRef.el) {
                    this.renderChart();
                }
            },
            () => [this.state.loading, this.state.categories]
        );
    }

    _defaultDateFrom() {
        const d = new Date();
        d.setDate(d.getDate() - 30);
        return d.toISOString().slice(0, 10);
    }

    _defaultDateTo() {
        return new Date().toISOString().slice(0, 10);
    }

    _setupAutoRefresh() {
        this._clearAutoRefresh();
        if (this.state.autoRefresh) {
            this.autoRefreshTimer = setInterval(() => {
                this.loadData();
            }, AUTO_REFRESH_INTERVAL_MS);
        }
    }

    _clearAutoRefresh() {
        if (this.autoRefreshTimer) {
            clearInterval(this.autoRefreshTimer);
            this.autoRefreshTimer = null;
        }
    }

    async loadData() {
        // Saat auto-refresh berjalan di background, jangan tampilkan
        // layar "Memuat data..." supaya tidak mengganggu (cuma dipakai
        // saat pertama kali buka halaman / klik Filter manual).
        const isBackgroundRefresh = this.state.categories.length > 0 && this.state.autoRefresh;
        if (!isBackgroundRefresh) {
            this.state.loading = true;
        }
        this.state.error = null;
        try {
            const result = await rpc("/pos_sales_dashboard/data", {
                date_from: this.state.dateFrom,
                date_to: this.state.dateTo,
            });
            this.state.categories = result.categories;
            this.state.lastUpdated = new Date().toLocaleTimeString("id-ID");
        } catch (err) {
            this.state.error = "Gagal memuat data. Cek koneksi MariaDB di menu Konfigurasi Database.";
        } finally {
            this.state.loading = false;
        }
    }

    renderChart() {
        if (!this.chartRef.el) {
            return;
        }
        if (this.chart) {
            this.chart.destroy();
        }
        const ctx = this.chartRef.el.getContext("2d");
        this.chart = new Chart(ctx, {
            type: "bar",
            data: {
                labels: this.state.categories.map((c) => c.category_name),
                datasets: [
                    {
                        label: "Total Penjualan (Rp)",
                        data: this.state.categories.map((c) => c.total_sales),
                        backgroundColor: "#714B67",
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
            },
        });
    }

    onDateChange(ev) {
        const { name, value } = ev.target;
        this.state[name] = value;
    }

    onFilterClick() {
        this.loadData();
    }

    onToggleAutoRefresh(ev) {
        this.state.autoRefresh = ev.target.checked;
        this._setupAutoRefresh();
    }

    _buildExportUrl(path) {
        const params = new URLSearchParams({
            date_from: this.state.dateFrom,
            date_to: this.state.dateTo,
        });
        return `${path}?${params.toString()}`;
    }

    onExportCsvClick() {
        window.open(this._buildExportUrl("/pos_sales_dashboard/export/csv"), "_blank");
    }

    onExportPdfClick() {
        window.open(this._buildExportUrl("/pos_sales_dashboard/export/pdf"), "_blank");
    }
}

registry.category("actions").add("pos_sales_dashboard.dashboard", PosSalesDashboard);
