import './bootstrap';
import '@fontsource-variable/archivo';
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

/** Local date in N days as YYYY-MM-DD, the value format of <input type="date">. */
const isoDate = (days) => {
    const date = new Date();
    date.setDate(date.getDate() + days);
    const pad = (n) => String(n).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
};

/* ------------------------------------------------------------------ */
/* Barcode reader                                                      */
/* ------------------------------------------------------------------ */

/**
 * Safari (iPhone, iPad) and desktop Firefox have no native BarcodeDetector.
 * html5-qrcode would then fall back to its JavaScript decoder, which rarely
 * reads an EAN code from a phone camera. zxing-wasm fills the gap, and
 * html5-qrcode picks it up like the native detector. If the WebAssembly file
 * does not load, the JavaScript decoder stays as the fallback.
 */
let barcodeDetectorReady = null;

const ensureBarcodeDetector = () => {
    barcodeDetectorReady ??= (async () => {
        if ('BarcodeDetector' in window) return;
        const { BarcodeDetector, prepareZXingModule } = await import('barcode-detector/ponyfill');
        // Production serves the WebAssembly file from /build (see vite.config.js); dev uses the CDN default.
        const overrides = __ZXING_BASE__
            ? { locateFile: (path, prefix) => (path.endsWith('.wasm') ? `${__ZXING_BASE__}/${path}` : prefix + path) }
            : {};
        await prepareZXingModule({ overrides, fireImmediately: true });
        window.BarcodeDetector = BarcodeDetector;
    })().catch(() => {
        barcodeDetectorReady = null;
    });

    return barcodeDetectorReady;
};

/* ------------------------------------------------------------------ */
/* Date scanner (OCR)                                                  */
/* ------------------------------------------------------------------ */

/** One shared Tesseract worker, created on first use (the model is a few MB). */
let ocrWorker = null;

const getOcrWorker = () => {
    ocrWorker ??= (async () => {
        const { createWorker, OEM, PSM } = await import('tesseract.js');
        // Production serves the engine from /build (see vite.config.js); dev uses the CDN default.
        const paths = __TESSERACT_BASE__
            ? { workerPath: `${__TESSERACT_BASE__}/worker.min.js`, corePath: __TESSERACT_BASE__, langPath: __TESSERACT_BASE__ }
            : {};
        const worker = await createWorker('eng', OEM.LSTM_ONLY, paths);
        await worker.setParameters({
            // Digits and separators only; the space keeps a date apart from a lot number next to it.
            tessedit_char_whitelist: '0123456789./- ',
            preserve_interword_spaces: '1',
            tessedit_pageseg_mode: PSM.SINGLE_BLOCK,
        });
        return worker;
    })().catch((error) => {
        ocrWorker = null;
        throw error;
    });

    return ocrWorker;
};

/**
 * Copies the part of the video under the on-screen frame into a canvas,
 * enlarged and in high contrast. Every other frame is slightly blurred,
 * which joins the dots of dot-matrix printed dates.
 */
