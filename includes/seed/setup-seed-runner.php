<?php
/**
 * Shared helpers for executing courier finance seed SQL.
 *
 * These helpers are reused by the admin settings page and CLI simulations.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('kit_get_seed_owner_user_id')) {
    /**
     * Resolve owner for seeded/imported records.
     * Prefer Mel Welmans. If not found, fallback to user 1.
     */
    function kit_get_seed_owner_user_id(): int
    {
        global $wpdb;

        $mel_id = (int) $wpdb->get_var(
            "SELECT ID FROM {$wpdb->users}
             WHERE LOWER(display_name) = 'mel welmans'
                OR LOWER(display_name) LIKE '%mel%welmans%'
                OR LOWER(user_login) IN ('mel','melwelmans')
                OR LOWER(user_nicename) IN ('mel','melwelmans')
                OR LOWER(user_email) LIKE 'mel%@%'
             LIMIT 1"
        );
        if ($mel_id > 0) {
            return $mel_id;
        }

        return 1;
    }
}

if (!function_exists('kit_normalize_sql_statement')) {
    /**
     * Trim and clean a single SQL statement, stripping comments and trailing semicolons.
     */
    function kit_normalize_sql_statement(string $statement): string
    {
        $statement = trim($statement);
        if ($statement === '' || $statement === ';') {
            return '';
        }

        $statement = preg_replace('/--.*$/m', '', $statement);
        $statement = preg_replace('/\/\*.*?\*\//s', '', $statement);
        $statement = trim($statement);

        return rtrim($statement, ';');
    }
}

if (!function_exists('kit_split_sql_statements')) {
    /**
     * Split raw SQL content into individual statements, respecting quoted strings.
     *
     * @return string[]
     */
    function kit_split_sql_statements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $in_string = false;
        $string_char = '';
        $length = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $buffer .= $char;

            if ($in_string) {
                if ($char === '\\') {
                    if ($i + 1 < $length) {
                        $buffer .= $sql[++$i];
                    }
                    continue;
                }

                if ($char === $string_char) {
                    if ($char === "'" && $i + 1 < $length && $sql[$i + 1] === "'") {
                        $buffer .= $sql[++$i];
                        continue;
                    }

                    $in_string = false;
                    $string_char = '';
                }

                continue;
            }

            if ($char === "'" || $char === '"') {
                $in_string = true;
                $string_char = $char;
                continue;
            }

            if ($char === ';') {
                $statement = kit_normalize_sql_statement($buffer);
                if ($statement !== '') {
                    $statements[] = $statement;
                }
                $buffer = '';
            }
        }

        $statement = kit_normalize_sql_statement($buffer);
        if ($statement !== '') {
            $statements[] = $statement;
        }

        return $statements;
    }
}

if (!function_exists('kit_prepare_seed_sql_for_execution')) {
    /**
     * Normalise SQL content for execution with the current WordPress prefix and placeholders.
     */
    function kit_prepare_seed_sql_for_execution(string $sql_content, wpdb $wpdb): string
    {
        if (strncmp($sql_content, "\xEF\xBB\xBF", 3) === 0) {
            $sql_content = substr($sql_content, 3);
        }

        $sql_content = str_replace(["\r\n", "\r"], "\n", $sql_content);
        $sql_content = str_replace('{PREFIX}', $wpdb->prefix, $sql_content);
        $sql_content = preg_replace('/`wp_([a-zA-Z_]+)`/', '`' . $wpdb->prefix . '$1`', $sql_content);
        $sql_content = preg_replace('/(?<![a-zA-Z0-9_])wp_([a-zA-Z_]+)/', $wpdb->prefix . '$1', $sql_content);

        $created_by_user_id = function_exists('kit_get_seed_owner_user_id') ? kit_get_seed_owner_user_id() : 1;

        $sql_content = str_replace('{CREATED_BY}', (string) $created_by_user_id, $sql_content);

        return $sql_content;
    }
}

