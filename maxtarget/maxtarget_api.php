<?php
class MaxtargetClient {
    var $mt_version           = 'V0.1';
    var $mt_verbose           = true;
    var $mt_img_dir           = '/banners/';
	var $mt_img_url           = '/banners/';
	var $mt_img_ext			  = '.jpg';
    var $mt_debug             = true;
    var $mt_isrobot           = false;
    var $mt_test              = false;
    var $mt_test_count        = 4;
    var $mt_template          = 'template';
    var $mt_charset           = 'DEFAULT';
    var $mt_use_ssl           = false;
    var $mt_server            = 'db.maxtarget.ru';
	var $mt_server_burl 	  = '/banners/';	
    var $mt_cache_lifetime    = 3600;
    var $mt_cache_reloadtime  = 300;
    var $mt_db_file           = '';
    var $mt_banners           = array();
    var $mt_banners_page      = array();
    var $mt_error             = '';
    var $mt_host              = '';
    var $mt_request_uri       = '';
    var $mt_fetch_remote_type = '';
    var $mt_socket_timeout    = 6;
    var $mt_force_show_code   = false;
    var $mt_multi_site        = false;
    var $mt_is_static         = false;
	var $mt_banners_url       = null;

    function MaxtargetClient($options = null) {
        $host = '';

        if (is_array($options)) {
            if (isset($options['host'])) {
                $host = $options['host'];
            }
        } elseif (strlen($options) != 0) {
            $host = $options;
            $options = array();
        } else {
            $options = array();
        }

        if (strlen($host) != 0) {
            $this->mt_host = $host;
        } else {
            $this->mt_host = $_SERVER['HTTP_HOST'];
        }

        $this->mt_host = preg_replace('{^https?://}i', '', $this->mt_host);
        $this->mt_host = preg_replace('{^www\.}i', '', $this->mt_host);
        $this->mt_host = strtolower( $this->mt_host);

        if (isset($options['is_static']) && $options['is_static']) {
            $this->mt_is_static = true;
        }

        if (isset($options['request_uri']) && strlen($options['request_uri']) != 0) {
            $this->mt_request_uri = $options['request_uri'];
        } else {
            if ($this->mt_is_static) {
                $this->mt_request_uri = preg_replace( '{\?.*$}', '', $_SERVER['REQUEST_URI']);
                $this->mt_request_uri = preg_replace( '{/+}', '/', $this->mt_request_uri);
	    } else {
                $this->mt_request_uri = $_SERVER['REQUEST_URI'];
            }
        }

        $this->mt_request_uri = rawurldecode($this->mt_request_uri);

        if (isset($options['multi_site']) && $options['multi_site'] == true) {
            $this->mt_multi_site = true;
        }

        if ((isset($options['verbose']) && $options['verbose']) ||
            isset($this->mt_links['__mt_debug__'])) {
            $this->mt_verbose = true;
        }
        if (isset($options['debug']) && $options['debug']) {
            $this->mt_debug = true;
        }

        if (isset($options['charset']) && strlen($options['charset']) != 0) {
            $this->mt_charset = $options['charset'];
        }

        if (isset($options['fetch_remote_type']) && strlen($options['fetch_remote_type']) != 0) {
            $this->mt_fetch_remote_type = $options['fetch_remote_type'];
        }

        if (isset($options['socket_timeout']) && is_numeric($options['socket_timeout']) && $options['socket_timeout'] > 0) {
            $this->mt_socket_timeout = $options['socket_timeout'];
        }

        if ((isset($options['force_show_code']) && $options['force_show_code']) ||
            isset($this->mt_links['__mt_debug__'])) {
            $this->mt_force_show_code = true;
        }

        #Cache options
        if (isset($options['use_cache']) && $options['use_cache']) {
            $this->mt_cache = true;
        }

        if (isset($options['cache_clusters']) && $options['cache_clusters']) {
            $this->mt_cache_size = $options['cache_clusters'];
        }

        if (isset($options['cache_dir']) && $options['cache_dir']) {
            $this->mt_cache_dir = $options['cache_dir'];
        }

        if (!defined('MT_USER')) {
            return $this->raise_error("Constant MT_USER is not defined.");
        }

		if (isset($_SERVER['HTTP_TRUSTLINK']) && $_SERVER['HTTP_TRUSTLINK']==MT_USER){
			$this->mt_test=true;
			$this->mt_isrobot=true;
			$this->mt_verbose = true;
		}

        if (isset($_GET['mt_test']) && $_GET['mt_test']==MT_USER){
            $this->mt_force_show_code=true;
			$this->mt_verbose = true;
        }

        $this->load_data();
    }

