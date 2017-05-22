<?php
define('MAXTARGET_COMPILED', 1);
?>
<?php

class Options {
    var $options = null;

    function __construct($options = array()) {
        if (is_array($options))
            $this->options = $options;
    }

    function __set($name, $value) {
        $this->options[$name] = $value;
    }

    function __isset($name) {
        return isset($this->options[$name]);
    }

    function __get($name) {
        if (isset($this->options[$name]))
            return $this->options[$name];
        else
            return null;
    }
}
?>
<?php

class Settings extends Options {
    var $defaults = array(
        'img_dir' => '/../banners/',
        'img_url' => '/banners/',
        'img_ext' => '.png',
        'test_count' => 4,
        'template' => 'template',
        'server_burl' => '/banners/',
        'server_furl' => '/favicons/',
        'cache_lifetime' => 3600,
        'cache_reloadtime' => 300,
        'db_file' => '',
        'banners' => array(),
        'banners_page' => array(),
        'error' => '',
        'socket_timeout' => 6,
        'banners_url' => null,
        'templates' => array(),
        'server' => 'cdn.maxtarget.ru',
        'charset' => 'DEFAULT');

    function encode_path($path) {
        $path = rawurldecode($path);
        $encoded_path = array_map('urlencode', explode('/', $path));
        return implode('/', $encoded_path);
    }

    function __construct($options = array()) {
        parent::__construct(array_merge($this->defaults, $options));

        if (!isset($this->host))
            $this->host = $_SERVER['HTTP_HOST'];

        $this->host = preg_replace('{^https?://}i', '', $this->host);
        $this->host = preg_replace('{^www\.}i', '', $this->host);
        $this->host = strtolower($this->host);

        if (strlen($this->request_uri) == 0)
            $this->request_uri = $_SERVER['REQUEST_URI'];

        if ($this->is_static) {
            $this->request_uri = preg_replace('{\?.*$}', '', $this->request_uri);
            $this->request_uri = preg_replace('{/+}', '/', $this->request_uri);
        }

        $this->request_uri = $this->encode_path($this->request_uri);

        if ((isset($_SERVER['HTTP_TRUSTLINK']) && $_SERVER['HTTP_TRUSTLINK'] == MT_USER) || (isset($_GET['mt_test']) && $_GET['mt_test'] == MT_USER)) {
            $this->test = true;
            $this->force_show_code = true;
            $this->isrobot = true;
            $this->verbose = true;
            $this->debug = true;
        }
    }
}
?>
<?php

class MtBasic {

    var $options = null;

    function __construct(&$options = null) {
        $this->options = $options;
        if (empty($this->options))
            $this->options = new Settings();
    }
}
?>
<?php

class Templater extends MtBasic {
    var $templates = array();
    var $reserved = array('template');
    var $img_template = '<a href="%url%"><image src="%image_url%" /></a>';

    function load_templates($tpl = array()) {
        $this->templates = $tpl;
    }

    function render($data) {
        $vars = array();
        $vals = array();
        $default_template = $this->options->mt->storage->size_default_template;
        $res = '';

        if (is_array($data))
            foreach ($data as $adv) {
                if (is_array($adv) && is_array($adv['data'])) {
                    foreach ($adv['data'] as $k => $v) {
                        if (in_array($k, $this->reserved))
                            continue;
                        if ($k == 'url') {
                            $vars[] = '%' . $k . '%';
                            $vals[] = $v;
                            $vars[] = '%url_domain%';
                            $vals[] = parse_url($v, PHP_URL_HOST);
                        }
                        elseif ($k == 'image') {
                            $vars[] = '%image_url%';
                            $vals[] = $this->options->mt->banner->load_banner($v);
                        }
                        elseif ($k == 'favicon_url') {
                            $vars[] = '%favicon_url%';
                            $vals[] = $this->options->mt->icon->load_icon($v);
                        }
                        else {
                            $vars[] = '%' . $k . '%';
                            $vals[] = $v;
                        }
                    }

                    $template = array_key_exists('template', $adv) ? $adv['template'] : null;
                    if (is_null($template))
                        $template = $default_template;

                    if (($template == -1) || is_null($template))
                        $tpl = $this->img_template;
                    else
                        $tpl = $this->templates[$template];

                    $res .= str_replace($vars, $vals, $tpl);
                }
            }
        return $res;
    }
}
?>
<?php