const cropForOcr = (video, frame, blur) => {
    const v = video.getBoundingClientRect();
    const f = frame.getBoundingClientRect();
    // The video uses object-fit: cover, so map screen pixels back to video pixels.
    const scale = Math.max(v.width / video.videoWidth, v.height / video.videoHeight);
    const offsetX = (v.width - video.videoWidth * scale) / 2;
    const offsetY = (v.height - video.videoHeight * scale) / 2;
    const sx = (f.left - v.left - offsetX) / scale;
    const sy = (f.top - v.top - offsetY) / scale;
    const sw = f.width / scale;
    const sh = f.height / scale;
    const zoom = Math.min(2, 1400 / sw);

    const canvas = document.createElement('canvas');
    canvas.width = Math.round(sw * zoom);
    canvas.height = Math.round(sh * zoom);
    const ctx = canvas.getContext('2d');
    ctx.filter = `grayscale(1) contrast(1.8)${blur ? ' blur(1px)' : ''}`;
    ctx.drawImage(video, sx, sy, sw, sh, 0, 0, canvas.width, canvas.height);

    return canvas;
};

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

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
        today: isoDate(0),

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
            // "1 Artikel läuft", "2 Artikel laufen": Artikel stays the same, only the verb changes.
            const count = (n, one, many) => `${n} Artikel ${n === 1 ? one : many}`;
            const expired = this.items.filter((i) => i.days <= 0).length;
            const variants = {
                1: { word: 'Entspannt', mood: 'happy', line: 'Nichts läuft ab. Ich traue dem Frieden nicht.' },
                2: { word: 'Genervt', mood: 'meh', line: `${count(this.crit.length, 'läuft', 'laufen')} in 2–3 Tagen ab. Ich zähle mit.` },
                3: { word: 'Sauer', mood: 'angry', line: `${count(expired, 'läuft heute ab oder ist', 'laufen heute ab oder sind')} schon hinüber.` },
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

            // Only the last piece animates the card away; 'busy' just blocks double taps.
            this.leaving[item.id] = item.quantity > 1 ? 'busy' : kind;

            try {
                const { data } = await window.axios.patch(kind === 'eat' ? item.consume_url : item.trash_url);
                if (data.remaining > 0) {
                    // Only one piece booked: the card stays with the new quantity.
                    delete this.leaving[item.id];
                    Object.assign(item, { quantity: data.remaining, meta: data.meta });
                    this.saved = data.saved;
                    this.trashed = data.trashed;
                    this.$store.ui.toast(data.toast);
                    return;
                }
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
            expires: config.old.expires_at ?? isoDate(7),
            fromScan: !!config.old.barcode,
            source: null, // memory | openfoodfacts | null
            expiresSource: config.old.expires_at ? 'manual' : 'default', // default | memory | code | ocr | manual
            rememberedDays: null,
        },
        notice: '',
        dateScan: { active: false, status: '' },
        dateStream: null,

        today: isoDate(0),

        get hitLabel() {
            return this.form.source === 'memory' ? 'Treffer · Schon mal gekauft' : 'Treffer · Open Food Facts';
        },

        get expiryHint() {
            switch (this.form.expiresSource) {
                case 'code':
                    return 'Datum aus dem Code gelesen. Prüf es trotzdem kurz.';
                case 'ocr':
                    return 'Datum von der Packung gelesen. Prüf es lieber nochmal.';
                case 'memory':
                    return `Letztes Mal hielt es ${this.form.rememberedDays} ${this.form.rememberedDays === 1 ? 'Tag' : 'Tage'}. Ich hab's mir gemerkt.`;
                default:
                    return 'Das Datum steht nicht im Barcode. Scann es von der Packung oder tipp es ein.';
            }
        },

        get remark() {
            const days = Math.round((new Date(this.form.expires) - new Date(this.today)) / 86400000);
            if (Number.isNaN(days)) return 'Ohne Datum kann ich niemanden beschuldigen.';
            if (days <= 2) return 'Das ist kein Einlagern, das ist Aufschieben.';
            if (days >= 14) return 'Optimistisch. Das gefällt mir.';
            return `${days} Tage. Ich setze mir schon mal eine Erinnerung.`;
        },

        async startScan() {
            if (this.phase === 'scanning') return;
            this.notice = '';
            this.phase = 'scanning';

            try {
                const [{ Html5Qrcode, Html5QrcodeSupportedFormats: F }] = await Promise.all([import('html5-qrcode'), ensureBarcodeDetector()]);
                this.scanner = new Html5Qrcode('ff-reader', {
                    // QR and DataMatrix: newer GS1 codes that can carry the expiry date.
                    formatsToSupport: [F.EAN_13, F.EAN_8, F.UPC_A, F.UPC_E, F.CODE_128, F.QR_CODE, F.DATA_MATRIX],
                    useBarCodeDetectorIfSupported: true,
                    verbose: false,
                });
                await this.scanner.start(
                    { facingMode: 'environment' },
                    // No resolution constraints: on phones with several rear cameras they can make the
                    // browser pick a lens that cannot focus up close, and html5-qrcode scales every
                    // frame down to the qrbox anyway.
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
                this.fill({ ...data, fromScan: true });
            } catch (error) {
                const data = error.response?.data ?? {};
                // 404 still carries the cleaned-up barcode and any date from a GS1 code.
                this.fill({
                    barcode: data.barcode ?? (/^\d{6,14}$/.test(code) ? code : ''),
                    expires_at: data.expires_at,
                    fromScan: true,
                });
                this.notice = data.message ?? data.errors?.barcode?.[0] ?? 'Open Food Facts schweigt. Trag es selbst ein.';
            }
        },

        fill({ barcode, name = '', category = 'Sonstiges', fromScan, source = null, expires_at = null, remembered_days = null }) {
            // Priority: date printed in the code > what this household entered last time > one week.
            const expiresSource = expires_at ? 'code' : remembered_days ? 'memory' : 'default';
            const expires = expires_at ?? isoDate(remembered_days ?? 7);

            this.form = { barcode, name, category, quantity: 1, expires, fromScan, source, expiresSource, rememberedDays: remembered_days };
            this.phase = 'found';
            // Wait until x-show has revealed the form before focusing.
            setTimeout(() => this.$refs.name?.focus(), 50);
        },

        async startDateScan() {
            if (this.dateScan.active) return;
            this.notice = '';
            this.dateScan = { active: true, status: 'Kamera startet …' };

            try {
                this.dateStream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'environment', width: { ideal: 1920 }, height: { ideal: 1080 } },
                    audio: false,
                });
                const video = this.$refs.dateVideo;
                video.srcObject = this.dateStream;
                await video.play();
            } catch {
                this.stopDateScan();
                this.notice = 'Keine Kamera verfügbar (oder keine Erlaubnis). Dann tipp das Datum ein.';
                return;
            }

            let worker;
            try {
                this.dateScan.status = 'Lade Texterkennung …';
                worker = await getOcrWorker();
            } catch {
                this.stopDateScan();
                this.notice = 'Die Texterkennung lädt nicht. Tipp das Datum lieber ein.';
                return;
            }

            const started = Date.now();
            let round = 0;
            this.dateScan.status = 'Halte das Datum in den Rahmen …';

            while (this.dateScan.active) {
                const video = this.$refs.dateVideo;
                if (video?.readyState >= 2) {
                    let text = '';
                    try {
                        ({ data: { text } } = await worker.recognize(cropForOcr(video, this.$refs.dateFrame, round++ % 2 === 1)));
                    } catch {
                        // A single unreadable frame is no reason to give up.
                    }
                    if (!this.dateScan.active) break;

                    if ((text.match(/\d/g) ?? []).length >= 4 && (await this.applyOcrText(text))) break;
                }

                if (Date.now() - started > 20000) {
                    this.dateScan.status = 'Schwer zu lesen. Mehr Licht, etwas näher ran – oder tipp es ein.';
                }
                await sleep(150);
            }
        },

        async applyOcrText(text) {
            try {
                const { data } = await window.axios.post(config.dateUrl, { text });
                if (!data.found) return false;

                this.form.expires = data.expires_at;
                this.form.expiresSource = 'ocr';
                vibrate(60);
                this.stopDateScan();
                this.$store.ui.toast('Datum erkannt. Ich hab ein Auge drauf.');
                return true;
            } catch {
                return false;
            }
        },

        stopDateScan() {
            this.dateScan = { active: false, status: '' };
            this.dateStream?.getTracks().forEach((track) => track.stop());
            this.dateStream = null;
        },

        manual() {
            this.notice = '';
            this.fill({ barcode: '', name: '', category: 'Sonstiges', fromScan: false });
        },

        async cancel() {
            await this.stopScanner();
            this.stopDateScan();
            this.notice = '';
            this.phase = 'idle';
        },

        destroy() {
            this.stopScanner();
            this.stopDateScan();
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
