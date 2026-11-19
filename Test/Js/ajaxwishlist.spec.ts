/*
 * @package      Webcode_Wishlist
 *
 * @copyright    Copyright © 2026 Webcode Ltd. (https://webcode.bg/)
 * @license      https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 *
 * The add-to-wishlist button may say "Added to Wish List" only when the product was
 * added. A guest is refused with JSON {"success": false, "error": "not_logged_in"}:
 * the button gets its own text back and the guest goes to the login page.
 */
import { afterEach, beforeEach, describe, expect, it } from 'vitest';
import { loadAmdModule } from './support/amd';

type Stub = {
    __stub: true;
    text: string;
    title: string | undefined;
    classes: Set<string>;
    store: Record<string, unknown>;
    replaced: unknown;
};

function button(text: string, title: string): Stub {
    return { __stub: true, text, title, classes: new Set(), store: {}, replaced: undefined };
}

/** A jQuery stand-in for exactly what ajaxwishlist.js touches. */
function fakeJquery(btn: Stub, block: Stub) {
    let proto: Record<string, any> = {};

    const wrap = (el: Stub) => ({
        find: (selector: string) => (selector === 'span' ? spanOf(el) : wrap(btn)),
        attr: (name: string, value?: string) => {
            if (value === undefined) {
                return el.title;
            }
            el.title = value;
        },
        addClass: (cls: string) => el.classes.add(cls),
        data: (key: string, value?: unknown) => {
            if (value === undefined) {
                return el.store[key];
            }
            el.store[key] = value;
        },
        replaceWith: (html: unknown) => {
            el.replaced = html;
        },
    });
    const spanOf = (el: Stub) => ({
        text: (value?: string) => {
            if (value === undefined) {
                return el.text;
            }
            el.text = value;
        },
    });

    const $: any = (target: unknown) => {
        if (target === '#wishlist-view-form') {
            return wrap(block);
        }
        return wrap(btn);
    };
    $.widget = (_name: string, p: Record<string, any>) => {
        proto = p;
        $.webcode = { wishlist: p };
    };
    $.proxy = (fn: unknown) => fn;

    return { $, widget: () => Object.assign(Object.create(proto), { options: { ...proto.options } }) };
}

describe('ajaxwishlist.js', () => {
    let btn: Stub;
    let block: Stub;
    let widget: Record<string, any>;
    const form = { __form: true };

    beforeEach(() => {
        (globalThis as any).window = { location: { href: 'https://shop.test/product.html' } };
        btn = button('Add to Wish List', 'Add to Wish List');
        block = button('', '');
        const fake = fakeJquery(btn, block);
        loadAmdModule('view/frontend/web/js/ajaxwishlist.js', {
            jquery: fake.$,
            'mage/translate': (s: string) => s,
            'mage/url': { build: (path: string) => 'https://shop.test/' + path },
            'mage/loader': {},
        });
        widget = fake.widget();
        widget.disableAddToWishlistButton(form);
    });

    afterEach(() => {
        delete (globalThis as any).window;
    });

    it('shows "Adding..." while the request runs', () => {
        expect(btn.text).toBe('Adding...');
    });

    it('a guest does not see "Added": the text comes back and the guest goes to log in', () => {
        widget._successHandler({ success: false, error: 'not_logged_in' }, form);

        expect(btn.text).toBe('Add to Wish List');
        expect(btn.title).toBe('Add to Wish List');
        expect(btn.classes.has('added')).toBe(false);
        expect((globalThis as any).window.location.href).toBe('https://shop.test/customer/account/login');
    });

    it('another refusal restores the button and stays on the page', () => {
        widget._successHandler({ success: false, error: 'something' }, form);

        expect(btn.text).toBe('Add to Wish List');
        expect(btn.classes.has('added')).toBe(false);
        expect((globalThis as any).window.location.href).toBe('https://shop.test/product.html');
    });

    it('a logged-in add (the core answers with a redirect, so HTML) marks the button added', () => {
        widget._successHandler('<!doctype html><html></html>', form);

        expect(btn.text).toBe('Added to Wish List');
        expect(btn.classes.has('added')).toBe(true);
    });

    it('a JSON success refreshes the wishlist block', () => {
        widget._successHandler({ success: true, wishlist: '<form id="wishlist-view-form"></form>' }, form);

        expect(btn.classes.has('added')).toBe(true);
        expect(block.replaced).toBe('<form id="wishlist-view-form"></form>');
    });

    it('a failed request restores the button', () => {
        widget._errorHandler(form);

        expect(btn.text).toBe('Add to Wish List');
        expect(btn.classes.has('added')).toBe(false);
    });
});