class Renderer extends MtBasic {
    var $templater = null;
    var $widget_count = 1;

    function render($storage, $errors = null, $stat = null, $user_code = null) {
        //TODO
        //$banners = $this->mt_banners_page[$size];

        $adv_size = 'adv' . $storage->size;
        $user_code_key = 'adv' . $storage->size . '_user_code';

        $result = '';
        $result .= "\n<!-- ";
        $result .= $storage->config->start;
        $result .= " -->\n<!-- ";
        $result .= $storage->config->$adv_size;
        $result .= " -->\n";

        //TODO
        if (false && isset($banners) && count($banners) > 0) {
            $banner_url = $this->fetch_banner($banners[0]['image']);
            if ($banner_url) {
                $result .= '<img src = "' . $banner_url . '" />';
            }
            else {
                $this->raise_error("Can't load banner");
            }
        }

        if (is_array($storage->config->robots) && in_array($_SERVER['REMOTE_ADDR'], $storage->config->robots) || $this->options->verbose) {

            if (!empty($errors) && $this->options->debug) {
                $result .= "\n<!-- ";
                $result .= implode("\n", $errors);
                $result .= " -->\n";
            }

            $result .= '<!--REQUEST_URI=' . $_SERVER['REQUEST_URI'] . "-->\n";
            $result .= "\n<!--\n";
            $result .= 'VER = ' . $this->options->version . "\n";
            $result .= 'REMOTE_ADDR=' . $_SERVER['REMOTE_ADDR'] . "\n";
            $result .= 'request_uri=' . $this->options->request_uri . "\n";
            $result .= 'charset=' . $this->options->charset . "\n";
            $result .= 'is_static=' . $this->options->is_static . "\n";
            $result .= 'multi_site=' . $this->options->multi_site . "\n";
            $info = $storage->info();
            $result .= 'file change date=' . $info['date'] . "\n";
            $result .= 'file_size=' . $info['size'] . "\n";
            $result .= 'banner_size =' . $storage->size . "\n";
            //$result .= 'c=' . count($banners) . "\n";
            //$result .= 'total=' . $this->mt_banners_count . "\n";
            $result .= "-->\n";
        }

        $res_banner = $this->templater->render($storage->page_data);
        if (empty($res_banner))
            $res_banner = $user_code;
        if (empty($res_banner))
            $res_banner = $storage->config->$user_code_key;

        $result .= $res_banner;
        $result .= $this->widget_tag($storage);

        if ($this->options->test && !$this->options->isrobot)
            $result = '<noindex>' . $result . '</noindex>';
        $result .= "\n<!-- ";
        $result .= $storage->config->end;
        $result .= " -->\n";
        return $result;
    }

    function render_error($errors = null) {
        $result = '';
        if (!empty($errors) && $this->options->debug) {
            $result .= "\n<!-- ";
            $result .= implode("\n", $errors);
            $result .= " -->\n";
        }
        return $result;
    }

    function widget_tag($storage) {
        if ($this->widget_count < 1)
            return '';
        $this->widget_count = $this->widget_count - 1;
        $hash = 'st' . sha1($this->options->host);
        $cid = empty($storage->config->counter_id) ? -1 : $storage->config->counter_id;
        $result = "\n<script>var mt_cid = " . $cid . '</script>';
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) {
            $result .= "\n" . '<script async="async" src="https://adcounter' . rand(1, 19) . '.uptolike.com/counter.js?sid=' . $hash . '" type="text/javascript"></script>';
        }
        else {
            $result .= "\n" . '<script async="async" src="http://adcounter' . rand(1, 19) . '.uptolike.ru/counter.js?sid=' . $hash . '" type="text/javascript"></script>';
        }

        return $result;
    }
}
?>
<?php

class Stat extends MtBasic {

    function get_stat() {
        $e = array();
        $i = array();
        foreach (get_loaded_extensions() as $i => $ext)
            $e[] = $ext . ' => ' . phpversion($ext) . "\n";
        $i = ini_get_all(null, true);
        $uname = php_uname();
        $sapi = php_sapi_name();
    }
}
?>
<?php

