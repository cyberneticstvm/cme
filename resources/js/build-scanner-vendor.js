import { readFileSync, writeFileSync, mkdirSync, copyFileSync } from 'node:fs';
import { resolve } from 'node:path';

const destination = resolve('public/vendor/zxing');
mkdirSync(destination, { recursive: true });
const bundle = readFileSync('node_modules/@zxing/browser/umd/zxing-browser.min.js', 'utf8');
writeFileSync(resolve(destination, 'zxing-browser.min.js'), bundle.replace(/\/\/# sourceMappingURL=.*$/m, ''), 'utf8');
copyFileSync('node_modules/@zxing/browser/LICENSE', resolve(destination, 'BROWSER-LICENSE'));
copyFileSync('node_modules/@zxing/library/LICENSE', resolve(destination, 'LIBRARY-LICENSE'));
console.log('Built the local ZXing scanner asset.');
