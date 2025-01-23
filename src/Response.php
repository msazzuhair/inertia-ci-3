<?php

namespace Inertia;

use CI_Controller;
use CI_Input;
use CI_Output;

class Response
{
    protected $viewData = [];
    protected $component;
    protected $props;
    protected $rootView;
    protected $version;

    private CI_Controller $CI;

    public function __construct($component, $props, $rootView = 'app', $version = null)
    {
        $this->CI =& get_instance();
        $this->CI->load->helper('url');

        $this->component = $component;
        $this->props = $props;
        $this->rootView = $rootView;
        $this->version = $version;
    }

    public function with($key, $value = null): Response
    {
        if (is_array($key)) {
            $this->props = array_merge($this->props, $key);
        } else {
            $this->props[$key] = $value;
        }

        return $this;
    }

    public function withViewData($key, $value = null)
    {
        if (is_array($key)) {
            $this->viewData = array_merge($this->viewData, $key);
        } else {
            $this->viewData[$key] = $value;
        }

        return $this;
    }

    public function __toString()
    {
        $partialData = $this->request()->get_header('X-Inertia-Partial-Data');
        $only = array_filter(
            explode(',', $partialData ? $partialData->getValue() : '')
        );

        $partialComponent = $this->request()->get_header('X-Inertia-Partial-Component');
        $props = ($only && ($partialComponent ? $partialComponent->getValue() : '') === $this->component)
            ? array_only($this->props, $only)
            : $this->props;

        array_walk_recursive($props, static function (&$prop) {
            $prop = closure_call($prop);
        });

        $page = [
            'component' => $this->component,
            'props' => $props,
            'url' => uri_string() !== '/' ?  '/'. uri_string() : '/',
            'version' => $this->version,
        ];

        return $this->make($page);
    }

    private function make($page): string
    {
        $request_headers = getallheaders();
        if (is_array($request_headers)) {
            $request_headers = array_change_key_case($request_headers);
        }
        $inertia = $this->CI->input->getHeader('x-inertia');

        if (isset($request_headers['x-inertia']) && $request_headers['x-inertia'] === 'true') {
            $this->CI->output->set_header('Vary: Accept');
            $this->CI->output->set_header('x-inertia: true');
            $this->CI->output->set_header('Content-Type: application/json');

            return json_encode($page);
        }

        return $this->view($page);
    }

    private function view($page): string
    {
        return $this->CI->load->view(
            $this->rootView,
            array_merge($this->viewData, ['page' => $page]),
            true
        );
    }

    private function request(): CI_Input
    {
        return  $this->CI->input;
    }

    private function response(): CI_Output
    {
        return  $this->CI->output;
    }
}
