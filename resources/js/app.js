import './bootstrap';
import Alpine from 'alpinejs';

// --- Thème clair / sombre (persisté) -------------------------------------------------
Alpine.store('theme', {
    dark: document.documentElement.classList.contains('dark'),
    toggle() {
        this.dark = !this.dark;
        document.documentElement.classList.toggle('dark', this.dark);
        try { localStorage.setItem('theme', this.dark ? 'dark' : 'light'); } catch (e) {}
    },
});

// --- Formatage monétaire (FCFA) ------------------------------------------------------
const fmt = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 2 });
window.money = (n) => fmt.format(Number(n) || 0) + ' FCFA';

// --- Éditeur de lignes de devis / facture --------------------------------------------
Alpine.data('documentForm', (initialLines = [], articles = []) => ({
    articles,
    lines: initialLines.length ? initialLines : [],

    init() {
        if (!this.lines.length) this.add();
    },
    add() {
        this.lines.push({ key: crypto.randomUUID(), article_id: '', description: '', quantity: 1, unit_price: 0, tax_rate: 18 });
    },
    remove(i) {
        if (this.lines.length > 1) this.lines.splice(i, 1);
    },
    pick(line) {
        const a = this.articles.find((x) => String(x.id) === String(line.article_id));
        if (!a) return;
        line.description = a.name;
        line.unit_price = Number(a.unit_price);
        line.tax_rate = Number(a.tax_rate);
    },
    lineSubtotal(l) { return Math.round((Number(l.quantity) || 0) * (Number(l.unit_price) || 0) * 100) / 100; },
    lineTax(l) { return Math.round(this.lineSubtotal(l) * (Number(l.tax_rate) || 0)) / 100; },
    get subtotal() { return this.lines.reduce((s, l) => s + this.lineSubtotal(l), 0); },
    get tax() { return this.lines.reduce((s, l) => s + this.lineTax(l), 0); },
    get total() { return this.subtotal + this.tax; },
}));

window.Alpine = Alpine;
Alpine.start();
