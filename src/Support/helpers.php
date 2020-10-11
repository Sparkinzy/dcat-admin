<?php

use Dcat\Admin\Admin;
use Dcat\Admin\Support\Helper;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\MessageBag;

if (! function_exists('admin_setting')) {
    /**
     * @param string|array $key
     * @param mixed         $default
     *
     * @return \Dcat\Admin\Support\Setting|mixed
     */
    function admin_setting($key = null, $default = [])
    {
        if ($key === null) {
            return app('admin.setting');
        }

        if (is_array($key)) {
            app('admin.setting')->save($key);

            return;
        }

        return app('admin.setting')->get($key, $default);
    }
}

if (! function_exists('admin_extension_setting')) {
    /**
     * @param string       $extension
     * @param string|array $key
     * @param mixed        $default
     *
     * @return mixed
     */
    function admin_extension_setting($extension, $key = null, $default = [])
    {
        $extension = app($extension);

        if ($extension instanceof Dcat\Admin\Extend\ServiceProvider) {
            return $extension->config($key, $default);
        }
    }
}

if (! function_exists('admin_section')) {
    /**
     * Get the string contents of a section.
     *
     * @param string $section
     * @param mixed  $default
     * @param array  $options
     *
     * @return mixed
     */
    function admin_section(string $section, $default = null, array $options = [])
    {
        return app('admin.sections')->yieldContent($section, $default, $options);
    }
}

if (! function_exists('admin_has_section')) {
    /**
     * Check if section exists.
     *
     * @param string $section
     *
     * @return mixed
     */
    function admin_has_section(string $section)
    {
        return app('admin.sections')->hasSection($section);
    }
}

if (! function_exists('admin_inject_section')) {
    /**
     * Injecting content into a section.
     *
     * @param string $section
     * @param mixed  $content
     * @param bool   $append
     * @param int    $priority
     */
    function admin_inject_section(string $section, $content = null, bool $append = true, int $priority = 10)
    {
        app('admin.sections')->inject($section, $content, $append, $priority);
    }
}

if (! function_exists('admin_inject_section_if')) {
    /**
     * Injecting content into a section.
     *
     * @param mixed  $condition
     * @param string $section
     * @param mixed  $content
     * @param bool   $append
     * @param int    $priority
     */
    function admin_inject_section_if($condition, $section, $content = null, bool $append = false, int $priority = 10)
    {
        if ($condition) {
            app('admin.sections')->inject($section, $content, $append, $priority);
        }
    }
}

if (! function_exists('admin_has_default_section')) {
    /**
     * Check if default section exists.
     *
     * @param string $section
     *
     * @return mixed
     */
    function admin_has_default_section(string $section)
    {
        return app('admin.sections')->hasDefaultSection($section);
    }
}

if (! function_exists('admin_inject_default_section')) {
    /**
     * Injecting content into a section.
     *
     * @param string                              $section
     * @param string|Renderable|Htmlable|callable $content
     */
    function admin_inject_default_section(string $section, $content)
    {
        app('admin.sections')->injectDefault($section, $content);
    }
}

if (! function_exists('admin_trans_field')) {
    /**
     * Translate the field name.
     *
     * @param $field
     * @param null $locale
     *
     * @return array|\Illuminate\Contracts\Translation\Translator|null|string
     */
    function admin_trans_field($field, $locale = null)
    {
        $slug = admin_controller_slug();

        return admin_trans("{$slug}.fields.{$field}", [], $locale);
    }
}

if (! function_exists('admin_trans_label')) {
    /**
     * Translate the label.
     *
     * @param $label
     * @param array $replace
     * @param null  $locale
     *
     * @return array|\Illuminate\Contracts\Translation\Translator|null|string
     */
    function admin_trans_label($label = null, $replace = [], $locale = null)
    {
        $label = $label ?: admin_controller_name();
        $slug = admin_controller_slug();

        return admin_trans("{$slug}.labels.{$label}", $replace, $locale);
    }
}

if (! function_exists('admin_trans_option')) {
    /**
     * Translate the field name.
     *
     * @param $field
     * @param array $replace
     * @param null  $locale
     *
     * @return array|\Illuminate\Contracts\Translation\Translator|null|string
     */
    function admin_trans_option($optionValue, $field, $replace = [], $locale = null)
    {
        $slug = admin_controller_slug();

        return admin_trans("{$slug}.options.{$field}.{$optionValue}", $replace, $locale);
    }
}

if (! function_exists('admin_trans')) {
    /**
     * Translate the given message.
     *
     * @param string $key
     * @param array  $replace
     * @param string $locale
     *
     * @return \Illuminate\Contracts\Translation\Translator|string|array|null
     */
    function admin_trans($key, $replace = [], $locale = null)
    {
        static $method = null;

        if ($method === null) {
            $method = version_compare(app()->version(), '6.0', '>=') ? 'get' : 'trans';
        }

        $translator = app('translator');

        if ($translator->has($key)) {
            return $translator->$method($key, $replace, $locale);
        }
        if (
            mb_strpos($key, 'global.') !== 0
            && count($arr = explode('.', $key)) > 1
        ) {
            unset($arr[0]);
            array_unshift($arr, 'global');
            $key = implode('.', $arr);

            if (! $translator->has($key)) {
                return end($arr);
            }

            return $translator->$method($key, $replace, $locale);
        }

        return last(explode('.', $key));
    }
}

