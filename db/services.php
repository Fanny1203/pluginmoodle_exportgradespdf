<?php
defined('MOODLE_INTERNAL') || die();

$functions = []; // Si aucune fonction web service n'est utilisée.

$services = []; // Si aucun service spécifique n'est défini.

$fileareas = [
    'gradebooks' => [ // Zone de fichiers appelée "gradebooks".
        'contextlevel' => CONTEXT_COURSE, // Contexte des cours.
        'component' => 'gradereport_exportpdf', // Le composant du plugin.
    ],
];