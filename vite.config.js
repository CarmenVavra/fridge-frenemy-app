import { readFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import { pathToFileURL } from 'node:url';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

const require = createRequire(import.meta.url);
const tesseractVersion = require('tesseract.js/package.json').version;
const tesseractDir = `tesseract/${tesseractVersion}`;
const zxingWasm = require.resolve('zxing-wasm/reader/zxing_reader.wasm');
// zxing-wasm does not export its package.json; it sits two levels above dist/reader.
const zxingVersion = JSON.parse(readFileSync(new URL('../../package.json', pathToFileURL(zxingWasm)))).version;
const zxingDir = `zxing/${zxingVersion}`;

/**
 * Self-hosts the OCR engine for the date scanner in production builds:
 * worker, WebAssembly core (with and without SIMD) and the English model,
 * which is enough for digits. Nothing is loaded from a CDN, and the service
 * worker caches the files like any other build asset. The versioned folder
 * makes sure an update is not shadowed by the cache.
 * In `npm run dev` Tesseract.js falls back to its default CDN.
 */
function tesseractAssets() {
    const files = {
        'worker.min.js': 'tesseract.js/dist/worker.min.js',
        'tesseract-core-lstm.wasm.js': 'tesseract.js-core/tesseract-core-lstm.wasm.js',
        'tesseract-core-simd-lstm.wasm.js': 'tesseract.js-core/tesseract-core-simd-lstm.wasm.js',
        'eng.traineddata.gz': '@tesseract.js-data/eng/4.0.0_best_int/eng.traineddata.gz',
    };

    return {
        name: 'tesseract-assets',
        apply: 'build',
        generateBundle() {
            for (const [name, source] of Object.entries(files)) {
                this.emitFile({ type: 'asset', fileName: `${tesseractDir}/${name}`, source: readFileSync(require.resolve(source)) });
            }
        },
    };
}

/**
 * Self-hosts the WebAssembly barcode reader for browsers without a native
 * BarcodeDetector (Safari on iPhone and iPad). Same idea as above: no CDN,
 * versioned folder. In `npm run dev` zxing-wasm falls back to jsDelivr.
 */
function zxingAssets() {
    return {
        name: 'zxing-assets',
        apply: 'build',
        generateBundle() {
            this.emitFile({
                type: 'asset',
                fileName: `${zxingDir}/zxing_reader.wasm`,
                source: readFileSync(zxingWasm),
            });
        },
    };
}

export default defineConfig(({ command }) => ({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tesseractAssets(),
        zxingAssets(),
    ],
    define: {
        __TESSERACT_BASE__: JSON.stringify(command === 'build' ? `/build/${tesseractDir}` : null),
        __ZXING_BASE__: JSON.stringify(command === 'build' ? `/build/${zxingDir}` : null),
    },
    server: {
        host: 'localhost',
        port: 5173,
        strictPort: true,
    },
}));