class Serializer extends MtBasic {
    var $data = null;
    var $config = array();
    var $page_data = array();
    var $size = null;
    var $size_default_template = null;
    var $fsize = 0;

    function __construct(&$options = array()) {
        parent::__construct($options);
        if (!isset($this->options->storage))
            $this->options->storage = array('file_path' => dirname(__FILE__) . '/mt.data');
    }

    function info() {
        $date = gmstrftime("%d.%m.%Y %H:%M:%S", filectime($this->options->storage['file_path']));
        return array('size' => $this->fsize, 'date' => $date);
    }

    function extract_options() {
        $this->config = new Options($this->get_section('config'));
    }

    function load_data() {
        if (filemtime($this->options->storage['file_path']) < (time() - $this->options->cache_lifetime) || (filemtime($this->options->storage['file_path']) < (time() - $this->options->cache_reloadtime) && filesize($this->options->storage['file_path']) == 0)) {
            $this->data = null;
            $this->fsize = 0;
        }
        else {
            $data = $this->read_data($this->options->storage['file_path']);
            $this->fsize = strlen($data);
            $this->data = unserialize($data);
            $this->extract_options();
        }
    }

    function assign_data($data, $size = 0) {
        $this->data = $data;
        $this->fsize = $size;
        $this->extract_options();
    }

    function has_data() {
        //Trying to load data
        if (empty($this->data) && $this->setup_datafile($this->options->storage['file_path']))
            $this->load_data();

        return !empty($this->data);
    }

    function save_data($data = null) {
        if (!empty($data))
            $this->write_data($this->options->storage['file_path'], serialize($data));
    }

    function save_raw_data($data = null) {
        if (!empty($data))
            $this->write_data($this->options->storage['file_path'], $data);
    }

    function set_size($size) {
        $this->size = $size;
        $data = $this->get_section($this->size, $this->get_section('data'));
        if (array_key_exists('template', $data)) {
            $this->size_default_template = $data['template'];
        }
    }

    function get_page_data($uri = '/') {
        return $this->get_section($uri, $this->get_section($this->size, $this->get_section('data')));
    }

    function get_section($section, $data = null) {
        if (empty($data))
            $data = $this->data;
        if (!empty($data) && !empty($section) && is_array($data) && isset($data[$section]))
            return $data[$section];
        else
            return array();
    }

    function switch_page($size, $uri = '/') {
        $this->set_size($size);
        $this->page_data = $this->get_page_data($uri);
    }


    function setup_datafile($filename) {
        if (!is_file($filename)) {
            if (@touch($filename, time() - $this->options->cache_lifetime)) {
                @chmod($filename, 0666);
            }
            else {
                throw new Exception("There is no file " . $filename . ". Fail to create. Set mode to 777 on the folder.");
            }
        }

        if (!is_writable($filename)) {
            throw new Exception("There is no permissions to write: " . $filename . "! Set mode to 777 on the folder.");
        }
        return true;
    }

    function read_data($filename) {
        $fp = @fopen($filename, 'rb');
        @flock($fp, LOCK_SH);
        if ($fp) {
            clearstatcache();
            $length = @filesize($filename);
            if (version_compare(PHP_VERSION, '5.3.0', '<')) {
                if (get_magic_quotes_gpc()) {
                    $mqr = get_magic_quotes_runtime();
                    set_magic_quotes_runtime(0);
                }
            }
            if ($length) {
                $data = @fread($fp, $length);
            }
            else {
                $data = '';
            }
            if (version_compare(PHP_VERSION, '5.3.0', '<')) {
                if (isset($mqr)) {
                    set_magic_quotes_runtime($mqr);
                }
            }
            @flock($fp, LOCK_UN);
            @fclose($fp);

            return $data;
        }

        throw new Exception("Can't get data from the file: " . $filename);
    }

