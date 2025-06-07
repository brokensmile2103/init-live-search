<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Handle clear logs securely with nonce
if (
    isset($_POST['ils_clear_logs']) &&
    check_admin_referer('ils_clear_logs_action', 'ils_clear_logs_nonce')
) {
    $chunk_index = absint(get_option('ils_log_chunk_index', 1));
    for ($i = 1; $i <= $chunk_index; $i++) {
        delete_transient("ils_log_chunk_$i");
    }
    update_option('ils_log_chunk_index', 1);
    wp_safe_redirect(admin_url('options-general.php?page=init-live-search-settings&tab=analytics'));
    exit;
}

$chunk_index = absint(get_option('ils_log_chunk_index', 1));
$all_logs = [];

for ($i = 1; $i <= $chunk_index; $i++) {
    $logs = get_transient("ils_log_chunk_$i");
    if (is_array($logs)) {
        $all_logs = array_merge($all_logs, $logs);
    }
}

usort($all_logs, fn($a, $b) => strcmp($b['time'], $a['time']));
?>

<h2><?php esc_html_e('Search Analytics', 'init-live-search'); ?></h2>

<p>
    <?php esc_html_e('Below is a temporary log of recent search queries. No IPs or personal data are stored.', 'init-live-search'); ?>
</p>

<?php if (!empty($all_logs)) : ?>
<form method="post" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
    <?php wp_nonce_field('ils_clear_logs_action', 'ils_clear_logs_nonce'); ?>
    <label style="display: inline-block; margin: 0;">
        <input type="checkbox" id="ils-toggle-group" />
        <?php esc_html_e('Group similar queries', 'init-live-search'); ?>
    </label>
    <div>
        <?php submit_button(__('Clear All Logs', 'init-live-search'), 'delete', 'ils_clear_logs', false); ?>
        <a href="<?php echo esc_url(add_query_arg('ils_export_csv', '1')); ?>" class="button"><?php esc_html_e('Export CSV', 'init-live-search'); ?></a>
    </div>
</form>
<?php endif; ?>

<?php
if (isset($_GET['ils_export_csv'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=ils-analytics.csv');
    $fh = fopen('php://output', 'w'); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
    fputcsv($fh, ['Time', 'Query', 'Results']);
    foreach ($all_logs as $row) {
        fputcsv($fh, [
            $row['time'],
            $row['query'],
            $row['results'],
        ]);
    }
    fclose($fh); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
    exit;
}
?>

<?php if (empty($all_logs)) : ?>
    <p><em><?php esc_html_e('No data recorded yet.', 'init-live-search'); ?></em></p>
<?php else : ?>
    <div style="max-height: 800px; overflow-y: auto;">
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Time', 'init-live-search'); ?></th>
                    <th><?php esc_html_e('Query', 'init-live-search'); ?></th>
                    <th><?php esc_html_e('Results', 'init-live-search'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_logs as $row) : ?>
                    <tr>
                        <td><?php echo esc_html($row['time']); ?></td>
                        <td><code><?php echo esc_html($row['query']); ?></code></td>
                        <td><?php echo (int) $row['results']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkbox = document.getElementById('ils-toggle-group');
    const tableBody = document.querySelector('.widefat tbody');

    if (!checkbox || !tableBody) return;

    const originalRows = Array.from(tableBody.querySelectorAll('tr'));

    checkbox.addEventListener('change', () => {
        tableBody.innerHTML = '';

        if (checkbox.checked) {
            const grouped = {};

            originalRows.forEach(row => {
                const cells = row.querySelectorAll('td');
                const query = cells[1]?.textContent.trim().toLowerCase().replace(/\s+/g, ' ') || '';
                const time = cells[0]?.textContent || '';
                const results = parseInt(cells[2]?.textContent || '0');

                if (!grouped[query]) {
                    grouped[query] = {
                        count: 1,
                        latest: time,
                        results
                    };
                } else {
                    grouped[query].count++;
                }
            });

            const sorted = Object.entries(grouped)
                .sort((a, b) => b[1].count - a[1].count); // sắp theo count giảm dần

            sorted.forEach(([query, data]) => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${data.latest}</td>
                    <td><code>${query}</code> <span style="color: #666;">×${data.count}</span></td>
                    <td>${data.results}</td>
                `;
                tableBody.appendChild(row);
            });

        } else {
            originalRows.forEach(row => tableBody.appendChild(row));
        }
    });
});
</script>
