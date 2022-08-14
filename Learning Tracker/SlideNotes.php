<?php

namespace App\Controllers;

use \Core\Model;
use \App\Models\Notes;
use \App\Auth;
use \Core\View;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * SlideNotes controller
 *
 * PHP version 7.0
 */
class SlideNotes extends AuthenticatedParticipant {

    public function addNotesAction() {

        $topic_id = $_POST['topic_id'];
        $order = $_POST['order'];
        $notes = $_POST['notes'];

        $result = Notes::saveNote($topic_id, $order, $notes);

        if ($result) {
            $response = array(
                "status" => "Success",
                "id" => $result
            );
        } else {
            $response = array(
                "status" => "Fail"
            );
        }

        echo json_encode($response);
    }

    public function updateNotesAction() {

        $topic_id = $_POST['topic_id'];
        $order = $_POST['order'];
        $notes = $_POST['notes'];

        $slide_id = Notes::updateNote($topic_id, $order, $notes);

        $response = array(
            "status" => "Success",
            "id" => $slide_id
        );

        echo json_encode($response);
    }

    public function deleteNoteAction() {

        $topic_id = $_POST['topic_id'];
        $order = $_POST['order'];

        Notes::deleteNote($topic_id, $order);

        $response = array(
            "status" => "Success"
        );

        echo json_encode($response);
    }

    public function sessionNotesAction() {
        
    }

    public function moduleNotesAction() {
        $module_index = $_POST['module_index'];

        $subject_id = $_SESSION['subject_id'];

        $module_notes = Notes::getModuleNotes($module_index, $subject_id);

        $response = array(
            "status" => "Success",
            "module_notes" => $module_notes
        );

        echo json_encode($response);
    }

    /**
     * Generate preview of the pdf for chosen view-type and module
     */
    public function previewModuleNotesAction() {
        $module_index = $this->route_params['moduleindex'];
        $type_index = $this->route_params['typeindex'];
        $subject_id = $_SESSION['subject_id'];

        $module_notes = Notes::getModuleNotes($module_index, $subject_id);

        View::renderTemplate('SlideNotes/notesExport.html',
                [
                    'notes' => $module_notes,
                    'type_index' => $type_index
                ]
        );
    }

    /**
     * Generate & download the pdf file for the chosen view-type and module
     */
    public function exportModuleNotesAction() {
        $module_index = $this->route_params['moduleindex'];
        $type_index = $this->route_params['typeindex'];
        $subject_id = $_SESSION['subject_id'];

        $module_notes = Notes::getModuleNotes($module_index, $subject_id);

        $html = View::returnTemplate('SlideNotes/notesExport.html',
                        [
                            'notes' => $module_notes,
                            'type_index' => $type_index
                        ]
        );

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $contxt = stream_context_create([
            'ssl' => [
                'verify_peer' => FALSE,
                'verify_peer_name' => FALSE,
                'allow_self_signed' => TRUE
            ]
        ]);
        $dompdf->setHttpContext($contxt);

        $dompdf->loadHtml($html);

        // (Optional) Setup the paper size and orientation
        $dompdf->setPaper('A4', 'portrait');

        // Render the HTML as PDF
        $dompdf->render();

        // Output the generated PDF to Browser
        $dompdf->stream();
    }

    /**
     * Generate preview of the pdf for chosen view-type and session
     */
    public function previewSessionNotesAction() {

        $session_index = $this->route_params['sessionindex'];
        $type_index = $this->route_params['typeindex'];
        $course_id = $_SESSION['course_id'];

        $session_notes = Notes::getSessionNotes($session_index, $course_id);

        View::renderTemplate('SlideNotes/notesExport.html',
                [
                    'notes' => $session_notes,
                    'type_index' => $type_index
                ]
        );
    }

    /**
     * Generate & download the pdf file for the chosen view-type and session
     */
    public function exportSessionNotesAction() {

        $session_index = $this->route_params['sessionindex'];
        $type_index = $this->route_params['typeindex'];
        $course_id = $_SESSION['course_id'];
        
        $session_notes = Notes::getSessionNotes($session_index, $course_id);
        $html = View::returnTemplate('SlideNotes/notesExport.html',
                        [
                            'notes' => $session_notes,
                            'type_index' => $type_index
                        ]
        );

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $contxt = stream_context_create([
            'ssl' => [
                'verify_peer' => FALSE,
                'verify_peer_name' => FALSE,
                'allow_self_signed' => TRUE
            ]
        ]);
        $dompdf->setHttpContext($contxt);

        $dompdf->loadHtml($html);

        // (Optional) Setup the paper size and orientation
        $dompdf->setPaper('A4', 'portrait');

        // Render the HTML as PDF
        $dompdf->render();

        // Output the generated PDF to Browser
        $dompdf->stream();
    }

}
