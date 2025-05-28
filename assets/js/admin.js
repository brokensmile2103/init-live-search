document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('generate_keywords_button');
    const input = document.getElementById('suggested_keywords_input');

    if (!btn || !input) return;

    btn.addEventListener('click', function () {
        btn.disabled = true;

        fetch(init_plugin_suite_live_search_ajax.ajaxurl + '?action=init_plugin_suite_live_search_generate_keywords', {
            method: 'GET',
            headers: {
                'X-WP-Nonce': init_plugin_suite_live_search_ajax.nonce
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    input.value = data.data || '';
                } else {
                    alert('Failed to generate keywords');
                }
            })
            .catch(error => {
                alert('Error: ' + error);
            })
            .finally(() => {
                btn.disabled = false;
            });
    });
});