    function write_data($filename, $data) {
        $fp = @fopen($filename, 'wb');
        if ($fp) {
            @flock($fp, LOCK_EX);
            //$length = strlen($data);
            //@fwrite($fp, $data, $length);
            @fwrite($fp, $data);
            @flock($fp, LOCK_UN);
            @fclose($fp);

            if (md5($this->read_data($filename)) != md5($data))
                throw new Exception("Integrity was violated while writing to file: " . $filename);

            return true;
        }
        //@touch($filename, time());

        throw new Exception("Can't write to file: " . $filename);
    }
}
?>
<?php

class Banner extends MtBasic {

    function file_name($name) {
        return $name . $this->options->img_ext;
    }

    function web_image_url($name) {
        return $this->options->img_url . $this->file_name($name);
    }

    function image_dir() {
        return $this->options->main_dir . $this->options->img_dir;
    }

    function image_path($name) {
        return $this->image_dir() . $this->file_name($name);
    }

    function load_banner($name) {
        if (!file_exists($this->image_path($name))) {
            $data = $this->options->mt->network->download_banner($this->file_name($name));
            if (!empty($data))
                $this->options->mt->storage->write_data($this->image_path($name), $data);

        }
        return $this->web_image_url($name);
    }

    function check_image_dir() {
        if (!file_exists($this->image_dir()))
            mkdir($this->image_dir(), 0777, true);
    }
}
?>
<?php

class Icon extends MtBasic {

    function file_name($name) {
        return $name;
    }

    function web_image_url($name) {
        return $this->options->img_url . $this->file_name($name);
    }

    function image_dir() {
        return $this->options->main_dir . $this->options->img_dir;
    }

    function image_path($name) {
        return $this->image_dir() . $this->file_name($name);
    }

    function load_icon($name) {
        if (!file_exists($this->image_path($name))) {
            $data = $this->options->mt->network->download_icon($this->file_name($name));
            if (!empty($data))
                $this->options->mt->storage->write_data($this->image_path($name), $data);

        }
        return $this->web_image_url($name);
    }

    function check_image_dir() {
        if (!file_exists($this->image_dir()))
            mkdir($this->image_dir(), 0777, true);
    }

}
?>
<?php

class Fetcher extends MtBasic {

    function setup_headers() {
        $h['X-MT-VER'] = $this->options->version;
        $h['X-MT-LANGVER'] = "PHP/" . phpversion();
        return $h;
    }

    function fetch_remote_file($host, $path) {

        $user_agent = 'Maxtarget Client PHP ' . $this->options->version . '/' . phpversion();

        @ini_set('allow_url_fopen', 1);
        @ini_set('default_socket_timeout', $this->options->socket_timeout);
        @ini_set('user_agent', $user_agent);

        if ($this->options->fetch_remote_type == 'file_get_contents' || ($this->options->fetch_remote_type == null && function_exists('file_get_contents') && ini_get('allow_url_fopen') == 1)) {
            if ($data = file_get_contents('http://' . $host . $path))
                return $data;
        }
        elseif ($this->options->mt_fetch_remote_type == 'curl' || ($this->options->mt_fetch_remote_type == null && function_exists('curl_init'))) {
            if ($ch = @curl_init()) {
                @curl_setopt($ch, CURLOPT_URL, 'http://' . $host . $path);
                @curl_setopt($ch, CURLOPT_HEADER, false);
                @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                @curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, round($this->options->socket_timeout / 2));
                @curl_setopt($ch, CURLOPT_TIMEOUT, round($this->options->socket_timeout / 2));
                @curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);

                if ($data = @curl_exec($ch)) {
                    return $data;
                }

                @curl_close($ch);
            }
        }
        else {
            $buff = '';
            $fp = @fsockopen($host, 80, $errno, $errstr, $this->options->socket_timeout);
            if ($fp) {
                @fputs($fp, "GET {$path} HTTP/1.0\r\nHost: {$host}\r\n");
                @fputs($fp, "User-Agent: {$user_agent}\r\n\r\n");
                while (!@feof($fp)) {
                    $buff .= @fgets($fp, 128);
                }
                @fclose($fp);

                $page = explode("\r\n\r\n", $buff);

                return $page[1];
            }
        }

        throw new Exception("Can't connect to server: " . $host . $path);
    }

    function download_data() {
        if ($this->options->fetch_remote_type == 'local')
            return file_get_contents($this->options->local_datafile_path);

        $path = '/' . $this->options->key . '/' . strtolower($this->options->host) . '/' . strtoupper($this->options->charset);

        if ($data = $this->fetch_remote_file($this->options->server, $path)) {
            if (substr($data, 0, 12) == 'FATAL ERROR:')
                throw new Exception($data);
            else
                return $data;
        }
        else
            throw new Exception('Can not download datafile');
    }

    function download_banner($file_name) {
        if ($this->options->fetch_remote_type == 'local')
            return file_get_contents($this->options->local_banner_folder . $file_name);

        $path = '/' . $this->options->key . '/' . strtolower($this->options->host) . '/' . $this->options->server_burl . '/' . $file_name;

        if ($data = $this->fetch_remote_file($this->options->server, $path)) {
            if (substr($data, 0, 12) == 'FATAL ERROR:')
                throw new Exception($data);
            else
                return $data;
        }
        else
            throw new Exception('Can not download banner' . $file_name);
    }

    function download_icon($file_name) {
        if ($this->options->fetch_remote_type == 'local')
            return file_get_contents($this->options->local_icon_folder . $file_name);

        $path = $this->options->server_furl . $file_name;

        if ($data = $this->fetch_remote_file($this->options->server, $path)) {
            if (substr($data, 0, 12) == 'FATAL ERROR:')
                throw new Exception($data);
            else
                return $data;
        }
        else
            throw new Exception('Can not download icon' . $file_name);
    }
}
?>
<?php