if (!function_exists('kit_run_setup_seed')) {
    /**
     * Execute (or simulate) the courier finance seed SQL.
     *
     * @param array{
     *     sql_file?: string|null,
     *     simulate?: bool,
     *     regenerate_if_missing?: bool,
     *     log_errors?: bool,
     *     log_success?: bool
     * } $args
     *
     * @return array{success:bool,message:string,stats?:array<string,int|array>,simulate?:bool}
     */
    function kit_run_setup_seed(array $args = []): array
    {
        global $wpdb;

        if (!isset($wpdb) || !($wpdb instanceof wpdb)) {
            return [
                'success' => false,
                'message' => 'Database connection unavailable while running setup seed.',
            ];
        }

        if (!function_exists('wp_parse_args')) {
            require_once ABSPATH . WPINC . '/functions.php';
        }

        $defaults = [
            'sql_file' => null,
            'simulate' => false,
            'regenerate_if_missing' => true,
            'log_errors' => true,
            'log_success' => false,
        ];
        $args = wp_parse_args($args, $defaults);

        $plugin_root = dirname(__DIR__, 2);
        $latest_sql_file = $plugin_root . '/latestSQL.sql';
        $new_sql_file = $plugin_root . '/newSQL.sql';

        $sql_file = is_string($args['sql_file']) && $args['sql_file'] !== ''
            ? $args['sql_file']
            : (file_exists($latest_sql_file) ? $latest_sql_file : (file_exists($new_sql_file) ? $new_sql_file : null));

        if (!$sql_file && !empty($args['regenerate_if_missing']) && function_exists('kit_generate_seed_sql_from_excel')) {
            $generated = kit_generate_seed_sql_from_excel();
            if (!empty($generated['success']) && !empty($generated['path']) && file_exists($generated['path'])) {
                $sql_file = $generated['path'];
            }
        }

        if (!$sql_file) {
            return [
                'success' => false,
                'message' => 'Seed SQL not found. Ensure latestSQL.sql or newSQL.sql exists in the plugin root.',
            ];
        }

        if (!is_readable($sql_file)) {
            return [
                'success' => false,
                'message' => 'Seed SQL file is not readable: ' . basename($sql_file),
            ];
        }

        if (function_exists('error_log')) {
            @error_log('[SetupSeed] Using SQL file: ' . $sql_file . ($args['simulate'] ? ' (simulation mode)' : ''));
        }

        $sql_content = file_get_contents($sql_file);
        if ($sql_content === false) {
            return [
                'success' => false,
                'message' => 'Failed to read seed SQL file.',
            ];
        }

        $sql_content = kit_prepare_seed_sql_for_execution($sql_content, $wpdb);
        $statements = kit_split_sql_statements($sql_content);

        if (empty($statements)) {
            return [
                'success' => false,
                'message' => 'No SQL statements found in the seed file after parsing.',
            ];
        }

        $simulate = !empty($args['simulate']);
        $executed = 0;
        $errors = [];
        $skipped = 0;

        if ($simulate) {
            $wpdb->query('START TRANSACTION');
        }

        foreach ($statements as $statement) {
            $trimmed = trim($statement);
            if ($trimmed === '') {
                continue;
            }

            // Customer INSERT statements are now included and should be executed

            if ($simulate) {
                $upper = strtoupper($trimmed);
                if (in_array($upper, ['START TRANSACTION', 'COMMIT', 'ROLLBACK'], true)) {
                    $skipped++;
                    continue;
                }
            }

            // Ensure statement ends with semicolon for proper execution
            $statement_to_execute = rtrim($statement, ';') . ';';
            
            $result = $wpdb->query($statement_to_execute);
            if ($result === false) {
                $error_message = $wpdb->last_error ?: 'Unknown database error';
                $errors[] = $error_message;
                if (!empty($args['log_errors']) && function_exists('error_log')) {
                    @error_log('[SetupSeed] DB Error: ' . $error_message . ' | SQL: ' . substr($statement, 0, 300));
                }
                if (!$simulate) {
                    break;
                }
            } else {
                $executed++;
            }
        }

        if ($simulate) {
            $wpdb->query('ROLLBACK');
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Seeding encountered errors. See details for the first error.',
                'stats' => [
                    'executed' => $executed,
                    'skipped' => $skipped,
                    'errors' => $errors,
                ],
                'simulate' => $simulate,
            ];
        }

        if (!empty($args['log_success']) && function_exists('error_log')) {
            @error_log('[SetupSeed] Seed executed successfully. Statements run: ' . $executed);
        }

        $message = $simulate
            ? 'Seed simulation completed successfully (no data committed).'
            : 'Setup seed executed successfully using ' . basename($sql_file) . '.';

        return [
            'success' => true,
            'message' => $message,
            'stats' => [
                'executed' => $executed,
                'skipped' => $skipped,
            ],
            'simulate' => $simulate,
        ];
    }
}


