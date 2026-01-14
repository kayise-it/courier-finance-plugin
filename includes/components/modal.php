<?php
if (! defined('ABSPATH')) {
    exit;
}

class KIT_Modal
{
    public static function render($id, $title = '', $content = '', $size = 'md', $show_button = true, $button_text = '')
    {
        $size_classes = [
            'sm'   => 'max-w-sm',
            'md'   => 'max-w-md',
            'lg'   => 'max-w-lg',
            'xl'   => 'max-w-xl',
            '2xl'  => 'max-w-2xl',
            '3xl'  => 'max-w-3xl',
            '4xl'  => 'max-w-4xl',
            '5xl'  => 'max-w-5xl',
            '6xl'  => 'max-w-6xl',
            'full' => 'max-w-full w-full mx-4',
        ];

        $size_class = $size_classes[$size] ?? $size_classes['xl'];

        ob_start(); ?>
        <?php
        if ($show_button) {
            $button_label = $button_text ?: $title;
            echo KIT_Commons::kitButton([
                'color' => 'blue',
                'modal' => $id,
                'icon' => 'plus',
            ], $button_label);
        }
        ?>
        <div id="<?php echo esc_attr($id); ?>"
            class="hidden fixed inset-0 z-[99999] items-start pt-10 justify-center bg-black bg-opacity-50 overflow-y-auto">
            <!-- Modal panel -->
            <div class="bg-white rounded-lg shadow-xl overflow-y-auto <?php echo esc_attr($size_class); ?> max-h-[90vh] my-8">
                <div class="flex justify-between items-center px-6 py-4 border-b sticky top-0 bg-white z-10">
                    <?php if ($title): ?>
                        <h3 class="text-lg font-semibold text-gray-800"><?php echo esc_html($title); ?></h3>
                    <?php endif; ?>
                    <button type="button" class="modal-close text-gray-500 hover:text-gray-700 focus:outline-none">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="p-6">
                    <?php echo $content; ?>
                </div>
            </div>
        </div>

        <script>
            jQuery(document).ready(function($) {
                const modal = $('#<?php echo esc_js($id); ?>');
                const body = $('body');

                // Open modal
                $(document).on('click', '[data-modal="<?php echo esc_js($id); ?>"]', function(e) {
                    e.preventDefault();
                    modal.removeClass('hidden').addClass('flex');
                    body.addClass('overflow-hidden');
                    
                    // Trigger custom event when modal opens
                    modal.trigger('modal:opened');
                    $(document).trigger('modal:opened', [modal]);
                });

                // Close modal
                modal.find('.modal-close').on('click', function(e) {
                    e.preventDefault();
                    modal.removeClass('flex').addClass('hidden');
                    body.removeClass('overflow-hidden');
                });

                // Close when clicking on backdrop
                modal.on('click', function(e) {
                    if (e.target === this) {
                        modal.removeClass('flex').addClass('hidden');
                        body.removeClass('overflow-hidden');
                    }
                });

                // Close with ESC key
                $(document).on('keydown', function(e) {
                    if (e.key === 'Escape' && modal.hasClass('flex')) {
                        modal.removeClass('flex').addClass('hidden');
                        body.removeClass('overflow-hidden');
                    }
                });
            });
        </script>
<?php
        return ob_get_clean();
    }
}
