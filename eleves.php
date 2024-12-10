<?php
require_once(__DIR__ . '/../../../config.php');

// Récupération des paramètres
$courseid = required_param('id', PARAM_INT); // ID du cours
$categoryid = required_param('categoryid', PARAM_INT); // ID de la catégorie sélectionnée

// Contexte et permissions
$context = context_course::instance($courseid);
require_login($courseid);
require_capability('gradereport/exportpdf:view', $context);

// Récupération du nom de la catégorie
$category = $DB->get_record('grade_categories', ['id' => $categoryid], '*', MUST_EXIST);

// Configuration de la page
$PAGE->set_url(new moodle_url('/grade/report/exportpdf/eleves.php', ['id' => $courseid, 'categoryid' => $categoryid]));
$PAGE->set_context($context);
$PAGE->set_title('Exporter les carnets de notes');
$PAGE->set_heading('Exportation des notes de la catégorie : ' . format_string($category->fullname));

echo $OUTPUT->header();
echo $OUTPUT->heading('Exportation des notes de la catégorie : ' . format_string($category->fullname));

// Récupérer les élèves inscrits
$students = get_enrolled_users($context, 'mod/assign:submit');

if (empty($students)) {
    echo '<p>Aucun élève inscrit dans ce cours.</p>';
    echo $OUTPUT->footer();
    exit;
}

// Affichage de la liste des élèves avec un bouton pour chaque export
echo '<ul>';
foreach ($students as $student) {
    $url = new moodle_url('/grade/report/exportpdf/carnet.php', [
        'id' => $courseid,
        'categoryid' => $categoryid,
        'userid' => $student->id
    ]);
    echo '<li>' . fullname($student) . ' - <a target="_blank" href="' . $url . '">Exporter</a></li>';
}
echo '</ul>';

echo $OUTPUT->footer();
