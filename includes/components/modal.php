<?php
if (! defined('ABSPATH')) {
    exit;
}

class KIT_Modal
{
    /**
     * @param string $id Modal element ID
     * @param string $title Modal title
     * @param string $content Modal body HTML
     * @param string $size sm|md|lg|xl|2xl|3xl|4xl|5xl|6xl|full
     * @param bool $show_button Whether to render trigger button
     * @param string $button_text Button label (defaults to title)
     * @param bool|null $use_bootstrap True = Bootstrap 5 markup/JS, false = custom. Null = auto (Bootstrap on frontend)
     */
    public static function render($id, $title = '', $content = '', $size = 'md', $show_button = true, $button_text = '', $use_bootstrap = null)
    {
        if ($use_bootstrap === null) {
            $use_bootstrap = ! is_admin();
        }

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
        $bootstrap_size_classes = [
            'sm'   => 'modal-sm',
            'md'   => '',
            'lg'   => 'modal-lg',
            'xl'   => 'modal-xl',
            '2xl'  => 'modal-xl',
            '3xl'  => 'modal-xl',
            '4xl'  => 'modal-xl',
            '5xl'  => 'modal-xl',
            '6xl'  => 'modal-xl',
            'full' => 'modal-fullscreen',
        ];

        $size_class = $size_classes[$size] ?? $size_classes['xl'];
        $bs_dialog_class = $bootstrap_size_classes[$size] ?? $bootstrap_size_classes['xl'];

        ob_start();

        if ($use_bootstrap) {
            // --- Bootstrap 5 (frontend / employee dashboard) ---
            if ($show_button) {
                $button_label = $button_text ?: $title;
                echo KIT_Commons::kitButton([
                    'color'         => 'blue',
                    'data-bs-toggle' => 'modal',
                    'data-bs-target' => '#' . $id,
                    'icon'           => 'plus',
                ], $button_label);
            }
            ?>
            <div id="<?php echo esc_attr($id); ?>" class="modal fade" tabindex="-1" aria-labelledby="<?php echo esc_attr($id); ?>-label" aria-hidden="true">
                <div class="modal-dialog modal-dialog-scrollable <?php echo esc_attr($bs_dialog_class); ?>">
                    <div class="modal-content">
                        <div class="modal-header">
                            <?php if ($title) : ?>
                                <h5 class="modal-title" id="<?php echo esc_attr($id); ?>-label"><?php echo esc_html($title); ?></h5>
                            <?php endif; ?>
                            <?php echo KIT_Commons::renderButton('', 'ghost', 'lg', [
                                'type'           => 'button',
                                'classes'        => 'btn-close-placeholder text-gray-500 hover:text-gray-700',
                                'ariaLabel'      => __('Close', 'courier-finance-plugin'),
                                'iconOnly'       => true,
                                'icon'           => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />',
                                'data-bs-dismiss' => 'modal',
                            ]); ?>
                        </div>
                        <div class="modal-body">
                            <?php echo $content; ?>
                        </div>
                    </div>
                </div>
            </div>
            <script>
            jQuery(document).ready(function($) {
                var $modal = $('#<?php echo esc_js($id); ?>');
                $modal.on('shown.bs.modal', function() {
                    $modal.trigger('modal:opened');
                    $(document).trigger('modal:opened', [$modal]);
                });
            });
            </script>
            <?php
            return ob_get_clean();
        }

        // --- Custom modal (admin / Tailwind) ---
        if ($show_button) {
            $button_label = $button_text ?: $title;
            echo KIT_Commons::kitButton([
                'color' => 'blue',
                'modal' => $id,
                'icon'  => 'plus',
            ], $button_label);
        }
        ?>
        <div id="<?php echo esc_attr($id); ?>"
            class="hidden fixed inset-0 z-[99999] items-start pt-10 justify-center bg-black bg-opacity-50 overflow-y-auto">
            <div class="bg-white rounded-lg shadow-xl overflow-y-auto <?php echo esc_attr($size_class); ?> max-h-[90vh] my-8">
                <div class="flex justify-between items-center px-6 py-4 border-b sticky top-0 bg-white z-10">
                    <?php if ($title) : ?>
                        <h3 class="text-lg font-semibold text-gray-800"><?php echo esc_html($title); ?></h3>
                    <?php endif; ?>
                    <?php echo KIT_Commons::renderButton('', 'ghost', 'lg', ['type' => 'button', 'classes' => 'modal-close text-gray-500 hover:text-gray-700 focus:outline-none', 'ariaLabel' => 'Close', 'iconOnly' => true, 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />']); ?>
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
                $(document).on('click', '[data-modal="<?php echo esc_js($id); ?>"]', function(e) {
                    e.preventDefault();
                    modal.removeClass('hidden').addClass('flex');
                    body.addClass('overflow-hidden');
                    modal.trigger('modal:opened');
                    $(document).trigger('modal:opened', [modal]);
                });
                modal.find('.modal-close').on('click', function(e) {
                    e.preventDefault();
                    modal.removeClass('flex').addClass('hidden');
                    body.removeClass('overflow-hidden');
                });
                modal.on('click', function(e) {
                    if (e.target === this) {
                        modal.removeClass('flex').addClass('hidden');
                        body.removeClass('overflow-hidden');
                    }
                });
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
