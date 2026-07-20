document.addEventListener('DOMContentLoaded', function () {
    // === Generate Keywords ===
    const btn = document.getElementById('generate_keywords_button');
    const input = document.getElementById('suggested_keywords_input');
    if (btn && input) {
        btn.addEventListener('click', function () {
            btn.disabled = true;
            fetch(init_plugin_suite_live_search_ajax.ajaxurl + '?action=init_plugin_suite_live_search_generate_keywords', {
                method: 'GET',
                headers: { 'X-WP-Nonce': init_plugin_suite_live_search_ajax.nonce }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        input.value = data.data || '';
                    } else {
                        alert((typeof data.data === 'string' && data.data) ? data.data : 'Failed to generate keywords');
                    }
                })
                .catch(error => { alert('Error: ' + error); })
                .finally(() => { btn.disabled = false; });
        });
    }

    // === Native Search Toggle ===
    const nativeCheckbox = document.querySelector('input[name="init_plugin_suite_live_search_settings[use_native_search]"]');
    const lockedRows = document.querySelectorAll('tr[data-native-locked="1"]');

    function toggleNativeLock(locked) {
        lockedRows.forEach(row => {
            row.style.opacity = locked ? '0.5' : '';
            row.style.pointerEvents = locked ? 'none' : '';
        });
    }

    if (nativeCheckbox) {
        toggleNativeLock(nativeCheckbox.checked);
        nativeCheckbox.addEventListener('change', function () {
            toggleNativeLock(this.checked);
        });
    }

    // === Related Command Lock ===
    const relatedRadios = document.querySelectorAll('input[name="init_plugin_suite_live_search_settings[default_command]"]');
    const relatedLockedRows = document.querySelectorAll('tr[data-related-locked="1"]');

    function toggleRelatedLock() {
        const isRelated = Array.from(relatedRadios).some(function (r) {
            return r.value === 'related' && r.checked;
        });
        relatedLockedRows.forEach(function (row) {
            row.style.opacity = isRelated ? '' : '0.5';
            row.style.pointerEvents = isRelated ? '' : 'none';
        });
    }

    if (relatedRadios.length && relatedLockedRows.length) {
        toggleRelatedLock();
        relatedRadios.forEach(function (radio) {
            radio.addEventListener('change', toggleRelatedLock);
        });
    }

    // === Meilisearch: Test Connection ===
    const meiliTestBtn = document.getElementById('init-ls-meili-test-connection');
    const meiliResultEl = document.getElementById('init-ls-meili-test-result');
    if (meiliTestBtn && meiliResultEl) {
        const i18n = init_plugin_suite_live_search_ajax.i18n || {};

        meiliTestBtn.addEventListener('click', function () {
            meiliTestBtn.disabled = true;
            meiliResultEl.style.color = '';
            meiliResultEl.style.lineHeight = '40px';
            meiliResultEl.textContent = i18n.meiliTesting || 'Testing...';

            fetch(init_plugin_suite_live_search_ajax.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-WP-Nonce': init_plugin_suite_live_search_ajax.nonce
                },
                body: new URLSearchParams({
                    action: 'init_plugin_suite_live_search_meili_test',
                    host: document.getElementById('meili_host').value,
                    index: document.getElementById('meili_index').value,
                    search_key: document.getElementById('meili_search_key').value
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        meiliResultEl.style.color = '#00a32a';
                        meiliResultEl.textContent = (i18n.meiliConnected || 'Connected successfully') + ' — ' +
                            (data.data.estimatedTotalHits ?? '?') + ' ' + (i18n.meiliDocuments || 'documents');
                    } else {
                        meiliResultEl.style.color = '#d63638';
                        meiliResultEl.textContent = data.data || (i18n.meiliConnectionFailed || 'Connection failed');
                    }
                })
                .catch(() => {
                    meiliResultEl.style.color = '#d63638';
                    meiliResultEl.textContent = i18n.meiliUnknownError || 'Unknown error';
                })
                .finally(() => { meiliTestBtn.disabled = false; });
        });
    }
});
