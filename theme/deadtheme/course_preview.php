<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/course/lib.php');
use core_course\customfield\course_handler;

global $DB, $OUTPUT, $PAGE;

$id = required_param('id', PARAM_INT);

$course = $DB->get_record('course', ['id' => $id, 'visible' => 1], '*', MUST_EXIST);
$context = context_course::instance($course->id);

// -----------------------------
// Configuração da página
// -----------------------------
$PAGE->set_url(new moodle_url('/theme/deadtheme/course_preview.php', ['id' => $id]));
$PAGE->set_context(context_system::instance()); // contexto system, pra não exigir matrícula
$PAGE->set_pagelayout('standard');
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));

$fs = get_file_storage();
$imageurl = '';

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

// -----------------------------
// Categoria
// -----------------------------
$categoryname = '';
$categoryrecord = $DB->get_record('course_categories', ['id' => $course->category]);
if ($categoryrecord) {
    $categoryname = format_string($categoryrecord->name);
}

// -----------------------------
// Descrição completa
// -----------------------------
$summary = format_text($course->summary, $course->summaryformat, ['context' => $context]);

$handler = course_handler::create();
$customfields = $handler->get_instance_data($course->id);

$cargahoraria = '';
$publicoalvo = '';

foreach ($customfields as $data) {
    $field = $data->get_field();
    $shortname = $field->get('shortname');
    $value = $data->export_value();

    if ($shortname === 'cargahoraria') {
        $cargahoraria = $value;
    }
    if ($shortname === 'publicoalvo') {
        $publicoalvo = $value;
    }
}

$sections = $DB->get_records_select(
    'course_sections',
    'course = :courseid AND visible = 1 AND section > 0',
    ['courseid' => $course->id],
    'section ASC',
    'id, section, name, summary'
);

$topics = [];
foreach ($sections as $section) {
    // Se o professor não deu nome customizado, usa "Tópico X" como padrão
    $sectionname = !empty($section->name) ? format_string($section->name) : 'Tópico ' . $section->section;
    $topics[] = ['name' => $sectionname];
}

// -----------------------------
// Verifica se o usuário já está logado/matriculado
// (só pra decidir o texto do botão)
// -----------------------------
$isenrolled = false;
if (isloggedin() && !isguestuser()) {
    $isenrolled = is_enrolled($context, $USER);
}

$enrolurl = new moodle_url('/course/view.php', ['id' => $course->id]);

// -----------------------------
// Renderiza
// -----------------------------
echo $OUTPUT->header();

echo $OUTPUT->render_from_template('theme_deadtheme/course_preview', [
    'coursename'    => format_string($course->fullname),
    'category'      => $categoryname,
    'hascategory'   => !empty($categoryname),
    'image'         => $imageurl,
    'hasimage'      => !empty($imageurl),
    'summary'       => $summary,
    'topics'        => $topics,
    'hastopics'     => count($topics) > 0,
    'enrolurl'      => $enrolurl->out(false),
    'buttontext'    => $isenrolled ? 'Acessar curso' : 'Matricular-se',
    'cargahoraria'    => $cargahoraria,
    'hascargahoraria' => !empty($cargahoraria),
    'publicoalvo'     => $publicoalvo,
    'haspublicoalvo'  => !empty($publicoalvo),
]);

echo $OUTPUT->footer();