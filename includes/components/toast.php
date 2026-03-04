<?php
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Bootstrap 4.3 Toast Notification System
 * Replaces the old KIT_Toast with Bootstrap 4.3 toasts
 * Based on: https://getbootstrap.com/docs/4.3/components/toasts/
 */
class KIT_Toast
{
	/**
	 * Initialize the Bootstrap toast system
	 */
	public static function init()
	{
		add_action('admin_enqueue_scripts', [self::class, 'enqueue_bootstrap']);
		add_action('admin_footer', [self::class, 'render_toast_container']);
	}

	/**
	 * Enqueue Bootstrap 4.3 CSS and JavaScript
	 */
	public static function enqueue_bootstrap()
	{
		// Only load on admin pages
		if (!is_admin()) {
			return;
		}

		// Enqueue Bootstrap 4.3 CSS
		if (!wp_style_is('bootstrap-css', 'enqueued')) {
			wp_enqueue_style(
				'bootstrap-css',
				'https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css',
				[],
				'4.3.1'
			);
		}

		// Enqueue Popper.js (required for Bootstrap JS)
		if (!wp_script_is('popper-js', 'enqueued')) {
			wp_enqueue_script(
				'popper-js',
				'https://cdn.jsdelivr.net/npm/popper.js@1.14.7/dist/umd/popper.min.js',
				['jquery'],
				'1.14.7',
				true
			);
		}

		// Enqueue Bootstrap 4.3 JavaScript (includes util.js)
		if (!wp_script_is('bootstrap-js', 'enqueued')) {
			wp_enqueue_script(
				'bootstrap-js',
				'https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/js/bootstrap.min.js',
				['jquery', 'popper-js'],
				'4.3.1',
				true
			);
		}
	}

	/**
	 * Render the toast container (positioned top-right for notifications)
	 */
	public static function render_toast_container()
	{
		if (!is_admin()) {
			return;
		}
		?>
		<!-- Toast Container - Positioned top right -->
		<div aria-live="polite" aria-atomic="true" style="position: fixed; top: 20px; right: 20px; z-index: 999999; min-height: 200px;">
			<div id="kit-toast-container" style="position: absolute; top: 0; right: 0;"></div>
		</div>

		<style>
		/* Custom styling for larger toasts */
		#kit-toast-container .toast {
			min-width: 350px;
			max-width: 450px;
			font-size: 15px;
		}
		#kit-toast-container .toast-header {
			padding: 14px 16px;
			font-size: 16px;
			font-weight: 600;
		}
		#kit-toast-container .toast-body {
			padding: 16px;
			font-size: 15px;
			line-height: 1.5;
		}
		#kit-toast-container .toast-header .close {
			font-size: 20px;
			opacity: 0.9;
		}
		</style>

		<script>
		(function() {
			// Bootstrap Toast wrapper
			window.KITToast = {
				show: function(message, type, title) {
					type = type || 'info';
					title = title || this.getDefaultTitle(type);

					var container = document.getElementById('kit-toast-container');
					if (!container) {
						console.error('Toast container not found');
						return;
					}

					// Create unique toast ID
					var toastId = 'toast-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);

					// Determine Bootstrap background color based on type
					var bgClass = this.getBootstrapClass(type);

					// Create toast HTML structure per Bootstrap 4.3 docs with larger styling
					var toastHtml = 
						'<div class="toast" role="alert" aria-live="assertive" aria-atomic="true" id="' + toastId + '" data-delay="5000" style="min-width: 350px; max-width: 450px;">' +
							'<div class="toast-header ' + bgClass + ' text-white" style="padding: 14px 16px; font-size: 16px; font-weight: 600;">' +
								'<strong class="mr-auto">' + this.escapeHtml(title) + '</strong>' +
								'<button type="button" class="ml-2 mb-1 close text-white" data-dismiss="toast" aria-label="Close" style="font-size: 20px; opacity: 0.9;">' +
									'<span aria-hidden="true">&times;</span>' +
								'</button>' +
							'</div>' +
							'<div class="toast-body" style="padding: 16px; font-size: 15px; line-height: 1.5;">' +
								this.escapeHtml(message) +
							'</div>' +
						'</div>';

					// Create toast element
					var toastElement = document.createElement('div');
					toastElement.innerHTML = toastHtml;
					var toast = toastElement.firstElementChild;

					// Append to container
					container.appendChild(toast);

					// Initialize and show Bootstrap toast
					jQuery(toast).toast({
						autohide: true,
						delay: 5000
					});

					// Show the toast
					jQuery(toast).toast('show');

					// Remove from DOM after it's hidden
					jQuery(toast).on('hidden.bs.toast', function() {
						jQuery(this).remove();
					});
				},

				getDefaultTitle: function(type) {
					var titles = {
						'success': 'Success',
						'error': 'Error',
						'warning': 'Warning',
						'info': 'Information'
					};
					return titles[type] || 'Notification';
				},

				getBootstrapClass: function(type) {
					var classes = {
						'success': 'bg-success',
						'error': 'bg-danger',
						'warning': 'bg-warning',
						'info': 'bg-info'
					};
					return classes[type] || 'bg-info';
				},

				escapeHtml: function(text) {
					var map = {
						'&': '&amp;',
						'<': '&lt;',
						'>': '&gt;',
						'"': '&quot;',
						"'": '&#039;'
					};
					return text.replace(/[&<>"']/g, function(m) { return map[m]; });
				}
			};
		})();
		</script>
		<?php
	}

	/**
	 * Show a toast notification (for immediate display)
	 */
	public static function show($message, $type = 'info', $title = null)
	{
		$title = $title ?: self::getDefaultTitle($type);

		ob_start();
		?>
		<script>
		document.addEventListener('DOMContentLoaded', function() {
			if (window.KITToast && typeof jQuery !== 'undefined') {
				jQuery(document).ready(function($) {
					window.KITToast.show('<?php echo esc_js($message); ?>', '<?php echo esc_js($type); ?>', '<?php echo esc_js($title); ?>');
				});
			} else {
				console.error('KITToast or jQuery not available');
			}
		});
		</script>
		<?php
		return ob_get_clean();
	}

	/**
	 * Legacy methods for backward compatibility
	 */
	public static function render($type, $message)
	{
		return self::show($message, $type);
	}

	public static function success($message, $title = null)
	{
		return self::show($message, 'success', $title);
	}

	public static function error($message, $title = null)
	{
		return self::show($message, 'error', $title);
	}

	public static function warning($message, $title = null)
	{
		return self::show($message, 'warning', $title);
	}

	public static function info($message, $title = null)
	{
		return self::show($message, 'info', $title);
	}

	/**
	 * Database operation success/error helpers
	 */
	public static function db_success($operation, $message)
	{
		return self::success($message, $operation . ' Success');
	}

	public static function db_error($operation, $message)
	{
		return self::error($message, $operation . ' Failed');
	}

	/**
	 * Helper methods
	 */
	private static function getDefaultTitle($type)
	{
		$titles = [
			'success' => 'Success',
			'error' => 'Error',
			'warning' => 'Warning',
			'info' => 'Information'
		];
		return $titles[$type] ?? 'Notification';
	}

	/**
	 * Ensure toast is loaded on any page where it's needed
	 * Can be called anywhere to activate the component
	 */
	public static function ensure_toast_loads()
	{
		// Initialize the system
		self::init();
		
		// Load the necessary components
		self::enqueue_bootstrap();
		self::render_toast_container();
	}
}
