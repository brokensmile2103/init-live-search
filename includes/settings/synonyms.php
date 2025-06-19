<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$custom_synonyms = get_option(INIT_PLUGIN_SUITE_LS_SYNONYM_OPTION, '{}');
?>

<h2><?php esc_html_e('Synonym Configuration', 'init-live-search'); ?></h2>
<p><?php esc_html_e('You can define custom synonyms here. This will allow search terms to be expanded using your specific site vocabulary.', 'init-live-search'); ?></p>

<form method="post" action="options.php" id="synonym-config-form">
    <?php settings_fields(INIT_PLUGIN_SUITE_LS_GROUP_SYNONYMS); ?>

    <table class="form-table" role="presentation">
        <tr>
            <th scope="row"><?php esc_html_e('Custom Synonym Map (JSON)', 'init-live-search'); ?></th>
            <td>
                <textarea name="<?php echo esc_attr(INIT_PLUGIN_SUITE_LS_SYNONYM_OPTION); ?>"
                          id="custom_synonyms_json"
                          rows="12"
                          cols="80"
                          class="large-text code"><?php echo esc_textarea($custom_synonyms); ?></textarea>
                <p class="description">
                    <?php esc_html_e('Enter a JSON object where each key maps to an array of synonyms. Example:', 'init-live-search'); ?><br>
                    <code>{"reaction": ["tương tác", "phản hồi"], "buy": ["mua", "order"]}</code>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('Add or Update Synonym', 'init-live-search'); ?></th>
            <td>
                <input type="text" id="syn_key" placeholder="<?php esc_attr_e('Keyword (e.g. reaction)', 'init-live-search'); ?>" class="regular-text">
                <input type="text" id="syn_value" placeholder="<?php esc_attr_e('Synonym (e.g. tương tác)', 'init-live-search'); ?>" class="regular-text">
                <button type="button" class="button" id="add_synonym_btn"><?php esc_html_e('Add / Update', 'init-live-search'); ?></button>
                <p class="description"><?php esc_html_e('Click to insert or update a keyword → synonym pair into the JSON above.', 'init-live-search'); ?></p>
            </td>
        </tr>
    </table>

    <?php submit_button(); ?>
</form>

<style>
    #custom_synonyms_json.invalid-json {
        border: 2px solid red !important;
        background: #ffeaea;
    }
    #json-error-msg {
        color: red;
        margin-top: 6px;
        font-size: 13px;
    }
</style>

<script>
const keyInput = document.getElementById('syn_key');
const valInput = document.getElementById('syn_value');
const textarea = document.getElementById('custom_synonyms_json');
const addBtn = document.getElementById('add_synonym_btn');
const form = document.getElementById('synonym-config-form');

// Tạo khung hiển thị lỗi JSON phía dưới textarea
const errorMsg = document.createElement('div');
errorMsg.id = 'json-error-msg';
textarea.parentNode.appendChild(errorMsg);

// Reset lỗi khi người dùng gõ
function clearJsonError() {
    textarea.classList.remove('invalid-json');
    errorMsg.textContent = '';
}

// Xử lý thêm/cập nhật synonym
function insertSynonym() {
    const key = keyInput.value.trim().toLowerCase();
    const val = valInput.value.trim();
    if (!key || !val) return;

    let data = {};
    clearJsonError();

    try {
        data = JSON.parse(textarea.value || '{}');

        if (typeof data !== 'object' || Array.isArray(data)) {
            throw new Error('JSON must be an object with key-value pairs.');
        }

        if (!Array.isArray(data[key])) data[key] = [];
        if (!data[key].includes(val)) data[key].push(val);

        textarea.value = JSON.stringify(data, null, 2);
        valInput.value = '';
    } catch (err) {
        textarea.classList.add('invalid-json');
        errorMsg.textContent = '⛔ ' + err.message;
    }
}

// Click nút thêm synonym
addBtn?.addEventListener('click', insertSynonym);

// Nhấn Enter trong ô input → insert
[keyInput, valInput].forEach(input => {
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            insertSynonym();
        }
    });
});

// Validate JSON trước khi submit
form?.addEventListener('submit', function (e) {
    const raw = textarea.value.trim();
    clearJsonError();

    if (raw === '') return;

    try {
        const parsed = JSON.parse(raw);

        if (typeof parsed !== 'object' || Array.isArray(parsed)) {
            throw new Error('JSON must be an object with key-value pairs.');
        }

        for (const key in parsed) {
            if (!Array.isArray(parsed[key])) {
                throw new Error(`"${key}" must map to an array of synonyms.`);
            }
        }
    } catch (err) {
        e.preventDefault();
        textarea.classList.add('invalid-json');
        errorMsg.textContent = '⛔ ' + err.message;
    }
});

textarea.addEventListener('input', clearJsonError);
</script>
