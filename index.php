<?php
require_once(__DIR__ . '/../../../config.php');

// Récupération des paramètres
$courseid = required_param('id', PARAM_INT); // ID du cours

// Contexte et permissions
$context = context_course::instance($courseid);
require_login($courseid);
require_capability('gradereport/exportpdf:view', $context);

// Configuration de la page
$PAGE->set_url(new moodle_url('/grade/report/exportpdf/index.php', ['id' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_title('Exporter les carnets de notes');
$PAGE->set_heading('Exporter les carnets de notes');

echo $OUTPUT->header();
echo $OUTPUT->heading('Exporter les carnets de notes');

// Récupération des catégories d'évaluation
$categories = $DB->get_records('grade_categories', ['courseid' => $courseid]);

if (empty($categories)) {
    echo '<p>Aucune catégorie d\'évaluation disponible pour ce cours.</p>';
    echo $OUTPUT->footer();
    exit;
}

// Affichage du formulaire pour sélectionner une catégorie
echo '<form method="GET" action="eleves.php">';
echo '<input type="hidden" name="id" value="' . $courseid . '">';
echo '<label for="categoryid">Sélectionnez une catégorie d\'évaluation :</label>';
echo '<select name="categoryid" id="categoryid">';
foreach ($categories as $category) {
    echo '<option value="' . $category->id . '">' . format_string($category->fullname) . '</option>';
}
echo '</select>';
echo '<button type="submit">Exporter</button>';
echo '</form>';

echo $OUTPUT->footer();
