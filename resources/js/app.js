import './bootstrap';
import Alpine from 'alpinejs';
import confetti from 'canvas-confetti';

const CONFETTI_COLORS = ['#ec3013', '#201e1d', '#ffc4b8', '#ff563c', '#ffffff', '#7d7979'];

/** Avatar faces, mirrored from App\Support\FridgeMood. */
const FACES = {
    happy: { mouth: 'M38 76Q50 88 62 76', brows: 'M34 48L46 46M54 46L66 48', fill: '#ffffff' },
    meh: { mouth: 'M40 78L60 78', brows: 'M34 49L46 49M54 49L66 49', fill: '#ffffff' },
    angry: { mouth: 'M38 84Q50 72 62 84', brows: 'M33 46L46 53M67 46L54 53', fill: '#ffc4b8' },
};

const vibrate = (pattern) => {
    try {
        navigator.vibrate?.(pattern);
    } catch {
        // Vibration is a nice-to-have; some browsers throw when not triggered by a gesture.
    }
};

/* ------------------------------------------------------------------ */
/* Global UI store: toast + push banner                                */
/* ------------------------------------------------------------------ */
document.addEventListener('alpine:init', () => {
    Alpine.store('ui', {
        toastText: '',
        pushText: '',
        pushTime: '08:00',
        pushDismissUrl: null,
        toastTimer: null,
        pushTimer: null,

        toast(message) {
            clearTimeout(this.toastTimer);
            this.toastText = message;
            this.toastTimer = setTimeout(() => (this.toastText = ''), 2400);
        },

        push(message, time = '08:00', dismissUrl = null) {
            clearTimeout(this.pushTimer);
            this.pushText = message;
            this.pushTime = time;
            this.pushDismissUrl = dismissUrl;
            this.pushTimer = setTimeout(() => this.closePush(), 6000);
        },

        closePush() {
            clearTimeout(this.pushTimer);
            if (this.pushDismissUrl) {
                window.axios.patch(this.pushDismissUrl).catch(() => {});
                this.pushDismissUrl = null;
            }
            this.pushText = '';
        },
    });

    /* -------------------------------------------------------------- */
    /* Fach dashboard                                                  */
    /* -------------------------------------------------------------- */
    Alpine.data('fridgeDashboard', (config) => ({
        items: config.items,
        saved: config.saved,
        trashed: config.trashed,
        filter: 'all',
        open: {},
        leaving: {},

        init() {
            if (config.alert) {
                this.$store.ui.push(config.alert.message, config.alert.time, config.alert.dismiss_url);
            }
        },

        get crit() {
            return this.items.filter((i) => i.days <= 3);
        },

        get fresh() {
            return this.items.filter((i) => i.days > 3);
        },

        isVisible(id) {
            const item = this.items.find((i) => i.id === id);
            if (!item) return false;
            if (this.filter === 'crit') return item.days <= 3;
            if (this.filter === 'fresh') return item.days > 3;
            return true;
        },

        get isEmpty() {
            return !this.items.some((i) => this.isVisible(i.id));
        },

        isOpen(item) {
            return !!this.open[item.id] || item.days <= 1;
        },

        toggle(id) {
            this.open[id] = !this.open[id];
        },

        get baro() {
            const minDays = this.items.length ? Math.min(...this.items.map((i) => i.days)) : 99;
            const level = minDays <= 0 ? 3 : minDays <= 3 ? 2 : 1;
            const variants = {
                1: { word: 'Entspannt', mood: 'happy', line: 'Nichts läuft ab. Ich traue dem Frieden nicht.' },
                2: { word: 'Genervt', mood: 'meh', line: `${this.crit.length} Artikel laufen in 2–3 Tagen ab. Ich zähle mit.` },
                3: { word: 'Sauer', mood: 'angry', line: `${this.items.filter((i) => i.days <= 0).length} Artikel laufen heute ab oder sind schon hinüber.` },
            };
            const face = FACES[variants[level].mood];

            return { level, ...variants[level], ...face, fill: level === 3 ? 'transparent' : face.fill };
        },

        async act(item, kind, event) {
            if (this.leaving[item.id]) return;

            if (kind === 'eat') {
                vibrate([100, 50, 100]);
                confetti({
                    particleCount: 90,
                    spread: 360,
                    startVelocity: 30,
                    gravity: 1.1,
                    ticks: 90,
                    colors: CONFETTI_COLORS,
                    origin: { x: event.clientX / window.innerWidth, y: event.clientY / window.innerHeight },
                    disableForReducedMotion: true,
                });
            }

            this.leaving[item.id] = kind;

            try {
                const { data } = await window.axios.patch(kind === 'eat' ? item.consume_url : item.trash_url);
                setTimeout(() => {
                    this.items = this.items.filter((i) => i.id !== item.id);
                    delete this.leaving[item.id];
                    this.saved = data.saved;
                    this.trashed = data.trashed;
                }, 460);
                this.$store.ui.toast(data.toast);
            } catch {
                delete this.leaving[item.id];
                this.$store.ui.toast('Das hat nicht geklappt. Selbst der Kühlschrank ist verwirrt.');
            }
        },
    }));

    /* -------------------------------------------------------------- */
    /* Scan / Einlagern                                                */
    /* -------------------------------------------------------------- */
    Alpine.data('scanPage', (config) => ({
        phase: config.phase ?? 'idle', // idle | scanning | found
        scanner: null,
        form: {
            barcode: config.old.barcode ?? '',
            name: config.old.name ?? '',
            category: config.old.category ?? 'Sonstiges',
            quantity: Number(config.old.quantity ?? 1),
            shelf: Number(config.old.shelf_days ?? 7),
            fromScan: !!config.old.barcode,
        },
        notice: '',

        get dateLabel() {
            const date = new Date();
            date.setDate(date.getDate() + this.form.shelf);
            return date.toLocaleDateString('de-DE', { day: '2-digit', month: 'short' });
        },

        get remark() {
            if (this.form.shelf <= 2) return 'Das ist kein Einlagern, das ist Aufschieben.';
            if (this.form.shelf >= 14) return 'Optimistisch. Das gefällt mir.';
            return 'Sieben Tage. Ich setze mir schon mal eine Erinnerung.';
        },

        async startScan() {
            if (this.phase === 'scanning') return;
            this.notice = '';
            this.phase = 'scanning';

            try {
                const { Html5Qrcode, Html5QrcodeSupportedFormats: F } = await import('html5-qrcode');
                this.scanner = new Html5Qrcode('ff-reader', {
                    formatsToSupport: [F.EAN_13, F.EAN_8, F.UPC_A, F.UPC_E, F.CODE_128],
                    verbose: false,
                });
                await this.scanner.start(
                    { facingMode: 'environment' },
                    { fps: 10, qrbox: { width: 260, height: 140 } },
                    (code) => this.onDecoded(code),
                    () => {},
                );
            } catch {
                await this.stopScanner();
                this.phase = 'idle';
                this.notice = 'Keine Kamera verfügbar (oder keine Erlaubnis). Dann eben manuell.';
            }
        },

        async stopScanner() {
            if (!this.scanner) return;
            try {
                await this.scanner.stop();
                this.scanner.clear();
            } catch {
                // Scanner was not running.
            }
            this.scanner = null;
        },

        async onDecoded(code) {
            await this.stopScanner();
            vibrate(60);

            try {
                const { data } = await window.axios.post(config.lookupUrl, { barcode: code });
                this.fill({ barcode: data.barcode, name: data.name, category: data.category, fromScan: true });
            } catch (error) {
                this.fill({ barcode: /^\d{6,14}$/.test(code) ? code : '', name: '', category: 'Sonstiges', fromScan: true });
                this.notice = error.response?.data?.message ?? 'Open Food Facts schweigt. Trag es selbst ein.';
            }
        },

        fill({ barcode, name, category, fromScan }) {
            this.form = { barcode, name, category, quantity: 1, shelf: 7, fromScan };
            this.phase = 'found';
            // Wait until x-show has revealed the form before focusing.
            setTimeout(() => this.$refs.name?.focus(), 50);
        },

        manual() {
            this.notice = '';
            this.fill({ barcode: '', name: '', category: 'Sonstiges', fromScan: false });
        },

        async cancel() {
            await this.stopScanner();
            this.notice = '';
            this.phase = 'idle';
        },

        destroy() {
            this.stopScanner();
        },
    }));

    /* -------------------------------------------------------------- */
    /* Butler: test alert                                              */
    /* -------------------------------------------------------------- */
    Alpine.data('butlerTest', (url) => ({
        busy: false,

        async test() {
            if (this.busy) return;
            this.busy = true;
            try {
                const { data } = await window.axios.post(url);
                vibrate([100, 50, 100]);
                this.$store.ui.push(data.message, data.time);
            } catch {
                this.$store.ui.toast('Der Butler ist gerade beleidigt. Versuch es gleich nochmal.');
            } finally {
                this.busy = false;
            }
        },
    }));
});

window.Alpine = Alpine;
Alpine.start();

if ('serviceWorker' in navigator && import.meta.env.PROD) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
}
