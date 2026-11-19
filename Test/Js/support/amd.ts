import { readFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

/**
 * Зарежда РЕАЛЕН Magento AMD модул (`define([...], factory)`) от диска и връща
 * това, което factory-то експортва, с подадени мокнати зависимости.
 *
 * `modulePath` е спрямо КОРЕНА на репото на модула (Test/Js/support/../../..).
 * Модулът не се преписва: спекът трябва да чупи, когато shipped кодът се промени.
 * (Същият помощник като в Webcode_ElasticsuiteAjax.)
 */
export function loadAmdModule<T>(modulePath: string, deps: Record<string, unknown>): T {
    const repoRoot = resolve(dirname(fileURLToPath(import.meta.url)), '../../..');
    const source = readFileSync(resolve(repoRoot, modulePath), 'utf8');

    let exported: unknown;
    let defined = false;

    const define = (names: string[], factory: (...args: unknown[]) => unknown): void => {
        const args = names.map((name) => {
            if (!(name in deps)) {
                throw new Error(`AMD зависимост без мок: "${name}". Добави я в deps.`);
            }
            return deps[name];
        });
        exported = factory(...args);
        defined = true;
    };

    new Function('define', source)(define);

    if (!defined) {
        throw new Error(`${modulePath} не извика define() — не е AMD модул?`);
    }
    return exported as T;
}
