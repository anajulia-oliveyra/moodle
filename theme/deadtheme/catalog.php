<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/course/lib.php');

global $DB, $OUTPUT, $PAGE;

// -----------------------------
// Configuração básica da página
// -----------------------------
$PAGE->set_url(new moodle_url('/theme/deadtheme/catalog.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Catálogo de Cursos');
$PAGE->set_heading('Catálogo de Cursos');

// -----------------------------
// Parâmetros de filtro (via GET)
// -----------------------------
$categoryid = optional_param('category', 0, PARAM_INT);
$search     = optional_param('q', '', PARAM_TEXT);
$page       = optional_param('page', 0, PARAM_INT);
$perpage    = 9;

// -----------------------------
// Busca categorias reais e visíveis do Moodle
// -----------------------------
$categoryrecords = $DB->get_records('course_categories', ['visible' => 1], 'sortorder ASC', 'id, name');

$areas = [];
foreach ($categoryrecords as $cat) {
    $catname = format_string($cat->name);
    $areas[] = [
        'id'     => $cat->id,
        'name'   => $catname,
        'active' => ($categoryid == $cat->id),
        'url'    => (new moodle_url('/theme/deadtheme/catalog.php', ['category' => $cat->id]))->out(false),
    ];
}

$allareasurl = (new moodle_url('/theme/deadtheme/catalog.php'))->out(false);

// -----------------------------
// Monta condições da query de cursos
// -----------------------------
$sqlwhere = 'visible = 1 AND id != 1'; // exclui o "curso" raiz do site (id 1)
$sqlparams = [];

if ($categoryid > 0) {
    $sqlwhere .= ' AND category = :categoryid';
    $sqlparams['categoryid'] = $categoryid;
}

if (!empty($search)) {
    $sqlwhere .= ' AND (' . $DB->sql_like('fullname', ':search1', false) . ' OR ' . $DB->sql_like('summary', ':search2', false) . ')';
    $sqlparams['search1'] = '%' . $DB->sql_like_escape($search) . '%';
    $sqlparams['search2'] = '%' . $DB->sql_like_escape($search) . '%';
}

// -----------------------------
// Conta total (pra paginação)
// -----------------------------
$totalcourses = $DB->count_records_select('course', $sqlwhere, $sqlparams);

// -----------------------------
// Busca os cursos da página atual
// -----------------------------
$courses = $DB->get_records_select(
    'course',
    $sqlwhere,
    $sqlparams,
    'id DESC',
    'id, fullname, shortname, category, summary, summaryformat',
    $page * $perpage,
    $perpage
);

// -----------------------------
// Monta array final com imagem e resumo real
// -----------------------------
$fs = get_file_storage();
$coursecards = [];

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

    // Resumo real do curso, sem tags HTML, cortado em 100 caracteres
    $summarytext = format_text($course->summary, $course->summaryformat, ['context' => $context]);
    $summarytext = trim(strip_tags($summarytext));
    if (mb_strlen($summarytext) > 100) {
        $summarytext = mb_substr($summarytext, 0, 100) . '...';
    }

    // Nome da categoria real do curso (pra mostrar como "etiqueta" no card, opcional)
    $categoryname = '';
    if (isset($categoryrecords[$course->category])) {
        $categoryname = format_string($categoryrecords[$course->category]->name);
    }

    $courseurl = new moodle_url('/theme/deadtheme/course_preview.php', ['id' => $course->id]);

    $coursecards[] = [
        'name'         => format_string($course->fullname),
        'summary'      => $summarytext,
        'category'     => $categoryname,
        'hassummary'   => !empty($summarytext),
        'url'          => $courseurl->out(false),
        'image'        => $imageurl,
    ];
}

// -----------------------------
// Paginação
// -----------------------------
$totalpages = ceil($totalcourses / $perpage);
$pagination = [];
for ($i = 0; $i < $totalpages; $i++) {
    $pagination[] = [
        'number' => $i + 1,
        'active' => ($i == $page),
        'url'    => (new moodle_url('/theme/deadtheme/catalog.php', [
            'category' => $categoryid,
            'q'        => $search,
            'page'     => $i
        ]))->out(false),
    ];
}

// -----------------------------
// Renderiza
// -----------------------------
echo $OUTPUT->header();

echo $OUTPUT->render_from_template('theme_deadtheme/catalog', [
    'areas'         => $areas,
    'allareasurl'   => $allareasurl,
    'showallactive' => ($categoryid == 0),
    'courses'       => $coursecards,
    'totalcourses'  => $totalcourses,
    'search'        => $search,
    'searchurl'     => (new moodle_url('/theme/deadtheme/catalog.php'))->out(false),
    'pagination'    => $pagination,
    'haspagination' => count($pagination) > 1,
]);

echo $OUTPUT->footer();