    function setup_datafile($filename){
        if (!is_file($filename)) {
            if (@touch($filename, time() - $this->mt_cache_lifetime)) {
                @chmod($filename, 0666);
            } else {
                return $this->raise_error("There is no file " . $filename  . ". Fail to create. Set mode to 777 on the folder.");
            }
        }

        if (!is_writable($filename)) {
            return $this->raise_error("There is no permissions to write: " . $filename . "! Set mode to 777 on the folder.");
        }
        return true;
    }

    function load_data() {
        if ($this->mt_multi_site) {
            $this->mt_db_file = dirname(__FILE__) . '/maxtarget.' . $this->mt_host . '.db';
        } else {
            $this->mt_db_file = dirname(__FILE__) . '/maxtarget.db';
        }

        if (!$this->setup_datafile($this->mt_db_file)){return false;}

        //cache
        if (!is_dir(dirname(__FILE__) .'/'.$this->mt_img_dir)) {
            if(!@mkdir(dirname(__FILE__) .'/'.$this->mt_img_dir)){
                return $this->raise_error("There is no dir " . dirname(__FILE__) .'/'.$this->mt_img_dir  . ". Fail to create. Set mode to 777 on the folder."); 
            }
        }
        //check dir rights
        if (!is_writable(dirname(__FILE__) .'/'.$this->mt_img_dir)) {
            return $this->raise_error("There is no permissions to write to dir " . $this->mt_img_dir . "! Set mode to 777 on the folder.");
        }
        

        @clearstatcache();

        //Load data
        if (filemtime($this->mt_db_file) < (time()-$this->mt_cache_lifetime) ||
           (filemtime($this->mt_db_file) < (time()-$this->mt_cache_reloadtime) && filesize($this->mt_db_file) == 0)) {

            @touch($this->mt_db_file, time());

            $path = '/' . MT_USER . '/' . strtolower( $this->mt_host ) . '/' . strtoupper( $this->mt_charset);

            if ($data = $this->fetch_remote_file($this->mt_server, $path)) {
                if (substr($data, 0, 12) == 'FATAL ERROR:' && $this->mt_debug) {
                    $this->raise_error($data);
                } else{
                    if (@unserialize($data) !== false) {
                    $this->lc_write($this->mt_db_file, $data);
                    $this->mt_cache_update = true;
                    } else if ($this->mt_debug) {
                        $this->raise_error("Cans't unserialize received data.");
                    }
                }
            }
        }


        $data = $this->lc_read($this->mt_db_file);


        $this->mt_file_change_date = gmstrftime ("%d.%m.%Y %H:%M:%S",filectime($this->mt_db_file));
        $this->mt_file_size = strlen( $data);

        if (!$data) {
            $this->mt_banners = array();
            if ($this->mt_debug)
                $this->raise_error("Empty file.");
        } else if (!$this->mt_banners = @unserialize($data)) {
            $this->mt_banners = array();
            if ($this->mt_debug)
                $this->raise_error("Can't unserialize data from file.");
        }


		if ($this->mt_test)
		{
        	if (isset($this->mt_banners['__test_tl_link__']) && is_array($this->mt_banners['__test_tl_link__']))
        		for ($i=0;$i<$this->mt_test_count;$i++)
					$this->mt_banners_page[$i]=$this->mt_banners['__test_tl_link__'];
                    if ($this->mt_charset!='DEFAULT'){
                        $this->mt_banners_page[$i]['text']=iconv("UTF-8", $this->mt_charset, $this->mt_banners_page[$i]['text']);
                        $this->mt_banners_page[$i]['anchor']=iconv("UTF-8", $this->mt_charset, $this->mt_banners_page[$i]['anchor']);
                    }
		} else {

            $mt_banners_temp=array();
            foreach($this->mt_banners as $key=>$value){
                $mt_banners_temp[rawurldecode($key)]=$value;
            }
            $this->mt_banners=$mt_banners_temp;
            $this->mt_banners_page=array();
            if (array_key_exists($this->mt_request_uri, $this->mt_banners) && is_array($this->mt_banners[$this->mt_request_uri])) {
                $this->mt_banners_page = array_merge($this->mt_banners_page, $this->mt_banners[$this->mt_request_uri]);
            }
		}

        $this->mt_banners_count = count($this->mt_banners_page);
    }

