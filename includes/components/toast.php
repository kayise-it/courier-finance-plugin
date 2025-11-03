<?php
if (!defined('ABSPATH')) {
	exit;
}

class KIT_Toast
{
	/**
	 * Initialize the toast system
	 */
	public static function init()
	{
		add_action('admin_enqueue_scripts', [self::class, 'enqueue_scripts']);
		add_action('admin_footer', [self::class, 'render_toast_container']);
	}

	/**
	 * Enqueue necessary scripts and styles
	 */
	public static function enqueue_scripts()
	{
		// Only load on admin pages
		if (!is_admin()) {
			return;
		}
		
		// Add inline CSS for toast styling
		wp_add_inline_style('wp-admin', self::get_toast_css());
	}

	/**
	 * Get CSS for toast notifications
	 */
	private static function get_toast_css()
	{
		return '
		.kit-toast-container {
			position: fixed;
			top: 16px;
			left: 50%;
			transform: translateX(-50%);
			z-index: 999999;
			display: flex;
			flex-direction: column;
			align-items: center;
			gap: 10px;
			pointer-events: none;
		}
		
		.kit-toast {
			pointer-events: auto;
			background: #323232;
			color: #ffffff;
			border-radius: 8px;
			box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
			padding: 14px 18px;
			display: flex;
			align-items: center;
			gap: 12px;
			min-width: 280px;
			max-width: 480px;
			transform: translateY(-20px);
			opacity: 0;
			transition: transform 0.25s ease, opacity 0.25s ease;
			border-left: 4px solid;
		}
		
		.kit-toast.show {
			transform: translateY(0);
			opacity: 1;
		}
		
		.kit-toast.success {
			border-left-color: #10b981;
		}
		
		.kit-toast.error {
			border-left-color: #ef4444;
		}
		
		.kit-toast.warning {
			border-left-color: #f59e0b;
		}
		
		.kit-toast.info {
			border-left-color: #3b82f6;
		}
		
		.kit-toast-icon {
			flex-shrink: 0;
			width: 20px;
			height: 20px;
		}
		
		.kit-toast-content {
			flex: 1;
		}
		
		.kit-toast-title {
			font-weight: 600;
			font-size: 14px;
			margin: 0 0 4px 0;
		}
		
		.kit-toast-message {
			font-size: 13px;
			color: #e5e7eb;
			margin: 0;
		}
		
		.kit-toast-close {
			background: none;
			border: none;
			cursor: pointer;
			padding: 4px;
			border-radius: 4px;
			opacity: 0.5;
			transition: opacity 0.2s ease;
		}
		
		.kit-toast-close:hover {
			opacity: 1;
		}
		';
	}

	/**
	 * Render the toast container
	 */
	public static function render_toast_container()
	{
		if (!is_admin()) {
			return;
		}
		?>
		<div id="kit-toast-container" class="kit-toast-container"></div>
		<script>
		(function() {
			// Toast system
			window.KITToast = {
				show: function(message, type, title) {
					type = type || 'info';
					title = title || this.getDefaultTitle(type);
					
					var container = document.getElementById('kit-toast-container');
					if (!container) return;
					
					var toast = document.createElement('div');
					toast.className = 'kit-toast ' + type;
					
					var icon = this.getIcon(type);
					var closeButton = '<button class="kit-toast-close" onclick="this.parentElement.remove()">×</button>';
					
					toast.innerHTML = 
						'<div class="kit-toast-icon">' + icon + '</div>' +
						'<div class="kit-toast-content">' +
							'<div class="kit-toast-title">' + title + '</div>' +
							'<div class="kit-toast-message">' + message + '</div>' +
						'</div>' +
						closeButton;
					
					container.appendChild(toast);
					
					// Trigger animation
					setTimeout(function() {
						toast.classList.add('show');
					}, 10);
					
					// Auto remove after ~4 seconds
					setTimeout(function() {
						if (toast.parentElement) {
							toast.style.opacity = '0';
							toast.style.transform = 'translateY(-20px)';
							setTimeout(function() {
								if (toast.parentElement) {
									toast.remove();
								}
							}, 250);
						}
					}, 4000);
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
				
				getIcon: function(type) {
					var icons = {
						'success': '<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-7.364 7.364a1 1 0 01-1.414 0L3.293 10.435a1 1 0 011.414-1.414l3.01 3.01 6.657-6.657a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>',
						'error': '<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-5h2v2H9v-2zm0-6h2v5H9V7z" clip-rule="evenodd"/></svg>',
						'warning': '<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.72-1.36 3.485 0l6.518 11.59c.75 1.335-.213 2.99-1.742 2.99H3.48c-1.53 0-2.492-1.655-1.743-2.99L8.257 3.1zM11 13a1 1 0 10-2 0 1 1 0 002 0zm-1-7a1 1 0 00-1 1v3a1 1 0 002 0V7a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>',
						'info': '<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10A8 8 0 11 2 10a8 8 0 0116 0zM9 9h2V7H9v2zm0 6h2v-5H9v5z" clip-rule="evenodd"/></svg>'
					};
					return icons[type] || icons['info'];
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
		$icon = self::getIcon($type);

		ob_start();
		?>
		<script>
		document.addEventListener('DOMContentLoaded', function() {
			if (window.KITToast) {
				window.KITToast.show('<?php echo esc_js($message); ?>', '<?php echo esc_js($type); ?>', '<?php echo esc_js($title); ?>');
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

	private static function getIcon($type)
	{
		$icons = [
			'success' => '<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-7.364 7.364a1 1 0 01-1.414 0L3.293 10.435a1 1 0 011.414-1.414l3.01 3.01 6.657-6.657a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>',
			'error' => '<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-5h2v2H9v-2zm0-6h2v5H9V7z" clip-rule="evenodd"/></svg>',
			'warning' => '<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.72-1.36 3.485 0l6.518 11.59c.75 1.335-.213 2.99-1.742 2.99H3.48c-1.53 0-2.492-1.655-1.743-2.99L8.257 3.1zM11 13a1 1 0 10-2 0 1 1 0 002 0zm-1-7a1 1 0 00-1 1v3a1 1 0 002 0V7a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>',
			'info' => '<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10A8 8 0 11 2 10a8 8 0 0116 0zM9 9h2V7H9v2zm0 6h2v-5H9v5z" clip-rule="evenodd"/></svg>'
		];
		return $icons[$type] ?? $icons['info'];
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
		self::enqueue_scripts();
		self::render_toast_container(); // This already includes the JS
	}
}


