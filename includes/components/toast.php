<?php
if (!defined('ABSPATH')) {
	exit;
}

class KIT_Toast
{
	public static function render($type, $message)
	{
		$type = in_array($type, ['success', 'error', 'warning', 'info']) ? $type : 'info';
		$colors = [
			'success' => 'bg-green-600',
			'error' => 'bg-red-600',
			'warning' => 'bg-yellow-600',
			'info' => 'bg-blue-600',
		];
		$icon = [
			'success' => '<svg class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-7.364 7.364a1 1 0 01-1.414 0L3.293 10.435a1 1 0 011.414-1.414l3.01 3.01 6.657-6.657a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>',
			'error' => '<svg class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-5h2v2H9v-2zm0-6h2v5H9V7z" clip-rule="evenodd"/></svg>',
			'warning' => '<svg class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.72-1.36 3.485 0l6.518 11.59c.75 1.335-.213 2.99-1.742 2.99H3.48c-1.53 0-2.492-1.655-1.743-2.99L8.257 3.1zM11 13a1 1 0 10-2 0 1 1 0 002 0zm-1-7a1 1 0 00-1 1v3a1 1 0 002 0V7a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>',
			'info' => '<svg class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10A8 8 0 11 2 10a8 8 0 0116 0zM9 9h2V7H9v2zm0 6h2v-5H9v5z" clip-rule="evenodd"/></svg>',
		];

		ob_start();
		?>
		<div id="kit-toast-container" class="fixed top-5 right-5 z-[999999] flex flex-col gap-3"></div>
		<script>
		(function(){
			function showToast(message, type){
				var container = document.getElementById('kit-toast-container');
				if(!container){ return; }
				var wrapper = document.createElement('div');
				wrapper.className = 'pointer-events-auto shadow-lg rounded-md text-white px-4 py-3 flex items-center gap-3 <?php echo esc_attr($colors[$type]); ?>';
				wrapper.innerHTML = '<?php echo $icon[$type]; ?>' + '<span class="text-sm">' + message.replace(/'/g, "&#39;") + '</span>';
				container.appendChild(wrapper);
				setTimeout(function(){
					wrapper.style.transition = 'opacity 300ms ease';
					wrapper.style.opacity = '0';
					setTimeout(function(){ container.removeChild(wrapper); }, 320);
				}, 2800);
			}
			if(!window.KITToast){ window.KITToast = { show: showToast }; }
			showToast('<?php echo esc_js($message); ?>','<?php echo esc_js($type); ?>');
		})();
		</script>
		<?php
		return ob_get_clean();
	}

	public static function success($message){ return self::render('success', $message); }
	public static function error($message){ return self::render('error', $message); }
	public static function warning($message){ return self::render('warning', $message); }
	public static function info($message){ return self::render('info', $message); }
}


