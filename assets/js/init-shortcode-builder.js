(function (global) {
    function initShortcodeBuilder({ shortcode, config }) {
        const i18n = global.InitShortcodeBuilder?.i18n || {};
        const t = (key, fallback) => i18n[key] || fallback;

        let modal = document.getElementById('init-shortcode-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'init-shortcode-modal';
            modal.style = `
                position:fixed;top:0;left:0;width:100%;height:100%;
                background:rgba(0,0,0,0.5);z-index:10000;
                display:flex;align-items:center;justify-content:center;
            `;
            modal.innerHTML = `
                <div id="init-shortcode-content" style="background:#fff;padding:20px;border-radius:4px;max-width:600px;width:100%;position:relative;">
                    <button id="init-shortcode-close-top" style="position:absolute;top:10px;right:10px;border:none;background:none;font-size:20px;cursor:pointer;"><svg width="20" height="20" viewBox="0 0 24 24"><path d="m21 21-9-9m0 0L3 3m9 9 9-9m-9 9-9 9" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                </div>
            `;
            document.body.appendChild(modal);
        }

        const closeModal = () => modal.remove();

        modal.addEventListener('click', e => {
            if (e.target === modal) closeModal();
        });

        const root = modal.querySelector('#init-shortcode-content');
        let html = '';
        html += `<h2 style="margin-top:0;">${config.label}</h2>`;
        html += '<table class="form-table"><tbody>';

        const state = {};
        for (const [key, attr] of Object.entries(config.attributes)) {
            state[key] = attr.default || '';
            html += '<tr><th><label>' + attr.label + '</label></th><td>';

            if (attr.type === 'select') {
                html += `<select data-key="${key}" class="regular-text">`;
                attr.options.forEach(opt => {
                    const selected = opt === attr.default ? 'selected' : '';
                    html += `<option value="${opt}" ${selected}>${opt}</option>`;
                });
                html += '</select>';
            } else if (attr.type === 'checkbox') {
                const checked = attr.default ? 'checked' : '';
                html += `<label><input type="checkbox" data-key="${key}" ${checked}> ${attr.label}</label>`;
            } else {
                html += `<input type="${attr.type}" value="${attr.default || ''}" data-key="${key}" class="regular-text">`;
            }

            html += '</td></tr>';
        }

        html += '</tbody></table>';
        html += `<p><label><strong>${t('shortcode_preview', 'Shortcode Preview')}:</strong></label><br>`;
        html += '<textarea id="shortcode-preview" readonly class="widefat" rows="3" style="margin-top:4px;"></textarea></p>';
        html += `<p><button id="copy-shortcode" class="button button-primary">${t('copy', 'Copy')}</button>`;
        html += ` <button id="close-shortcode" class="button">${t('close', 'Close')}</button></p>`;

        root.innerHTML = root.innerHTML.replace('</button>', '</button>' + html);

        const updatePreview = () => {
            const parts = [shortcode];
            for (const [key, val] of Object.entries(state)) {
                if (val === true) {
                    parts.push(`${key}="true"`);
                } else if (val !== '' && val !== false) {
                    parts.push(`${key}="${val}"`);
                }
            }
            document.getElementById('shortcode-preview').value = '[' + parts.join(' ') + ']';
        };

        const attachEvents = () => {
            root.querySelectorAll('[data-key]').forEach(input => {
                input.addEventListener('input', e => {
                    const key = e.target.getAttribute('data-key');
                    if (e.target.type === 'checkbox') {
                        state[key] = e.target.checked;
                    } else {
                        state[key] = e.target.value;
                    }
                    updatePreview();
                });
            });

            document.getElementById('copy-shortcode').addEventListener('click', () => {
                const textarea = document.getElementById('shortcode-preview');
                navigator.clipboard.writeText(textarea.value).then(() => {
                    const btn = document.getElementById('copy-shortcode');
                    const original = t('copy', 'Copy');
                    btn.textContent = t('copied', 'Copied!');
                    setTimeout(() => btn.textContent = original, 2000);
                });
            });

            document.getElementById('close-shortcode').addEventListener('click', closeModal);
            document.getElementById('init-shortcode-close-top').addEventListener('click', closeModal);
        };

        updatePreview();
        attachEvents();
    }

    function renderShortcodeBuilderButton({ label, dashicon, onClick, className = '' }) {
        const btn = document.createElement('button');
        btn.className = `button ${className}`;
        btn.style.margin = '10px 0';
        btn.style.display = 'inline-flex';
        btn.style.alignItems = 'center';
        btn.style.gap = '6px';
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

    global.initShortcodeBuilder = initShortcodeBuilder;
    global.renderShortcodeBuilderButton = renderShortcodeBuilderButton;
})(window);
