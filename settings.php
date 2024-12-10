<?php
defined('MOODLE_INTERNAL') || die();

$ADMIN->add('gradereports', new admin_externalpage(
    'gradereport_exportpdf',
    get_string('pluginname', 'gradereport_exportpdf'),
    new moodle_url('/grade/report/exportpdf/index.php'),
    'gradereport/exportpdf:view'
));
