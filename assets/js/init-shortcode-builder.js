(function (global) {
    function initShortcodeBuilder({ shortcode, config }) {
        const i18n = global.InitShortcodeBuilder?.i18n || {};
        const t = (key, fallback) => i18n[key] || fallback;

        let modal = document.getElementById('init-shortcode-modal');
        if (modal) modal.remove(); // always recreate to reset state

        modal = document.createElement('div');
        modal.id = 'init-shortcode-modal';
        modal.style = `
            position:fixed;top:0;left:0;width:100%;height:100%;
            background:rgba(0,0,0,0.5);z-index:10000;
            display:flex;align-items:center;justify-content:center;
        `;

        const content = document.createElement('div');
        content.id = 'init-shortcode-content';
        content.style = `
            background:#fff;padding:20px;border-radius:4px;
            max-width:600px;width:100%;position:relative;
        `;

        const closeBtn = document.createElement('button');
        closeBtn.id = 'init-shortcode-close-top';
        closeBtn.innerHTML = `<svg width="20" height="20" viewBox="0 0 24 24"><path d="m21 21-9-9m0 0L3 3m9 9 9-9m-9 9-9 9" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
        closeBtn.style = `
            position:absolute;top:10px;right:10px;
            border:none;background:none;font-size:20px;cursor:pointer;
        `;
        content.appendChild(closeBtn);

        // Title
        const heading = document.createElement('h2');
        heading.textContent = config.label;
        heading.style.marginTop = '0';
        content.appendChild(heading);

        // Form table
        const table = document.createElement('table');
        table.className = 'form-table';
        const tbody = document.createElement('tbody');

        const state = {};

        for (const [key, attr] of Object.entries(config.attributes)) {
            state[key] = attr.default || '';

            const tr = document.createElement('tr');
            const th = document.createElement('th');
            const td = document.createElement('td');

            th.innerHTML = `<label>${attr.label}</label>`;

            if (attr.type === 'select') {
                const select = document.createElement('select');
                select.className = 'regular-text';
                select.setAttribute('data-key', key);
                attr.options.forEach(opt => {
                    const option = document.createElement('option');
                    option.value = opt;
                    option.textContent = opt;
                    if (opt === attr.default) option.selected = true;
                    select.appendChild(option);
                });
                td.appendChild(select);
            } else if (attr.type === 'checkbox') {
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.setAttribute('data-key', key);
                if (attr.default) checkbox.checked = true;
                td.appendChild(checkbox);
                td.append(' ' + attr.label);
            } else {
                const input = document.createElement('input');
                input.type = attr.type;
                input.className = 'regular-text';
                input.value = attr.default || '';
                input.setAttribute('data-key', key);
                td.appendChild(input);
            }

            tr.appendChild(th);
            tr.appendChild(td);
            tbody.appendChild(tr);
        }

        table.appendChild(tbody);
        content.appendChild(table);

        // Preview textarea
        const previewLabel = document.createElement('label');
        previewLabel.innerHTML = `<strong>${t('shortcode_preview', 'Shortcode Preview')}:</strong>`;
        const preview = document.createElement('textarea');
        preview.id = 'shortcode-preview';
        preview.className = 'widefat';
        preview.rows = 3;
        preview.readOnly = true;
        preview.style.marginTop = '4px';
        content.appendChild(previewLabel);
        content.appendChild(document.createElement('br'));
        content.appendChild(preview);

        // Buttons
        const actions = document.createElement('p');
        const copyBtn = document.createElement('button');
        copyBtn.id = 'copy-shortcode';
        copyBtn.className = 'button button-primary';
        copyBtn.textContent = t('copy', 'Copy');

        const closeBottomBtn = document.createElement('button');
        closeBottomBtn.id = 'close-shortcode';
        closeBottomBtn.className = 'button';
        closeBottomBtn.textContent = t('close', 'Close');

        actions.appendChild(copyBtn);
        actions.append(' ');
        actions.appendChild(closeBottomBtn);
        content.appendChild(actions);

        modal.appendChild(content);
        document.body.appendChild(modal);

        // Close modal logic
        const closeModal = () => modal.remove();
        setTimeout(() => {
            modal.addEventListener('click', e => {
                if (e.target === modal) closeModal();
            });
        }, 20);
        closeBtn.addEventListener('click', closeModal);
        closeBottomBtn.addEventListener('click', closeModal);

        // Live preview
        const updatePreview = () => {
            const parts = [shortcode];
            for (const [key, val] of Object.entries(state)) {
                if (val === true) {
                    parts.push(`${key}="true"`);
                } else if (val !== '' && val !== false) {
                    parts.push(`${key}="${val}"`);
                }
            }
            preview.value = `[${parts.join(' ')}]`;
        };

        // Field listeners
        content.querySelectorAll('[data-key]').forEach(el => {
            el.addEventListener('input', e => {
                const key = el.getAttribute('data-key');
                if (el.type === 'checkbox') {
                    state[key] = el.checked;
                } else {
                    state[key] = el.value;
                }
                updatePreview();
            });
        });

        // Copy to clipboard
        copyBtn.addEventListener('click', () => {
            navigator.clipboard.writeText(preview.value).then(() => {
                const original = t('copy', 'Copy');
                copyBtn.textContent = t('copied', 'Copied!');
                setTimeout(() => (copyBtn.textContent = original), 2000);
            });
        });

        updatePreview();
    }

    function renderShortcodeBuilderButton({ label, dashicon, onClick, className = '' }) {
        const btn = document.createElement('button');
        btn.className = `button ${className}`;
        btn.type = 'button';
        btn.style.display = 'inline-flex';
        btn.style.alignItems = 'center';
        btn.style.gap = '6px';
        btn.style.marginRight = '10px';
        btn.style.marginBottom = '10px';
        btn.addEventListener('click', onClick);

        if (dashicon) {
            const icon = document.createElement('span');
            icon.className = `dashicons dashicons-${dashicon}`;
            icon.style.lineHeight = '1';
            btn.appendChild(icon);
        }

        btn.appendChild(document.createTextNode(label || 'Build Shortcode'));
        return btn;
    }

    function renderShortcodeBuilderPanel({ title, buttons }) {
        const panel = document.createElement('div');
        panel.style.margin = '16px 0';
        panel.style.border = '1px solid #ccd0d4';
        panel.style.padding = '12px 16px';
        panel.style.borderRadius = '4px';
        panel.style.background = '#f9f9f9';

        const heading = document.createElement('h2');
        heading.textContent = title;
        heading.style.marginTop = '0';
        heading.style.fontSize = '16px';
        heading.style.marginBottom = '12px';
        panel.appendChild(heading);

        const btnGroup = document.createElement('div');
        btnGroup.style.display = 'flex';
        btnGroup.style.flexWrap = 'wrap';
        btnGroup.style.gap = '10px';

        buttons.forEach(btn => {
            btnGroup.appendChild(renderShortcodeBuilderButton(btn));
        });

        panel.appendChild(btnGroup);
        return panel;
    }

    global.initShortcodeBuilder = initShortcodeBuilder;
    global.renderShortcodeBuilderButton = renderShortcodeBuilderButton;
    global.renderShortcodeBuilderPanel = renderShortcodeBuilderPanel;
})(window);
