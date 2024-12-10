<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Injecte un élément dans le menu du carnet de notes (Gradebook).
 */
function local_export_gradebook_pdf_extend_navigation_course($navigation, $course, $context) {
    // Vérifie si l'utilisateur a les permissions nécessaires.
    if (!has_capability('local/export_gradebook_pdf:view', $context)) {
        return;
    }

    // Recherche le nœud du carnet de notes.
    $gradebooknode = $navigation->find('grades', navigation_node::TYPE_SETTING);

    if ($gradebooknode) {
        // Ajoute un sous-élément pointant vers le plugin.
        $url = new moodle_url('/local/export_gradebook_pdf/index.php', ['courseid' => $course->id]);
        $gradebooknode->add(
            get_string('exportgrades', 'local_export_gradebook_pdf'),
            $url,
            navigation_node::NODETYPE_LEAF,
            null,
            'exportgradebookpdf'
        );
    }
}

