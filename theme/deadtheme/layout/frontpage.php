<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * A drawer based layout for the boost theme.
 *
 * @package   theme_boost
 * @copyright 2021 Bas Brands
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/behat/lib.php');
require_once($CFG->dirroot . '/course/lib.php');

// Add block button in editing mode.
$addblockbutton = $OUTPUT->addblockbutton();

if (isloggedin()) {
    $courseindexopen = (get_user_preferences('drawer-open-index', true) == true);
    $blockdraweropen = (get_user_preferences('drawer-open-block') == true);
} else {
    $courseindexopen = false;
    $blockdraweropen = false;
}

if (defined('BEHAT_SITE_RUNNING') && get_user_preferences('behat_keep_drawer_closed') != 1) {
    $blockdraweropen = true;
}

$extraclasses = ['uses-drawers'];
if ($courseindexopen) {
    $extraclasses[] = 'drawer-open-index';
}

$blockshtml = $OUTPUT->blocks('side-pre');
$hasblocks = (strpos($blockshtml, 'data-block=') !== false || !empty($addblockbutton));
if (!$hasblocks) {
    $blockdraweropen = false;
}
$courseindex = core_course_drawer();
if (!$courseindex) {
    $courseindexopen = false;
}

$bodyattributes = $OUTPUT->body_attributes($extraclasses);
$forceblockdraweropen = $OUTPUT->firstview_fakeblocks();

$secondarynavigation = false;
$overflow = '';
if ($PAGE->has_secondary_navigation()) {
    $tablistnav = $PAGE->has_tablist_secondary_navigation();
    $moremenu = new \core\navigation\output\more_menu($PAGE->secondarynav, 'nav-tabs', true, $tablistnav);
    $secondarynavigation = $moremenu->export_for_template($OUTPUT);
    $overflowdata = $PAGE->secondarynav->get_overflow_menu_data();
    if (!is_null($overflowdata)) {
        $overflow = $overflowdata->export_for_template($OUTPUT);
    }
}

$primary = new core\navigation\output\primary($PAGE);
$renderer = $PAGE->get_renderer('core');
$primarymenu = $primary->export_for_template($renderer);
$buildregionmainsettings = !$PAGE->include_region_main_settings_in_header_actions() && !$PAGE->has_secondary_navigation();
// If the settings menu will be included in the header then don't add it here.
$regionmainsettingsmenu = $buildregionmainsettings ? $OUTPUT->region_main_settings_menu() : false;

$header = $PAGE->activityheader;
$headercontent = $header->export_for_template($renderer);

$slides = [
    [
        'image' => $CFG->wwwroot . '/theme/deadtheme/pix/slide1.png',
        'alt' => 'Inscrições abertas para os cursos livres da UFVJM',
        'active' => true
    ],
    [
        'image' => $CFG->wwwroot . '/theme/deadtheme/pix/slide2.png',
        'alt' => 'Conheça nossos cursos de graduação e pós-graduação',
        'active' => false
    ],
    [
        'image' => $CFG->wwwroot . '/theme/deadtheme/pix/slide3.jpg',
        'alt' => 'Formas de ingressar',
        'active' => false
    ]
];

$frontpageslider = $OUTPUT->render_from_template(
    'theme_deadtheme/frontpage_slider',
    [
        'slides' => $slides
    ]
);

$areas = [
    ['name' => 'Tecnologia', 'url' => $CFG->wwwroot . '/theme/deadtheme/catalog.php?category=1'],
    ['name' => 'Agronomia', 'url' => $CFG->wwwroot . '/theme/deadtheme/catalog.php?category=7'],
    ['name' => 'Saúde', 'url' => $CFG->wwwroot . '/theme/deadtheme/catalog.php?category=12'],
    ['name' => 'Alimentos', 'url' => $CFG->wwwroot . '/theme/deadtheme/catalog.php?category=4'],
    ['name' => 'Meio Ambiente', 'url' => $CFG->wwwroot . '/theme/deadtheme/catalog.php?category=11'],
    ['name' => 'Direito','url' => $CFG->wwwroot . '/theme/deadtheme/catalog.php?category=8'],
    ['name' => 'Arquitetura', 'url' => $CFG->wwwroot . '/theme/deadtheme/catalog.php?category=10'],
    ['name' => 'Comércio', 'url' => $CFG->wwwroot . '/theme/deadtheme/catalog.php?category=9'],
];

global $DB;

$courses = $DB->get_records(
    'course',
    ['visible' => 1],
    'id DESC',
    'id, fullname, shortname, category, summary',
    0,
    3
);

$featuredCourses = [];

$fs = get_file_storage();

foreach ($courses as $course) {

    $imageurl = '';

    $context = context_course::instance($course->id);

    $files = $fs->get_area_files(
        $context->id,
        'course',
        'overviewfiles',
        false,
        'filename',
        false
    );

    foreach ($files as $file) {

        if (!$file->is_directory()) {

            $imageurl = moodle_url::make_pluginfile_url(
                $context->id,
                'course',
                'overviewfiles',
                null,
                $file->get_filepath(),
                $file->get_filename()
            )->out(false);

            break;
        }
    }

    $courseurl = new moodle_url(
        '/theme/deadtheme/course_preview.php',
        ['id' => $course->id]
    );


    $summarytext = strip_tags(format_text($course->summary, FORMAT_HTML, ['context' => $context]));
    $summarytext = trim(preg_replace('/\s+/', ' ', $summarytext));
    // ALTERADO: mb_* para não cortar caractere acentuado no meio.
    if (mb_strlen($summarytext) > 100) {
        $summarytext = mb_substr($summarytext, 0, 100) . '...';
    }

    $featuredCourses[] = [
        'name' => format_string($course->fullname),
        'url' => $courseurl->out(false),
        'category' => $course->category,
        'image' => $imageurl,
        'summary' => $summarytext   // ALTERADO: antes não era enviado ao template.
    ];
}

$catalogurl = new moodle_url('/theme/deadtheme/catalog.php');

$frontpageareas = $OUTPUT->render_from_template(
    'theme_deadtheme/frontpage_areas',
    [
        'areas' => $areas,
        'featuredCourses' => $featuredCourses,
        'catalogurl' => $catalogurl->out(false)
    ]
);

$templatecontext = [
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
    'output' => $OUTPUT,
    'sidepreblocks' => $blockshtml,
    'hasblocks' => $hasblocks,
    'bodyattributes' => $bodyattributes,
    'courseindexopen' => $courseindexopen,
    'blockdraweropen' => $blockdraweropen,
    'courseindex' => $courseindex,
    'primarymoremenu' => $primarymenu['moremenu'],
    'secondarymoremenu' => $secondarynavigation ?: false,
    'mobileprimarynav' => $primarymenu['mobileprimarynav'],
    'usermenu' => $primarymenu['user'],
    'langmenu' => $primarymenu['lang'],
    'forceblockdraweropen' => $forceblockdraweropen,
    'regionmainsettingsmenu' => $regionmainsettingsmenu,
    'hasregionmainsettingsmenu' => !empty($regionmainsettingsmenu),
    'overflow' => $overflow,
    'headercontent' => $headercontent,
    'addblockbutton' => $addblockbutton,
    'frontpageslider' => $frontpageslider,
    'frontpageareas' => $frontpageareas,
    'featuredCourses' => $featuredCourses,
    'catalogmenulink' => $catalogurl->out(false)
];

echo $OUTPUT->render_from_template('theme_deadtheme/drawers', $templatecontext);