class MaxtargetCore {
    var $settings = null;
    var $renderer = null;
    var $stat = null;
    var $storage = null;
    var $banner = null;
    var $icon = null;
    var $network = null;
    var $errors = array();

    function __construct(&$settings) {
        $this->settings = $settings;
        $this->settings->version = '1.0.9';
    }

    function error($err) {
        $this->errors[] = $err;
    }

    function has_errors() {
        return count($this->errors) > 0;
    }

    function show_banner($size = null, $user_code = null) {
        try {
            if (empty($this->settings->key))
                throw new Exception("Key is not defined.");

            if (!$this->storage->has_data()) {
                $data = $this->network->download_data();
                $this->storage->save_raw_data($data);
                $this->storage->assign_data(unserialize($data), strlen($data));
            }

            $this->renderer->templater->load_templates($this->storage->config->templates);
            $this->storage->switch_page($size, $this->settings->request_uri);
            $this->banner->check_image_dir();

        } catch (Exception $e) {
            $this->error($e->getMessage());
        }

        try {
            return $this->renderer->render($this->storage, $this->errors, $this->stat->get_stat(), $user_code);
        } catch (Exception $e) {
            return $this->renderer->render_error(array($e->getMessage()));
        }
    }
}
?>
<?php

if (!defined('MAXTARGET_COMPILED')) {
    require_once 'settings/options.php';
    require_once 'settings/settings.php';
    require_once 'basic.php';
    require_once 'render/templater/templater.php';
    require_once 'render/renderer.php';
    require_once 'statistics/stat.php';
    require_once 'storage/serializer.php';
    require_once 'storage/banner.php';
    require_once 'storage/icon.php';
    require_once 'network/fetcher.php';
    require_once 'MaxtargetCore.php';
}

class MaxtargetClient {
    var $maxtarget = null;

    function __construct($options = null) {
        $settings = new Settings($options);
        $settings->main_dir = dirname(__FILE__);
        $this->maxtarget = new MaxtargetCore($settings);
        $settings->mt = $this->maxtarget;
        $this->maxtarget->stat = new Stat($settings);
        $this->maxtarget->renderer = new Renderer($settings);
        $this->maxtarget->renderer->templater = new Templater($settings);
        $this->maxtarget->storage = new Serializer($settings);
        $this->maxtarget->banner = new Banner($settings);
        $this->maxtarget->icon = new Icon($settings);
        $this->maxtarget->network = new Fetcher($settings);
    }

    //Proxying calls
    public function __call($name, $arguments) {
        return call_user_func_array(array($this->maxtarget, $name), $arguments);
    }
}
?>