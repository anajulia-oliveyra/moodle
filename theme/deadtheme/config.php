<?php
defined('MOODLE_INTERNAL') || die();

$THEME->name = 'deadtheme';
$THEME->parents = ['boost'];
$THEME->sheets = [];
$THEME->editor_sheets = [];
$THEME->scss = function($theme) {
    return theme_deadtheme_get_main_scss_content($theme);
};
$THEME->extrascsscallback = 'theme_deadtheme_get_extra_scss';
$THEME->prescsscallback = 'theme_deadtheme_get_pre_scss';
$THEME->layouts = [
    'frontpage' => [
        'file' => 'frontpage.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
        'options' => ['nonavbar' => true],
    ],
];
$THEME->enable_dock = false;
$THEME->yuicssmodules = [];
$THEME->rendererfactory = 'theme_overridden_renderer_factory';
$THEME->requiredblocks = '';
$THEME->addblockposition = BLOCK_ADDBLOCK_POSITION_FLATNAV;
$THEME->iconsystem = \core\output\icon_system::FONTAWESOME;