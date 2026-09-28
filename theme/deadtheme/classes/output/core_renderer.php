<?php
namespace theme_deadtheme\output;

defined('MOODLE_INTERNAL') || die();

class core_renderer extends \theme_boost\output\core_renderer {

    public function render_frontpage_slider() {
        $data = [
            'slides' => [
                ['image' => $this->image_url('slide1', 'theme')->out(), 'alt' => 'Slide 1', 'active' => true],
                ['image' => $this->image_url('slide2', 'theme')->out(), 'alt' => 'Slide 2', 'active' => false],
            ],
        ];
        return $this->render_from_template('theme_deadtheme/frontpage_slider', $data);
    }
}