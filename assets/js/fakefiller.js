(function () {
    'use strict';

    var cfg = window.KITFakeFillerConfig || {};
    if (!cfg.enabled) {
        return;
    }

    var CACHE_KEY = '__kitFakeFillerData';

    function pick(list) {
        if (!Array.isArray(list) || !list.length) return null;
        return list[Math.floor(Math.random() * list.length)];
    }

    function randInt(min, max) {
        return Math.floor(Math.random() * (max - min + 1)) + min;
    }

    function trigger(el, type) {
        if (!el) return;
        el.dispatchEvent(new Event(type, { bubbles: true }));
    }

    function setValue(el, value) {
        if (!el || value === null || value === undefined) return;
        el.value = value;
        trigger(el, 'input');
        trigger(el, 'change');
        trigger(el, 'blur');
    }

    function pickSelectValue(selectEl) {
        if (!selectEl || !selectEl.options || !selectEl.options.length) return;
        var options = Array.prototype.slice.call(selectEl.options).filter(function (opt) {
            return opt.value && !opt.disabled;
        });
        if (!options.length) return;
        var selected = pick(options);
        setValue(selectEl, selected.value);
    }

    function normalizeKey(key) {
        return String(key || '').toLowerCase().replace(/[^a-z0-9]+/g, '');
    }

    function fieldCandidates(scope, key) {
        var normalized = normalizeKey(key);
        var candidates = [];
        if (!normalized) return candidates;
        var fields = scope.querySelectorAll('input, select, textarea');
        Array.prototype.forEach.call(fields, function (el) {
            var idKey = normalizeKey(el.id || '');
            var nameKey = normalizeKey(el.name || '');
            if (!idKey && !nameKey) return;
            if (idKey === normalized || nameKey === normalized || idKey.indexOf(normalized) > -1 || nameKey.indexOf(normalized) > -1) {
                candidates.push(el);
            }
        });
        return candidates;
    }

    function setByType(el, value) {
        if (!el || value === null || value === undefined) return;
        var tag = (el.tagName || '').toLowerCase();
        var type = (el.type || '').toLowerCase();

        if (tag === 'select') {
            if (Array.isArray(value) && value.length) {
                var chosen = pick(value);
                if (chosen !== null && chosen !== undefined) setValue(el, chosen);
                return;
            }
            setValue(el, value);
            return;
        }

        if (type === 'checkbox') {
            var desired = !!value;
            if (el.checked !== desired) {
                el.checked = desired;
                trigger(el, 'change');
            }
            return;
        }

        if (type === 'radio') {
            if (String(el.value) === String(value)) {
                el.checked = true;
                trigger(el, 'change');
            }
            return;
        }

        if (Array.isArray(value) && value.length) {
            setValue(el, pick(value));
            return;
        }

        setValue(el, value);
    }

    function applyProfileMap(scope, profile) {
        if (!profile || typeof profile !== 'object') return;
        Object.keys(profile).forEach(function (key) {
            var value = profile[key];
            var elements = fieldCandidates(scope, key);
            if (!elements.length) return;

            if (elements[0].type === 'radio' && !Array.isArray(value)) {
                Array.prototype.forEach.call(elements, function (el) {
                    if (String(el.value) === String(value)) {
                        el.checked = true;
                        trigger(el, 'change');
                    }
                });
                return;
            }

            Array.prototype.forEach.call(elements, function (el) {
                setByType(el, value);
            });
        });
    }

    function pickOptionByText(selectEl, keywords) {
        if (!selectEl || !selectEl.options || !selectEl.options.length) return false;
        var all = Array.prototype.slice.call(selectEl.options);
        var valid = all.filter(function (opt) { return opt.value && !opt.disabled; });
        if (!valid.length) return false;

        var list = Array.isArray(keywords) ? keywords : [keywords];
        var normalized = list.map(function (k) { return String(k || '').toLowerCase(); }).filter(Boolean);
        if (!normalized.length) return false;

        var match = valid.find(function (opt) {
            var txt = String(opt.textContent || opt.label || '').toLowerCase();
            return normalized.some(function (k) { return txt.indexOf(k) > -1; });
        });
        if (!match) return false;
        setValue(selectEl, match.value);
        return true;
    }

    function expandData(raw) {
        var customerFirst = raw.customer_first_names || [];
        var customerLast = raw.customer_last_names || [];
        var companyPrefix = raw.company_prefixes || [];
        var companySuffix = raw.company_suffixes || [];
        var streets = (raw.addresses && raw.addresses.streets) || [];
        var saAreas = (raw.addresses && raw.addresses.sa_suburbs) || [];
        var tzCities = (raw.addresses && raw.addresses.tz_cities) || [];
        var tzBusinessHubs = (raw.addresses && raw.addresses.tz_business_hubs) || [];
        var phonePrefixes = (raw.phone_prefixes && raw.phone_prefixes.za) || ['+27 82', '+27 73'];
        var productTemplates = raw.product_templates || [];
        var seedCustomers = Array.isArray(raw.customers) ? raw.customers : [];
        var seedProducts = Array.isArray(raw.products) ? raw.products : [];
        var waybillDescriptions = raw.waybill_descriptions || [];
        var stepProfiles = raw.step_profiles || {};

        var customers = [];
        var products = [];
        var customerTarget = parseInt(raw.customer_count_target || 100, 10);
        var productTarget = parseInt(raw.product_count_target || 300, 10);

        var i;
        for (i = 0; i < customerTarget; i++) {
            var first = pick(customerFirst) || ('Customer' + (i + 1));
            var last = pick(customerLast) || 'User';
            var company = (pick(companyPrefix) || 'Biz') + ' ' + (pick(companySuffix) || 'Traders');
            var area = pick(saAreas) || 'Johannesburg';
            var city = pick(tzCities) || 'Dar es Salaam';
            var street = pick(streets) || 'Main Road';
            var hub = pick(tzBusinessHubs) || ('City Trade Zone, ' + city);
            var phonePrefix = pick(phonePrefixes) || '+27 82';
            var emailSlug = (first + '.' + last + '.' + (i + 1)).toLowerCase().replace(/\s+/g, '');

            customers.push({
                customer_name: first,
                customer_surname: last,
                company_name: company,
                cell: phonePrefix + ' ' + randInt(100, 999) + ' ' + randInt(1000, 9999),
                telephone: phonePrefix + ' ' + randInt(100, 999) + ' ' + randInt(1000, 9999),
                email_address: emailSlug + '@example.co.za',
                address: 'Shop ' + randInt(1, 320) + ', Block ' + String.fromCharCode(65 + randInt(0, 6)) + ', '
                    + hub + ', ' + randInt(1000, 99999) + ', Tanzania',
                customer_notes: 'Fake filler profile #' + (i + 1)
            });
        }

        if (seedCustomers.length) {
            customers = seedCustomers.concat(customers);
        }

        for (i = 0; i < productTarget; i++) {
            var base = pick(productTemplates) || 'Wholesale goods';
            var qty = randInt(1, 20);
            products.push({
                item_name: base,
                quantity: qty,
                unit_price: randInt(150, 25000),
                client_invoice: 'INV-' + randInt(10000, 99999),
                misc_item: base + ' - handling/clearing',
                misc_price: randInt(50, 1500),
                misc_quantity: randInt(1, 5)
            });
        }

        if (seedProducts.length) {
            products = seedProducts.concat(products);
        }

        return {
            customers: customers,
            products: products,
            waybill_descriptions: waybillDescriptions,
            step_profiles: stepProfiles
        };
    }

    function loadData() {
        if (window[CACHE_KEY]) return Promise.resolve(window[CACHE_KEY]);
        return fetch(cfg.dataUrl, { credentials: 'same-origin' })
            .then(function (res) {
                if (!res.ok) throw new Error('Failed to load fakefiller.json');
                return res.json();
            })
            .then(function (json) {
                var data = expandData(json || {});
                window[CACHE_KEY] = data;
                return data;
            });
    }

    function fillCustomer(scope, data) {
        var existingCustomers = [];
        if (window.CUSTOMERS_DATA && typeof window.CUSTOMERS_DATA === 'object') {
            existingCustomers = Object.keys(window.CUSTOMERS_DATA).map(function (key) {
                return window.CUSTOMERS_DATA[key];
            }).filter(function (item) {
                return item && (item.customer_name || item.customer_surname || item.company_name);
            });
        }

        var fromExisting = existingCustomers.length > 0 && Math.random() < 0.8;
        var c = pick(fromExisting ? existingCustomers : data.customers);
        if (!c && existingCustomers.length) c = pick(existingCustomers);
        if (!c) return;

        var customerSelectHidden = document.getElementById('customer-select');
        var custIdInput = document.getElementById('cust_id');
        var customerSearch = document.getElementById('customer-search');

        if (fromExisting) {
            var selectedId = c.cust_id || c.id || '';
            if (customerSelectHidden && selectedId) setValue(customerSelectHidden, String(selectedId));
            if (custIdInput && selectedId) setValue(custIdInput, String(selectedId));
            if (customerSearch) {
                var fullName = ((c.customer_name || c.name || '') + ' ' + (c.customer_surname || c.surname || '')).trim();
                setValue(customerSearch, fullName || c.company_name || '');
            }
        } else {
            var addNewBtn = document.getElementById('add-new-customer-btn');
            if (addNewBtn) addNewBtn.click();
            if (customerSelectHidden) setValue(customerSelectHidden, 'new');
            if (custIdInput) setValue(custIdInput, '0');
        }

        var fieldMap = {
            customer_name: c.customer_name || c.name || '',
            customer_surname: c.customer_surname || c.surname || '',
            company_name: c.company_name || '',
            cell: c.cell || '',
            telephone: c.telephone || c.cell || '',
            email_address: c.email_address || c.email || ('tester' + randInt(100, 999) + '@example.co.za'),
            address: c.address || ('Shop ' + randInt(1, 220) + ', Kariakoo Trading Area, Ilala, Dar es Salaam, Tanzania'),
            customer_notes: c.customer_notes
        };

        Object.keys(fieldMap).forEach(function (name) {
            var el = scope.querySelector('[name="' + name + '"], #' + name);
            setValue(el, fieldMap[name]);
        });

        var radios = scope.querySelectorAll('input[name="client_type"]');
        if (radios.length) {
            var chosenRadio = pick(Array.prototype.slice.call(radios));
            if (chosenRadio) {
                chosenRadio.checked = true;
                trigger(chosenRadio, 'change');
            }
        }
    }

    function fillWaybillDescription(scope, data) {
        var descField = scope.querySelector('#waybill_description, [name="waybill_description"]');
        if (!descField || descField.readOnly || descField.disabled) return;
        var product = pick(data.products || []);
        var productName = (product && product.item_name) ? product.item_name : 'commercial goods';
        var descriptions = data.waybill_descriptions || [];
        var template = pick(descriptions) || 'Business shipment of {product} from South Africa to Tanzania.';
        var description = String(template).replace(/\{product\}/g, productName);
        setValue(descField, description);
    }

    function fillStep2(scope) {
        var pending = document.getElementById('pending_option');
        if (pending) {
            pending.checked = Math.random() < 0.15;
            trigger(pending, 'change');
        }

        var country = document.getElementById('stepDestinationSelect') || scope.querySelector('[name="destination_country"]');
        if (country) {
            if (!pickOptionByText(country, ['tanzania', 'tz'])) {
                pickSelectValue(country);
            }
        }

        var city = document.getElementById('destination_city') || scope.querySelector('[name="destination_city"]');
        if (city) {
            setTimeout(function () {
                if (!pickOptionByText(city, ['dar es salaam', 'arusha', 'mwanza', 'dodoma', 'mbeya', 'morogoro'])) {
                    pickSelectValue(city);
                }
            }, 250);
        }

        var cards = scope.querySelectorAll('.delivery-card');
        if (cards.length) {
            pick(Array.prototype.slice.call(cards)).click();
        }
    }

    function fillNumbers(scope) {
        var numeric = scope.querySelectorAll('input[type="number"], input[name*="mass"], input[name*="volume"], input[name*="length"], input[name*="width"], input[name*="height"]');
        Array.prototype.forEach.call(numeric, function (el) {
            if (el.disabled || el.readOnly) return;
            if (el.value && String(el.value).trim() !== '') return;
            var n = 0;
            var key = (el.name || el.id || '').toLowerCase();
            if (key.indexOf('mass') > -1) n = randInt(50, 1200);
            else if (key.indexOf('volume') > -1) n = randInt(1, 20);
            else if (key.indexOf('length') > -1) n = randInt(20, 200);
            else if (key.indexOf('width') > -1) n = randInt(20, 150);
            else if (key.indexOf('height') > -1) n = randInt(20, 160);
            else if (key.indexOf('price') > -1 || key.indexOf('charge') > -1) n = randInt(50, 25000);
            else if (key.indexOf('quantity') > -1 || key.indexOf('qty') > -1) n = randInt(1, 12);
            else n = randInt(1, 100);
            setValue(el, n);
        });
    }

    function fillTexts(scope) {
        var texts = scope.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], textarea');
        Array.prototype.forEach.call(texts, function (el) {
            if (el.disabled || el.readOnly) return;
            if (el.value && String(el.value).trim() !== '') return;
            var key = (el.name || el.id || '').toLowerCase();
            if (key.indexOf('email') > -1) setValue(el, 'tester' + randInt(100, 999) + '@example.co.za');
            else if (key.indexOf('cell') > -1 || key.indexOf('phone') > -1 || key.indexOf('tel') > -1) setValue(el, '+27 82 ' + randInt(100, 999) + ' ' + randInt(1000, 9999));
            else if (key.indexOf('address') > -1) setValue(el, 'Shop ' + randInt(1, 220) + ', Kariakoo Trading Area, Ilala, Dar es Salaam, Tanzania');
            else if (key.indexOf('description') > -1) setValue(el, 'Debug shipment - auto filled by fake filler');
            else if (key.indexOf('invoice') > -1) setValue(el, 'INV-' + randInt(10000, 99999));
            else setValue(el, 'Test ' + randInt(100, 999));
        });
    }

    function fillSelects(scope) {
        var selects = scope.querySelectorAll('select');
        Array.prototype.forEach.call(selects, function (selectEl) {
            if (selectEl.disabled) return;
            if (selectEl.value && selectEl.value !== '') return;
            pickSelectValue(selectEl);
        });
    }

    function fillChecksAndRadios(scope) {
        var radioNames = {};
        var radios = scope.querySelectorAll('input[type="radio"]');
        Array.prototype.forEach.call(radios, function (r) {
            if (!r.name || r.disabled) return;
            if (!radioNames[r.name]) radioNames[r.name] = [];
            radioNames[r.name].push(r);
        });
        Object.keys(radioNames).forEach(function (name) {
            var options = radioNames[name];
            var already = options.some(function (r) { return r.checked; });
            if (!already) {
                var chosen = pick(options);
                if (chosen) {
                    chosen.checked = true;
                    trigger(chosen, 'change');
                }
            }
        });

        var checkboxGroups = {};
        var checks = scope.querySelectorAll('input[type="checkbox"]');
        Array.prototype.forEach.call(checks, function (c) {
            if (c.disabled) return;
            var key = c.name || c.id || ('__single_' + Math.random());
            if (!checkboxGroups[key]) checkboxGroups[key] = [];
            checkboxGroups[key].push(c);
        });
        Object.keys(checkboxGroups).forEach(function (name) {
            var group = checkboxGroups[name];
            if (!group.length) return;
            var alreadyChecked = group.some(function (c) { return c.checked; });
            if (alreadyChecked) return;

            if (group.length === 1) {
                if (Math.random() < 0.45) {
                    group[0].checked = true;
                    trigger(group[0], 'change');
                }
                return;
            }

            var targetCount = randInt(1, Math.min(2, group.length));
            var shuffled = group.slice().sort(function () { return Math.random() - 0.5; });
            shuffled.slice(0, targetCount).forEach(function (c) {
                c.checked = true;
                trigger(c, 'change');
            });
        });
    }

    function setToggleState(toggleEl, desired) {
        if (!toggleEl || toggleEl.disabled) return;
        var current = !!toggleEl.checked;
        if (current !== !!desired) {
            toggleEl.click();
        }
    }

    function setChargeButtonState(buttonEl, hiddenInputEl, desired) {
        if (!buttonEl || !hiddenInputEl || buttonEl.disabled) return;
        var current = String(hiddenInputEl.value || '0') === '1';
        if (current !== !!desired) {
            buttonEl.click();
        }
    }

    function fillChargeOptions(scope) {
        var combos = [
            { vat: true, sadc: false, sad500: false },
            { vat: false, sadc: true, sad500: false },
            { vat: false, sadc: false, sad500: true },
            { vat: true, sadc: false, sad500: true },
            { vat: false, sadc: true, sad500: true }
        ];
        var desired = pick(combos) || { vat: false, sadc: true, sad500: false };

        var vatBtn = scope.querySelector('#vat_include');
        var sadcBtn = scope.querySelector('#sadc_certificate');
        var sad500Btn = scope.querySelector('#include_sad500');

        var vatInput = scope.querySelector('#vat_include_input');
        var sadcInput = scope.querySelector('#include_sadc_input');
        var sad500Input = scope.querySelector('#include_sad500_input');

        if (vatInput && sadcInput && sad500Input && (vatBtn || sadcBtn || sad500Btn)) {
            setChargeButtonState(vatBtn, vatInput, false);
            setChargeButtonState(sadcBtn, sadcInput, false);
            setChargeButtonState(sad500Btn, sad500Input, false);

            if (desired.sad500) setChargeButtonState(sad500Btn, sad500Input, true);
            if (desired.vat) setChargeButtonState(vatBtn, vatInput, true);
            if (desired.sadc) setChargeButtonState(sadcBtn, sadcInput, true);
            return;
        }

        var vatCb = scope.querySelector('input[type="checkbox"]#vat_include, input[type="checkbox"][name="vat_include"]');
        var sadcCb = scope.querySelector('input[type="checkbox"]#sadc_certificate, input[type="checkbox"][name="include_sadc"]');
        var sad500Cb = scope.querySelector('input[type="checkbox"]#include_sad500, input[type="checkbox"][name="include_sad500"]');

        setToggleState(vatCb, false);
        setToggleState(sadcCb, false);
        setToggleState(sad500Cb, false);

        if (desired.sad500) setToggleState(sad500Cb, true);
        if (desired.vat) setToggleState(vatCb, true);
        if (desired.sadc) setToggleState(sadcCb, true);
    }

    function fillDynamicItems(scope, data) {
        var addButtons = scope.querySelectorAll('[id^="add-waybill-item"], [id^="add-misc-item"]');
        Array.prototype.forEach.call(addButtons, function (btn) {
            var times = randInt(1, 2);
            for (var i = 0; i < times; i++) btn.click();
        });

        var rows = scope.querySelectorAll('.dynamic-item');
        Array.prototype.forEach.call(rows, function (row) {
            var product = pick(data.products);
            if (!product) return;

            var map = {
                item_name: product.item_name,
                quantity: product.quantity,
                unit_price: product.unit_price,
                client_invoice: product.client_invoice,
                misc_item: product.misc_item,
                misc_price: product.misc_price,
                misc_quantity: product.misc_quantity
            };

            Object.keys(map).forEach(function (key) {
                var field = row.querySelector('[name*="' + key + '"]');
                if (!field) return;
                var currentRaw = String(field.value || '').trim();
                var currentNum = parseFloat(currentRaw);
                var isZeroLike = currentRaw === '0' || currentRaw === '0.0' || currentRaw === '0.00' || (!isNaN(currentNum) && currentNum <= 0);
                var isEmpty = currentRaw === '';

                // Always inject pricing when default is zero so each product has value.
                if (key === 'unit_price' || key === 'misc_price') {
                    if (isEmpty || isZeroLike) {
                        setValue(field, map[key]);
                    }
                    return;
                }

                // Keep quantity sensible if unset/invalid.
                if (key === 'quantity' || key === 'misc_quantity') {
                    if (isEmpty || isZeroLike) {
                        setValue(field, map[key]);
                    }
                    return;
                }

                // For descriptions/invoices, only fill if blank.
                if (isEmpty) {
                    setValue(field, map[key]);
                }
            });
        });
    }

    function activeScope() {
        return document.querySelector('.step.active') || document.getElementById('multi-step-waybill-form') || document;
    }

    function fillOne(scope, data) {
        var activeStepEl = document.querySelector('.step.active');
        var activeStepId = activeStepEl ? activeStepEl.id : '';
        var isWholeFormScope = !!(scope && scope.id === 'multi-step-waybill-form');
        var shouldFillStep2 = isWholeFormScope || activeStepId === 'step-2';

        var profiles = (data && data.step_profiles) || {};
        var specificProfile = profiles[activeStepId] || null;
        var defaultProfile = profiles.default || null;
        applyProfileMap(scope, defaultProfile);
        applyProfileMap(scope, specificProfile);

        fillCustomer(scope, data);
        fillWaybillDescription(scope, data);
        fillChargeOptions(scope);
        if (shouldFillStep2) {
            fillStep2(scope);
        }
        fillSelects(scope);
        fillTexts(scope);
        fillNumbers(scope);
        fillChecksAndRadios(scope);
        fillDynamicItems(scope, data);
    }

    function ensureButton() {
        if (document.getElementById('kit-fake-filler-btn')) return;
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.id = 'kit-fake-filler-btn';
        btn.textContent = 'Fake Fill';
        btn.style.position = 'fixed';
        btn.style.top = '14px';
        btn.style.right = '14px';
        btn.style.zIndex = '2147483647';
        btn.style.background = 'linear-gradient(135deg, #4f46e5, #7c3aed)';
        btn.style.color = '#fff';
        btn.style.border = 'none';
        btn.style.borderRadius = '999px';
        btn.style.padding = '10px 16px';
        btn.style.fontSize = '12px';
        btn.style.fontWeight = '700';
        btn.style.boxShadow = '0 8px 22px rgba(79,70,229,0.35)';
        btn.style.cursor = 'pointer';
        btn.title = 'Click: fill active step. Shift+Click: fill all steps.';

        btn.addEventListener('click', function (ev) {
            btn.disabled = true;
            btn.textContent = 'Filling...';
            loadData()
                .then(function (data) {
                    if (ev.shiftKey) {
                        fillOne(document.getElementById('multi-step-waybill-form') || document, data);
                    } else {
                        fillOne(activeScope(), data);
                    }
                })
                .catch(function (err) {
                    console.error('Fake filler failed:', err);
                })
                .finally(function () {
                    btn.disabled = false;
                    btn.textContent = 'Fake Fill';
                });
        });

        document.body.appendChild(btn);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ensureButton);
    } else {
        ensureButton();
    }
})();
