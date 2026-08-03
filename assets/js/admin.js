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

    // === Meilisearch: Reindex Now + background progress polling ===
    const meiliReindexBtn = document.getElementById('init-ls-meili-reindex-now');
    const meiliStatusEl = document.getElementById('init-ls-meili-reindex-status');

    if (meiliReindexBtn && meiliStatusEl) {
        const i18n = init_plugin_suite_live_search_ajax.i18n || {};
        let meiliPollTimer = null;

        function renderMeiliStatus(data) {
            let html = '';

            if (data.running) {
                let text = i18n.meiliReindexing || 'Reindexing in the background…';
                if (data.total) {
                    text += ' (' + data.total + ')';
                }
                html = '<p class="description">' + text + '</p>';
            } else if (data.last_error) {
                html = '<p class="description" style="color:#d63638;">' +
                    (i18n.meiliReindexStopped || 'Background reindex stopped after repeated errors:') +
                    ' ' + data.last_error + '</p>';
            } else if (data.indexed_at) {
                html = '<p class="description">' +
                    (i18n.meiliIndexLastBuilt || 'Index last built:') + ' ' + data.indexed_at + '.</p>';
            }

            if (!data.running && Array.isArray(data.skipped) && data.skipped.length) {
                const template = i18n.meiliSkippedWarning ||
                    '%1$d post(s) were too large to send to Meilisearch even individually and were skipped (post IDs: %2$s).';
                const message = template
                    .replace('%1$d', data.skipped.length)
                    .replace('%2$s', data.skipped.join(', '));
                html += '<p class="description" style="color:#dba617;">' + message + '</p>';
            }

            meiliStatusEl.innerHTML = html;
        }

        function stopMeiliPolling() {
            if (meiliPollTimer) {
                clearInterval(meiliPollTimer);
                meiliPollTimer = null;
            }
            meiliReindexBtn.disabled = false;
        }

        function pollMeiliStatus() {
            fetch(init_plugin_suite_live_search_ajax.ajaxurl + '?action=init_plugin_suite_live_search_meili_reindex_status', {
                headers: { 'X-WP-Nonce': init_plugin_suite_live_search_ajax.nonce }
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) return;
                    renderMeiliStatus(data.data);
                    if (!data.data.running) {
                        stopMeiliPolling();
                    }
                })
                .catch(() => {});
        }

        meiliReindexBtn.addEventListener('click', function () {
            meiliReindexBtn.disabled = true;
            meiliStatusEl.innerHTML = '<p class="description">' + (i18n.meiliReindexStarted || 'Started…') + '</p>';

            fetch(init_plugin_suite_live_search_ajax.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-WP-Nonce': init_plugin_suite_live_search_ajax.nonce
                },
                body: new URLSearchParams({ action: 'init_plugin_suite_live_search_meili_start_reindex' })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (!meiliPollTimer) {
                            meiliPollTimer = setInterval(pollMeiliStatus, 4000);
                        }
                        pollMeiliStatus();
                    } else {
                        meiliReindexBtn.disabled = false;
                        meiliStatusEl.innerHTML = '<p class="description" style="color:#d63638;">' +
                            (typeof data.data === 'string' && data.data ? data.data : (i18n.meiliUnknownError || 'Unknown error')) +
                            '</p>';
                    }
                })
                .catch(() => {
                    meiliReindexBtn.disabled = false;
                });
        });

        // Nếu tiến trình đang chạy sẵn từ trước khi load trang (vd admin rời
        // trang rồi quay lại) — tự resume polling, không cần bấm lại nút.
        if (meiliStatusEl.dataset.running === '1') {
            meiliReindexBtn.disabled = true;
            meiliPollTimer = setInterval(pollMeiliStatus, 4000);
        }
    }
});
