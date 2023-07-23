<?php
/*
Plugin Name: Rckflr WP Cleanup Scheduled Actions
Plugin URI: https://rckflr.party/
Description: Este plugin permite limpiar acciones programadas fallidas, canceladas y completadas. A través de su página de configuración, se pueden seleccionar estados específicos de acción para limpiar, definir la cantidad de registros a eliminar y ajustar el período de retención de las acciones programadas.
Version: 1.0
Author: Mauricio Perera
Author URI: https://www.linkedin.com/in/mauricioperera/
Donate link: https://www.buymeacoffee.com/rckflr
*/

function rckflr_cleanup_scheduled_actions() {
    global $wpdb;

    if(isset($_POST['status']) && !empty($_POST['status'])) {
        $status = $_POST['status'];
        $limit = isset($_POST['limit']) && !empty($_POST['limit']) ? intval($_POST['limit']) : null;

        $actions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM `{$wpdb->prefix}actionscheduler_actions` WHERE `status` = %s" . (is_null($limit) ? '' : " LIMIT {$limit}"),
                $status
            )
        );

        if(count($actions) > 0) {
            foreach($actions as $action) {
                $wpdb->delete(
                    "{$wpdb->prefix}actionscheduler_actions",
                    ['action_id' => $action->action_id],
                    ['%d']
                );
            }
        }
    }

    if(isset($_POST['retention_period']) && !empty($_POST['retention_period'])) {
        update_option('retention_period', $_POST['retention_period']);
    }
}
add_action('admin_init', 'rckflr_cleanup_scheduled_actions');

function rckflr_change_retention_period() {
    $retention_period = get_option('retention_period', DAY_IN_SECONDS * 30);
    return $retention_period;
}
add_filter('action_scheduler_retention_period', 'rckflr_change_retention_period');

function rckflr_cleanup_scheduled_actions_admin_menu() {
    add_menu_page('Limpiar Acciones Programadas', 'Limpiar Acciones Programadas', 'manage_options', 'cleanup-scheduled-actions', 'rckflr_cleanup_scheduled_actions_admin_page');
}
add_action('admin_menu', 'rckflr_cleanup_scheduled_actions_admin_menu');

function rckflr_cleanup_scheduled_actions_admin_page(){
    global $wpdb;

    $statuses = ['failed' => 'fallidas', 'canceled' => 'canceladas', 'complete' => 'completadas'];
    $counts = [];
    $retention_periods = [
        '1 Minuto' => MINUTE_IN_SECONDS,
        '1 Hora' => HOUR_IN_SECONDS,
        '1 Día' => DAY_IN_SECONDS,
        '1 Semana' => WEEK_IN_SECONDS
    ];
    $current_retention_period = get_option('retention_period', DAY_IN_SECONDS * 30);

    foreach($statuses as $status => $status_label) {
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM `{$wpdb->prefix}actionscheduler_actions` WHERE `status` = %s",
                $status
            )
        );

        $counts[$status_label] = $count;
    }

    ?>
    <div class="wrap">
        <h1>Limpiar Acciones Programadas</h1>

        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="status">Estado</label></th>
                    <td>
                        <select name="status" id="status">
                            <option value="">-- Seleccionar Estado --</option>
                            <?php foreach($statuses as $status => $status_label): ?>
                            <option value="<?php echo esc_attr($status); ?>"><?php echo esc_html(ucfirst($status_label)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="limit">Límite</label></th>
                    <td>
                        <input type="number" name="limit" id="limit" min="1" step="1">
                        <p class="description">Ingrese el número de acciones a eliminar. Dejar en blanco para eliminar todas.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="retention_period">Período de Retención</label></th>
                    <td>
                        <select name="retention_period" id="retention_period">
                            <option value="">-- Seleccionar Período de Retención --</option>
                            <?php foreach($retention_periods as $name => $seconds): ?>
                            <option value="<?php echo esc_attr($seconds); ?>" <?php selected($current_retention_period, $seconds); ?>><?php echo esc_html($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>

            <?php submit_button('Limpiar Acciones'); ?>
        </form>

        <a href="https://www.buymeacoffee.com/rckflr" target="_blank" class="button">Buy Me a Coffee</a>

        <h2>Conteo de Acciones</h2>

        <ul>
            <?php foreach($counts as $status_label => $count): ?>
            <li><?php echo esc_html(ucfirst($status_label)); ?>: <?php echo esc_html($count); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
}
?>