if (! function_exists('admin_controller_slug')) {
    /**
     * @return string
     */
    function admin_controller_slug()
    {
        static $slug = [];

        $controller = admin_controller_name();

        return $slug[$controller] ?? ($slug[$controller] = Helper::slug($controller));
    }
}

if (! function_exists('admin_controller_name')) {
    /**
     * Get the class "basename" of the current controller.
     *
     * @return string
     */
    function admin_controller_name()
    {
        static $name = [];

        $router = app('router');

        if (! $router->current()) {
            return 'undefined';
        }

        $actionName = $router->current()->getActionName();

        if (! isset($name[$actionName])) {
            $controller = class_basename(explode('@', $actionName)[0]);

            $name[$actionName] = str_replace('Controller', '', $controller);
        }

        return $name[$actionName];
    }
}

if (! function_exists('admin_path')) {

    /**
     * Get admin path.
     *
     * @param string $path
     *
     * @return string
     */
    function admin_path($path = '')
    {
        return ucfirst(config('admin.directory')).($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}

if (! function_exists('admin_url')) {
    /**
     * Get admin url.
     *
     * @param string $path
     * @param mixed  $parameters
     * @param bool   $secure
     *
     * @return string
     */
    function admin_url($path = '', $parameters = [], $secure = null)
    {
        if (url()->isValidUrl($path)) {
            return $path;
        }

        $secure = $secure ?: (config('admin.https') || config('admin.secure'));

        return url(admin_base_path($path), $parameters, $secure);
    }
}

if (! function_exists('admin_base_path')) {
    /**
     * Get admin url.
     *
     * @param string $path
     *
     * @return string
     */
    function admin_base_path($path = '')
    {
        $prefix = '/'.trim(config('admin.route.prefix'), '/');

        $prefix = ($prefix == '/') ? '' : $prefix;

        $path = trim($path, '/');

        if (is_null($path) || strlen($path) == 0) {
            return $prefix ?: '/';
        }

        return $prefix.'/'.$path;
    }
}

if (! function_exists('admin_toastr')) {
    /**
     * Flash a toastr message bag to session.
     *
     * @param string $message
     * @param string $type
     * @param array $options
     */
    function admin_toastr($message = '', $type = 'success', $options = [])
    {
        $toastr = new MessageBag(get_defined_vars());

        session()->flash('dcat-admin-toastr', $toastr);
    }
}

if (! function_exists('admin_success')) {

    /**
     * Flash a success message bag to session.
     *
     * @param string $title
     * @param string $message
     */
    function admin_success($title, $message = '')
    {
        admin_info($title, $message, 'success');
    }
}

if (! function_exists('admin_error')) {

    /**
     * Flash a error message bag to session.
     *
     * @param string $title
     * @param string $message
     */
    function admin_error($title, $message = '')
    {
        admin_info($title, $message, 'error');
    }
}

if (! function_exists('admin_warning')) {

    /**
     * Flash a warning message bag to session.
     *
     * @param string $title
     * @param string $message
     */
    function admin_warning($title, $message = '')
    {
        admin_info($title, $message, 'warning');
    }
}

if (! function_exists('admin_info')) {

    /**
     * Flash a message bag to session.
     *
     * @param string $title
     * @param string $message
     * @param string $type
     */
    function admin_info($title, $message = '', $type = 'info')
    {
        $message = new MessageBag(get_defined_vars());

        session()->flash($type, $message);
    }
}

if (! function_exists('admin_asset')) {
    /**
     * @param $path
     *
     * @return string
     */
    function admin_asset($path)
    {
        return Dcat\Admin\Admin::asset()->url($path);
    }
}

if (! function_exists('admin_assets_require')) {

    /**
     * @param $alias
     *
     * @return void
     */
    function admin_assets_require(?string $alias)
    {
        Admin::asset()->collect($alias);
    }
}

if (! function_exists('admin_api_route')) {

    /**
     * @param string $path
     *
     * @return string
     */
    function admin_api_route(?string $path = '')
    {
        return Dcat\Admin\Admin::app()->getCurrentApiRoutePrefix().$path;
    }
}

if (! function_exists('admin_extension_path')) {
    /**
     * @param string|null $path
     *
     * @return string
     */
    function admin_extension_path(?string $path = null)
    {
        $dir = rtrim(config('admin.extension.dir'), '/') ?: base_path('dcat-admin-extensions');

        $path = ltrim($path, '/');

        return $path ? $dir.'/'.$path : $dir;
    }
}

if (! function_exists('admin_color')) {
    /**
     * @param string|null $color
     *
     * @return string|\Dcat\Admin\Color
     */
    function admin_color(?string $color = null)
    {
        if ($color === null) {
            return Admin::color();
        }

        return Admin::color()->get($color);
    }
}
