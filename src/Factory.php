<?php

namespace Inertia;

class Factory
{
    private \CI_Controller $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->helper('url');
    }

    /**
     * @var array
     */
    protected array $sharedProps = [];

    /**
     * @var string
     */
    protected string $rootView = 'app';

    /**
     * @var mixed
     */
    protected mixed $version = null;

    /**
     * @param string $name
     *
     * @return void
     */
    public function setRootView(string $name): void
    {
        $this->rootView = $name;
    }

    /**
     * @param $key
     * @param null $value
     *
     * @return void
     */
    public function share($key, $value = null): void
    {
        if (is_array($key)) {
            $this->sharedProps = array_merge($this->sharedProps, $key);
        } else {
            array_set($this->sharedProps, $key, $value);
        }
    }

    /**
     * @param null $key
     * @return array
     */
    public function getShared($key = null): array
    {
        if ($key) {
            return array_get($this->sharedProps, $key);
        }

        return $this->sharedProps;
    }

    /**
     * @param $version
     *
     * @return void
     */
    public function version($version): void
    {
        $this->version = $version;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return (string) closure_call($this->version);
    }

    /**
     * @param $component
     * @param array $props
     *
     */
    public function render($component, array $props = [])
    {
        $request_headers = getallheaders();
        if (isset($request_headers['X-Inertia']) && $request_headers['X-Inertia'] === 'true') {
            $this->CI->output
                ->set_content_type('application/json')
                ->set_header('X-Inertia: true')
                ->set_output(json_encode([
                    'component' => $component,
                    'url' => '/' . uri_string(),
                    'props' => $props,
                    'version' => $this->getVersion()
                ]));
        } else {
            $this->CI->load
                ->view('app.php', array_merge(['page' => [
                    'component' => $component,
                    'url' => '/' . uri_string(),
                    'props' => $props,
                    'version' => $this->getVersion()
                ]]));
        }
    }

    /**
     * @param $page
     *
     * @return string
     */
    public function app($page): string
    {
        return '<div id="app" data-page="' . htmlentities(json_encode($page)) . '"></div>';
    }

    /**
     * @param $uri
     * @return void
     */
    public function redirect($uri): void
    {
        redirect($uri, code: 303);
    }

    public function location($url)
    {
//        return BaseResponse::make('', 409, ['X-Inertia-Location' => $url]);
    }
}
