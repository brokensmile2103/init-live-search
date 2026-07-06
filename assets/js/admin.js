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
});
