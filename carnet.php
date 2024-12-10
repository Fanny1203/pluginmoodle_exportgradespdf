<?php
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/pdflib.php');

// Récupération des paramètres
$courseid = required_param('id', PARAM_INT); // ID du cours
$categoryid = required_param('categoryid', PARAM_INT); // ID de la catégorie sélectionnée
$userid = required_param('userid', PARAM_INT); // ID de l'élève

// Contexte et permissions
$context = context_course::instance($courseid);
require_login($courseid);
require_capability('gradereport/exportpdf:view', $context);

// Récupération des informations de l'élève
$student = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

// Récupération des items de notes pour la catégorie sélectionnée
$grade_items = $DB->get_records('grade_items', ['categoryid' => $categoryid]);

// Génération du PDF
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

// Générer le nom du fichier avec le nom de l'élève
$filename = "carnet_" . clean_filename(fullname($student)) . ".pdf";

// Envoi du PDF au navigateur
$pdf->Output($filename, 'I'); // 'I' pour afficher directement dans le navigateur
