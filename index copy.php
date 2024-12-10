<?php
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/pdflib.php');

require_login();

// Récupération des paramètres
$courseid = required_param('id', PARAM_INT); // ID du cours
$categoryid = optional_param('categoryid', 0, PARAM_INT); // ID de la catégorie (optionnel)

// Contexte et permissions
$context = context_course::instance($courseid);
require_capability('local/export_gradebook_pdf:view', $context); // Vérification des permissions

// Page setup
$PAGE->set_url(new moodle_url('/local/export_gradebook_pdf/index.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_title('Exporter les carnets de notes');
$PAGE->set_heading('Exporter les carnets de notes');

echo $OUTPUT->header();
echo $OUTPUT->heading('Exporter les carnets de notes');

// Récupérer les catégories de notes pour affichage dans un formulaire
if (!has_capability('moodle/grade:viewall', $context)) {
    throw new moodle_exception('nopermissiontoviewgrades');
}
$categories = $DB->get_records('grade_categories', ['id' => $courseid]);

// Formulaire pour sélectionner une catégorie
if (!$categoryid) {
    echo '<form method="GET">';
    echo '<input type="hidden" name="id" value="' . $courseid . '">';
    echo '<label for="categoryid">Sélectionnez une catégorie :</label>';
    echo '<select name="categoryid" id="categoryid">';
    foreach ($categories as $cat) {
        echo '<option value="' . $cat->id . '">' . $cat->fullname . '</option>';
    }
    echo '</select>';
    echo '<button type="submit">Exporter</button>';
    echo '</form>';
    echo $OUTPUT->footer();
    exit;
}

// Récupération des notes des élèves pour la catégorie sélectionnée
$grade_items = $DB->get_records('grade_items', ['categoryid' => $categoryid]);
$students = get_enrolled_users($context, 'mod/assign:submit');

// Génération des PDF
$pdf_path = $CFG->tempdir . '/gradebooks/';
if (!file_exists($pdf_path)) {
    mkdir($pdf_path, 0777, true);
}

// Générer un PDF par élève
foreach ($students as $student) {
    $pdf = new pdf();
    $pdf->AddPage();
    $pdf->SetFont('Helvetica', '', 12);
    $pdf->Write(0, "Carnet de notes pour : " . fullname($student) . "\n\n");

    foreach ($grade_items as $item) {
        $grade = $DB->get_record('grade_grades', [
            'itemid' => $item->id,
            'userid' => $student->id
        ]);
        $pdf->Write(0, "{$item->itemname}: " . ($grade ? $grade->finalgrade : '-') . "\n");
    }

    $filename = $pdf_path . "grades_{$student->id}.pdf";
    $pdf->Output($filename, 'F');

    $context = context_course::instance($courseid); // Contexte du cours
    $filename = "grades_{$student->id}.pdf";
    


}

// Générer le PDF récapitulatif
$pdf_summary = new pdf();
$pdf_summary->AddPage();
$pdf_summary->SetFont('Helvetica', '', 12);
$pdf_summary->Write(0, "Récapitulatif des notes pour la catégorie : {$categoryid}\n\n");

foreach ($students as $student) {
    $pdf_summary->Write(0, "Étudiant : " . fullname($student) . "\n");
    foreach ($grade_items as $item) {
        $grade = $DB->get_record('grade_grades', [
            'itemid' => $item->id,
            'userid' => $student->id
        ]);
        $pdf_summary->Write(0, "  {$item->itemname}: " . ($grade ? $grade->finalgrade : '-') . "\n");
    }
    $pdf_summary->Write(0, "\n");
}

$summary_filename = $pdf_path . 'grades_summary.pdf';
$pdf_summary->Output($summary_filename, 'F');

// Affichage des liens pour télécharger les PDF
echo '<h3>Export terminé</h3>';
echo '<ul>';
foreach ($students as $student) {
    echo '<li><a href="' . $CFG->wwwroot . '/temp/gradebooks/grades_' . $student->id . '.pdf">Carnet de ' . fullname($student) . '</a></li>';
}
echo '<li><a href="' . $CFG->wwwroot . '/temp/gradebooks/grades_summary.pdf">PDF récapitulatif</a></li>';
echo '</ul>';

echo $OUTPUT->footer();
