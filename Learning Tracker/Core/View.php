<?php

namespace Core;

/**
 * View
 *
 * PHP version 7.0
 */
class View {

    /**
     * Render a view file
     *
     * @param string $view  The view file
     * @param array $args  Associative array of data to display in the view (optional)
     *
     * @return void
     */
    public static function render($view, $args = []) {
        extract($args, EXTR_SKIP);

        $file = dirname(__DIR__) . "/App/Views/$view";  // relative to Core directory

        if (is_readable($file)) {
            require $file;
        } else {
            throw new \Exception("$file not found");
        }
    }

    /**
     * Render a view template using Twig
     *
     * @param string $template  The template file
     * @param array $args  Associative array of data to display in the view (optional)
     *
     * @return void
     */
    public static function renderTemplate($template, $args = []) {
        foreach ($_SESSION as $session_item) {
            array_push($args, $session_item);
        }
        echo static::getTemplate($template, $args);
    }
    
    public static function returnTemplate($template, $args = []) {
        foreach ($_SESSION as $session_item) {
            array_push($args, $session_item);
        }
        return static::getTemplate($template, $args);
    }

    /**
     * Get the contents of a view template using Twig
     *
     * @param string $template  The template file
     * @param array $args  Associative array of data to display in the view (optional)
     *
     * @return string
     */
    public static function getTemplate($template, $args = []) {
        static $twig = null;

        if ($twig === null) {
            $loader = new \Twig_Loader_Filesystem(dirname(__DIR__) . '/App/Views');
            $twig = new \Twig_Environment($loader);
            $twig->addGlobal('current_user', \App\Auth::getUser());
            $twig->addGlobal('flash_messages', \App\Flash::getMessages());
        }

        return $twig->render($template, $args);
    }
    
    /**
     * New function that takes template as a string variable and return after merging with arguments
     * @param type $template
     * @param type $args
     * @return type
     */
    public static function returnTemplateFromString($template_string, $args = []) {
        $env = new \Twig_Environment(new \Twig_Loader_Array(array()));
        $template = $env->createTemplate($template_string);
        return $template->render($args);
    }

}
