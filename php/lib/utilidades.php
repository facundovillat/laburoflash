<?php
function es_ajax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function json_responder($payload) {
    header('Content-Type: application/json');
    echo json_encode($payload);
}
?>