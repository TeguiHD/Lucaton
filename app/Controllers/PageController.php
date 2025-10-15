<?php
class PageController {
    public function faq() {
        $current_page = 'help_center';
        $page_context = 'faq';
        include VIEWS_PATH . '/public/faq.php';
    }

    public function terms() {
        $current_page = 'terms';
        include VIEWS_PATH . '/public/terms.php';
    }

    public function privacy() {
        $current_page = 'privacy';
        include VIEWS_PATH . '/public/privacy.php';
    }

    public function cookies() {
        $current_page = 'cookies';
        include VIEWS_PATH . '/public/cookies.php';
    }

    public function codeOfConduct() {
        $current_page = 'code_of_conduct';
        include VIEWS_PATH . '/public/codigo-de-conducta.php';
    }

    public function status() {
        $current_page = 'system_status';
        include VIEWS_PATH . '/public/estado-del-sistema.php';
    }

    public function report() {
        $current_page = 'report_issue';
        include VIEWS_PATH . '/public/reportar-problema.php';
    }

    public function contact() {
        $current_page = 'contact';
        include VIEWS_PATH . '/public/contacto.php';
    }

    public function help() {
        $current_page = 'help_center';
        $page_context = 'help';
        include VIEWS_PATH . '/public/centro-de-ayuda.php';
    }
}
?>
