<?php
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/pdflib.php');

// Récupération des paramètres
$courseid = required_param('id', PARAM_INT); // ID du cours
$categoryid = optional_param('categoryid', 0, PARAM_INT); // ID de la catégorie (optionnel)

// Vérification de l'accès et des permissions
$context = context_course::instance($courseid);
require_login($courseid);
require_capability('gradereport/exportpdf:view', $context);

// Configuration de la page
$PAGE->set_url(new moodle_url('/grade/report/exportpdf/index.php', ['id' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'gradereport_exportpdf'));
$PAGE->set_heading(get_string('pluginname', 'gradereport_exportpdf'));

echo $OUTPUT->header();
echo $OUTPUT->heading('Exporter les carnets de notes');

// Affiche le formulaire ou génère les PDF
if (!$categoryid) {
    display_category_selection_form($courseid);
} else {
    process_export($courseid, $categoryid);
}

echo $OUTPUT->footer();
exit;

/**
 * Affiche le formulaire de sélection des catégories de notes.
 *
 * @param int $courseid L'identifiant du cours.
 */
function display_category_selection_form($courseid) {
    global $DB;

    // Récupération des catégories de notes
    $categories = $DB->get_records('grade_categories', ['courseid' => $courseid]);

    echo '<form method="GET">';
    echo '<input type="hidden" name="id" value="' . $courseid . '">';
    echo '<label for="categoryid">Sélectionnez une catégorie :</label>';
    echo '<select name="categoryid" id="categoryid">';
    foreach ($categories as $category) {
        echo '<option value="' . $category->id . '">' . format_string($category->fullname) . '</option>';
    }
    echo '</select>';
    echo '<button type="submit">Exporter</button>';
    echo '</form>';
}

/**
 * Traite l'export des carnets de notes au format PDF.
 *
 * @param int $courseid L'identifiant du cours.
 * @param int $categoryid L'identifiant de la catégorie de notes.
 */
function process_export($courseid, $categoryid) {
    global $DB, $CFG;

    $context = context_course::instance($courseid);
    $students = get_enrolled_users($context, 'mod/assign:submit');
    $grade_items = $DB->get_records('grade_items', ['categoryid' => $categoryid]);

    $pdf_path = $CFG->tempdir . '/gradebooks/';
    if (!file_exists($pdf_path)) {
        mkdir($pdf_path, 0777, true);
    }

    // Générer un PDF pour chaque élève
    foreach ($students as $student) {
        $filename = generate_student_pdf($pdf_path, $student, $grade_items);
        echo '<li><a href="' . generate_pluginfile_url($context, $filename) . '">Carnet de ' . fullname($student) . '</a></li>';
    }

    // Générer un PDF récapitulatif
    $summary_filename = generate_summary_pdf($pdf_path, $students, $grade_items);
    echo '<li><a href="' . generate_pluginfile_url($context, $summary_filename) . '">PDF récapitulatif</a></li>';
}

/**
 * Génère un PDF pour un étudiant.
 *
 * @param string $pdf_path Chemin pour stocker les PDF.
 * @param object $student Objet utilisateur.
 * @param array $grade_items Items de notes.
 * @return string Nom du fichier PDF généré.
 */
function generate_student_pdf($pdf_path, $student, $grade_items) {
    $pdf = new pdf();
    $pdf->AddPage();
    $pdf->SetFont('Helvetica', '', 12);
    $pdf->Write(0, "Carnet de notes pour : " . fullname($student) . "\n\n");

    foreach ($grade_items as $item) {
        global $DB;
        $grade = $DB->get_record('grade_grades', [
            'itemid' => $item->id,
            'userid' => $student->id
        ]);
        $pdf->Write(0, "{$item->itemname}: " . ($grade ? $grade->finalgrade : '-') . "\n");
    }

    $filename = "grades_{$student->id}.pdf";
    $pdf->Output($pdf_path . $filename, 'F');
    return $filename;
}

/**
 * Génère un PDF récapitulatif pour une catégorie.
 *
 * @param string $pdf_path Chemin pour stocker les PDF.
 * @param array $students Liste des étudiants.
 * @param array $grade_items Items de notes.
 * @return string Nom du fichier PDF récapitulatif généré.
 */
function generate_summary_pdf($pdf_path, $students, $grade_items) {
    $pdf = new pdf();
    $pdf->AddPage();
    $pdf->SetFont('Helvetica', '', 12);
    $pdf->Write(0, "Récapitulatif des notes\n\n");

    foreach ($students as $student) {
        $pdf->Write(0, "Étudiant : " . fullname($student) . "\n");
        foreach ($grade_items as $item) {
            global $DB;
            $grade = $DB->get_record('grade_grades', [
                'itemid' => $item->id,
                'userid' => $student->id
            ]);
            $pdf->Write(0, "{$item->itemname}: " . ($grade ? $grade->finalgrade : '-') . "\n");
        }
        $pdf->Write(0, "\n");
    }

    $filename = 'grades_summary.pdf';
    $pdf->Output($pdf_path . $filename, 'F');
    return $filename;
}

/**
 * Génère une URL sécurisée pour accéder aux fichiers PDF via pluginfile.php.
 *
 * @param context $context Contexte du cours.
 * @param string $filename Nom du fichier PDF.
 * @return moodle_url URL sécurisée pour le fichier.
 */
function generate_pluginfile_url($context, $filename) {
    return moodle_url::make_pluginfile_url(
        $context->id,
        'gradereport_exportpdf',
        'gradebooks',
        0,
        '/',
        $filename
    );
}
