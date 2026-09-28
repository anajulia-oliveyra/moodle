<?php
defined('MOODLE_INTERNAL') || die();

function theme_deadtheme_get_main_scss_content($theme) {
    global $CFG;
    return file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/default.scss');
}

function theme_deadtheme_get_pre_scss($theme) {
    return '';
}

function theme_deadtheme_get_extra_scss($theme) {
    $content = '';
    $filename = $theme->dir . '/scss/post.scss';
    if (file_exists($filename)) {
        $content .= file_get_contents($filename);
    }
    return $content;
}