    function fetch_remote_file($host, $path) {
        $user_agent = 'Maxtarget Client PHP ' . $this->mt_version;

        @ini_set('allow_url_fopen', 1);
        @ini_set('default_socket_timeout', $this->mt_socket_timeout);
        @ini_set('user_agent', $user_agent);

        if (
            $this->mt_fetch_remote_type == 'file_get_contents' || (
                $this->mt_fetch_remote_type == '' && function_exists('file_get_contents') && ini_get('allow_url_fopen') == 1
            )
        ) {
            if ($data = file_get_contents('http://' . $host . $path)) {
                return $data;
            }
        } elseif (
            $this->mt_fetch_remote_type == 'curl' || (
                $this->mt_fetch_remote_type == '' && function_exists('curl_init')
            )
        ) {
            if ($ch = @curl_init()) {
                @curl_setopt($ch, CURLOPT_URL, 'http://' . $host . $path);
                @curl_setopt($ch, CURLOPT_HEADER, false);
                @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                @curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->mt_socket_timeout);
                @curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);

                if ($data = @curl_exec($ch)) {
                    return $data;
                }

                @curl_close($ch);
            }
        } else {
            $buff = '';
            $fp = @fsockopen($host, 80, $errno, $errstr, $this->mt_socket_timeout);
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

        return $this->raise_error("Can't connect to server: " . $host . $path);
    }

    function lc_read($filename) {
        $fp = @fopen($filename, 'rb');
        @flock($fp, LOCK_SH);
        if ($fp) {
            clearstatcache();
            $length = @filesize($filename);
            if (version_compare(PHP_VERSION, '5.3.0', '<')) {
                if(get_magic_quotes_gpc()){
                    $mqr = get_magic_quotes_runtime();
                    set_magic_quotes_runtime(0);
                }
            }
            if ($length) {
                $data = @fread($fp, $length);
            } else {
                $data = '';
            }
            if (version_compare(PHP_VERSION, '5.3.0', '<')) {
                if(isset($mqr)){
                    set_magic_quotes_runtime($mqr);
                }
            }
            @flock($fp, LOCK_UN);
            @fclose($fp);

            return $data;
        }

        return $this->raise_error("Can't get data from the file: " . $filename);
    }

    function lc_write($filename, $data) {
        $fp = @fopen($filename, 'wb');
        if ($fp) {
            @flock($fp, LOCK_EX);
            $length = strlen($data);
            @fwrite($fp, $data, $length);
            @flock($fp, LOCK_UN);
            @fclose($fp);

            if (md5($this->lc_read($filename)) != md5($data)) {
                return $this->raise_error("Integrity was violated while writing to file: " . $filename);
            }

            return true;
        }

        return $this->raise_error("Can't write to file: " . $filename);
    }
	
	function download_banner($id){
		$url = $this->get_banner_mt_url($id);
		$data = $this->fetch_remote_file($this->mt_server, $url);
		if (isset($data) && strlen($data)>0){
			$this->lc_write($this->get_banner_path($id), $data);	
			return $this->get_banner_url($id);
		}else{
			return false;
		}
	}
	
	function check_banner($id){
		$path = $this->get_banner_path($id);
		if (is_file($path) && (filesize($path)>0)) {
			return $this->get_banner_url($id);
		}else{
			return false;
		}
	}
	
	function get_banner_path($id){
		return dirname(__FILE__) . $this->mt_img_dir.$id.$this->mt_img_ext;
	}
	
	function get_banner_url($id){
		$prefix = isset($this->mt_banners_url) ? $this->mt_banners_url : '/'.MT_USER;
		return $prefix.$this->mt_img_dir.$id.$this->mt_img_ext;
	}
	
	function get_banner_mt_url($id){
		return '/' . MT_USER . '/' . strtolower( $this->mt_host ) . $this->mt_server_burl.$id.$this->mt_img_ext;
	}
	
	function fetch_banner($id){
		if($path = $this->check_banner($id)){
			return $path;
		}else{
			return $this->download_banner($id);
		}
	}

    function raise_error($e) {
        $this->mt_error .= '<!--ERROR: ' . $e . '-->';
        return false;
    }

    function show_banner($size = null)
    {

        $banners = $this->mt_banners_page[$size];

    	$result = '';
		$result .= $this->mt_banners['__maxtarget_start__'];
		if (isset($banners) && count($banners)>0){
       		$banner_url = $this->fetch_banner($banners[0]['image']);
			if ($banner_url){	
        		$result .= '<img src = "'.$banner_url.'" />';
			}else{
				$this->raise_error("Can't load banner");
			}
		}
		
        if (isset($this->mt_links['__mt_robots__']) && in_array($_SERVER['REMOTE_ADDR'], $this->mt_links['__mt_robots__']) || $this->mt_verbose) {

            if ($this->mt_error != '' && $this->mt_debug) {
                $result .= $this->mt_error;
            }			
			$result .= $this->mt_banners['__maxtarget_adv'.$size.'__'];
            $result .= '<!--REQUEST_URI=' . $_SERVER['REQUEST_URI'] . "-->\n";
            $result .= "\n<!--\n";
            $result .= 'L ' . $this->mt_version . "\n";
            $result .= 'REMOTE_ADDR=' . $_SERVER['REMOTE_ADDR'] . "\n";
            $result .= 'request_uri=' . $this->mt_request_uri . "\n";
            $result .= 'charset=' . $this->mt_charset . "\n";
            $result .= 'is_static=' . $this->mt_is_static . "\n";
            $result .= 'multi_site=' . $this->mt_multi_site . "\n";
            $result .= 'file change date=' . $this->mt_file_change_date . "\n";
            $result .= 'lc_file_size=' . $this->mt_file_size . "\n";
            $result .= 'n=' . $size . "\n";
			$result .= 'c=' . count($banners) . "\n";
            $result .= 'total=' . $this->mt_banners_count . "\n";			
            $result .= '-->';
        }
        
       	
 
        $result = $result . $this->widget_tag();

        if ($this->mt_test && !$this->mt_isrobot)
        	$result = '<noindex>'.$result.'</noindex>';
		$result .= $this->mt_banners['__maxtarget_end__'];
        return $result;
    }

    function widget_tag() {
      $hash = 'mt' . sha1($this->mt_host);
	  $result = '<script>var mt_cid = '.$this->mt_banners['__maxtarget_counter_id__'].'</script>';
      $result .= '<script async="async" src="http://maxtarget:Cahg2Peesie4@db-stage.maxtarget.ru/v1/mt.js?pid=' . $hash . '" type="text/javascript"></script>';

      return $result;
    }
}
?>
