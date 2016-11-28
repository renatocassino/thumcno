<?php

namespace Tacnoman;

//These are now disabled by default because the file sizes of PNGs (and GIFs) are much smaller than we used to generate.
//They only work for PNGs. GIFs and JPEGs are not affected.
if (!defined('OPTIPNG_ENABLED')) {
    define('OPTIPNG_ENABLED', false);
}
if (!defined('OPTIPNG_PATH')) {
    define('OPTIPNG_PATH', '/usr/bin/optipng');
} //This will run first because it gives better compression than pngcrush.
if (!defined('PNGCRUSH_ENABLED')) {
    define('PNGCRUSH_ENABLED', false);
}
if (!defined('PNGCRUSH_PATH')) {
    define('PNGCRUSH_PATH', '/usr/bin/pngcrush');
} //This will only run if OPTIPNG_PATH is not set or is not valid
if (!defined('WEBSHOT_ENABLED')) {
    define('WEBSHOT_ENABLED', false);
}            //Beta feature. Adding webshot=1 to your query string will cause the script to return a browser screenshot rather than try to fetch an image.
if (!defined('WEBSHOT_CUTYCAPT')) {
    define('WEBSHOT_CUTYCAPT', '/usr/local/bin/CutyCapt');
} //The path to CutyCapt.
if (!defined('WEBSHOT_XVFB')) {
    define('WEBSHOT_XVFB', '/usr/bin/xvfb-run');
}        //The path to the Xvfb server
if (!defined('WEBSHOT_SCREEN_X')) {
    define('WEBSHOT_SCREEN_X', '1024');
}            //1024 works ok
if (!defined('WEBSHOT_SCREEN_Y')) {
    define('WEBSHOT_SCREEN_Y', '768');
}            //768 works ok
if (!defined('WEBSHOT_COLOR_DEPTH')) {
    define('WEBSHOT_COLOR_DEPTH', '24');
}            //I haven't tested anything besides 24
if (!defined('WEBSHOT_IMAGE_FORMAT')) {
    define('WEBSHOT_IMAGE_FORMAT', 'png');
}            //png is about 2.5 times the size of jpg but is a LOT better quality
if (!defined('WEBSHOT_TIMEOUT')) {
    define('WEBSHOT_TIMEOUT', '20');
}            //Seconds to wait for a webshot
if (!defined('WEBSHOT_USER_AGENT')) {
    define('WEBSHOT_USER_AGENT', 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-GB; rv:1.9.2.18) Gecko/20110614 Firefox/3.6.18');
} //I hate to do this, but a non-browser robot user agent might not show what humans see. So we pretend to be Firefox
if (!defined('WEBSHOT_JAVASCRIPT_ON')) {
    define('WEBSHOT_JAVASCRIPT_ON', true);
}            //Setting to false might give you a slight speedup and block ads. But it could cause other issues.
if (!defined('WEBSHOT_JAVA_ON')) {
    define('WEBSHOT_JAVA_ON', false);
}            //Have only tested this as fase
if (!defined('WEBSHOT_PLUGINS_ON')) {
    define('WEBSHOT_PLUGINS_ON', true);
}            //Enable flash and other plugins
if (!defined('WEBSHOT_PROXY')) {
    define('WEBSHOT_PROXY', '');
}                //In case you're behind a proxy server.
if (!defined('WEBSHOT_XVFB_RUNNING')) {
    define('WEBSHOT_XVFB_RUNNING', false);
}            //ADVANCED: Enable this if you've got Xvfb running in the background.

// If ALLOW_EXTERNAL is true and Thumcno::$params['allow_external_sites'] is false, then external images will only be fetched from these domains and their subdomains.
if (!isset($ALLOWED_SITES)) {
    $ALLOWED_SITES = array(
        'flickr.com',
        'staticflickr.com',
        'picasa.com',
        'img.youtube.com',
        'upload.wikimedia.org',
        'photobucket.com',
        'imgur.com',
        'imageshack.us',
        'tinypic.com',
    );
}
// -------------------------------------------------------------
// -------------- STOP EDITING CONFIGURATION HERE --------------
// -------------------------------------------------------------

class ThumcnoServer
{
    public $src = '';
    public $is404 = false;
    public $docRoot = '';
    public $lastURLError = false;
    public $localImage = '';
    public $localImageMTime = 0;
    public $url = false;
    public $myHost = '';
    public $isURL = false;
    protected $cachefile = '';
    protected $errors = array();
    protected $toDeletes = array();
    protected $cacheDirectory = '';
    protected $startTime = 0;
    protected $lastBenchTime = 0;
    protected $cropTop = false;
    protected $salt = '';
    protected $filePrependSecurityBlock = "<?php die('Execution denied!'); //"; //Designed to have three letter mime type, space, question mark and greater than symbol appended. 6 bytes total.
    protected static $curlDataWritten = 0;
    protected static $curlFH = false;

    public static function start()
    {
        $tim = new self();
        try {
            $tim->configThumcno();
            $tim->tryBrowserCache();
            $tim->tryServerCache();
            $tim->run();
        } catch (\Exception $e) {
            $tim->handleErrors();
        }

        $tim->handleErrors();
    }

    public function configThumcno()
    {
        global $ALLOWED_SITES;
        $config = Config::getInstance();
        $this->startTime = microtime(true);
        date_default_timezone_set('UTC');
        Logger::info('Starting new request from '.$this->getIP().' to '.$_SERVER['REQUEST_URI']);
        $this->calcDocRoot();
        $this->setSalt();
        $this->setCacheDirectory();
        $this->cleanCache();
        $this->setHostVars();

        if ($this->ifPermittedByExternalReferer()) {
            $this->blockByExternalReferer();
        }

        $this->validateExternalImage();
        $this->setCacheNameFile();
    }
    public function __destruct()
    {
        foreach ($this->toDeletes as $del) {
            Logger::debug("Deleting temp file $del");
            @unlink($del);
        }
    }

    /**
     * Create salt for request.
     * On windows systems I'm assuming fileinode returns an empty string or a number that doesn't change. Check this.
     */
    protected function setSalt()
    {
        $this->salt = @filemtime(__FILE__).'-'.@fileinode(__FILE__);
        Logger::info('Salt is: '.$this->salt);
    }

    /**
     * Set cache directory, create and permission. If does not setted, use the tmp dir in SO.
     */
    protected function setCacheDirectory()
    {
        $config = Config::getInstance();
        if ($config->appConfigs['file_cache_directory']) {
            if (!is_dir($config->appConfigs['file_cache_directory'])) {
                @mkdir($config->appConfigs['file_cache_directory']);
                if (!is_dir($config->appConfigs['file_cache_directory'])) {
                    $this->error('Could not create the file cache directory.');

                    return false;
                }
            }
            $this->cacheDirectory = $config->appConfigs['file_cache_directory'];
            if (!touch($this->cacheDirectory.'/index.html')) {
                $this->error('Could not create the index.html file - to fix this create an empty file named index.html file in the cache directory.');
            }
        } else {
            $this->cacheDirectory = sys_get_temp_dir();
        }
    }

    /**
     * If block_external_leechers permit external referer and the request has a referer.
     */
    public function ifPermittedByExternalReferer()
    {
        $config = Config::getInstance();

        return $config->appConfigs['block_external_leechers'] && array_key_exists('HTTP_REFERER', $_SERVER) && (!preg_match('/^https?:\/\/(?:www\.)?'.$this->myHost.'(?:$|\/)/i', $_SERVER['HTTP_REFERER']));
    }

    protected function blockByExternalReferer()
    {
        // base64 encoded red image that says 'no hotlinkers'
        // nothing to worry about! :)
        $imgData = base64_decode("R0lGODlhUAAMAIAAAP8AAP///yH5BAAHAP8ALAAAAABQAAwAAAJpjI+py+0Po5y0OgAMjjv01YUZ\nOGplhWXfNa6JCLnWkXplrcBmW+spbwvaVr/cDyg7IoFC2KbYVC2NQ5MQ4ZNao9Ynzjl9ScNYpneb\nDULB3RP6JuPuaGfuuV4fumf8PuvqFyhYtjdoeFgAADs=");
        header('Content-Type: image/gif');
        header('Content-Length: '.strlen($imgData));
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: '.gmdate('D, d M Y H:i:s', time()));
        echo $imgData;
        Logger::error("Blocking image by referer: {$_SERVER['HTTP_REFERER']}");
        throw new \Exception('Error by referer');
    }

    /**
     * Set host vars (src, isURL and url).
     */
    public function setHostVars()
    {
        $config = Config::getInstance();
        if (!isset($config->urlParams['src'])) {
            $this->error('Not found `src` param');
        }

        $this->myHost = preg_replace('/^www\./i', '', $config->domain);
        $src = $config->urlParams['src'];
        $src = preg_replace('/^(\/?(\.+)\/)+/', '/', $src);

        if (preg_match('/^https?:\/\/[^\/]+/i', $src)) {
            Logger::info('Is a request for an external URL: '.$this->src);
            $this->isURL = true;
            $this->src = $src;
        } else {
            Logger::info('Is a request for an internal file: '.$this->src);
            $this->src = $config->appConfigs['path_images'].'/'.$src;
        }

        $this->url = parse_url($this->src);
        $this->src = preg_replace('/https?:\/\/(?:www\.)?'.$this->myHost.'/i', '', $this->src);
    }

    /**
     * If request an external images, validation here.
     */
    public function validateExternalImage()
    {
        $config = Config::getInstance();
        if ($this->isURL && (!$config->appConfigs['allow_external'])) {
            $this->error('You are not allowed to fetch images from an external website.');
        }

        if ($this->isURL) {
            if ($config->appConfigs['allow_external_sites']) {
                Logger::debug('Fetching from all external sites is enabled.');
            } else {
                Logger::debug('Fetching only from selected external sites is enabled.');
                $allowed = false;
                foreach ($ALLOWED_SITES as $site) {
                    if ((strtolower(substr($this->url['host'], -strlen($site) - 1)) === strtolower(".$site")) || (strtolower($this->url['host']) === strtolower($site))) {
                        Logger::debug("URL hostname {$this->url['host']} matches $site so allowing.");
                        $allowed = true;
                    }
                }
                if (!$allowed) {
                    return $this->error('You may not fetch images from that site. To enable this site in timthumb, you can either add it to $ALLOWED_SITES and set allow_external = true. Or you can set allow_external_sites = true, depending on your security needs.');
                }
            }
        }
    }

    /**
     * Set cachename file. Using url parameters (or route parameters), salt, filemtime (for local images)
     * generate an encrypted uniq name for each request.
     */
    public function setCacheNameFile()
    {
        $config = Config::getInstance();
        $cachePrefix = ($this->isURL ? '_ext_' : '_int_');
        $fileCacheName = $config->appConfigs['file_cache_prefix'].$cachePrefix.md5($this->salt.implode('&', $config->urlParams));

        if (!$this->isURL) {
            $this->localImage = $this->getLocalImagePath($this->src);
            if (!$this->localImage) {
                $this->error("Could not find the local image: {$this->localImage}");
                $this->set404();

                return false;
            }
            Logger::info("Local image path is {$this->localImage}");

            // We include the mtime of the local file in case in changes on disk.
            $this->localImageMTime = @filemtime($this->localImage);
            $fileCacheName .= $this->localImageMTime;
        }

        $fileCacheName .= $config->appConfigs['file_cache_suffix'];
        $this->cachefile = $this->cacheDirectory.'/'.$fileCacheName;
        Logger::debug('Cache file is: '.$this->cachefile);
    }

    public function run()
    {
        $config = Config::getInstance();
        if ($this->isURL) {
            if (!$config->appConfigs['allow_external']) {
                Logger::error('Got a request for an external image but allow_external is disabled so returning error msg.');
                $this->error('You are not allowed to fetch images from an external website.');

                return false;
            }
            Logger::info('Got request for external image. Starting serveExternalImage.');
            if ($this->param('webshot')) {
                if (WEBSHOT_ENABLED) {
                    Logger::error("webshot param is set, so we're going to take a webshot.");
                    $this->serveWebshot();
                } else {
                    $this->error('You added the webshot parameter but webshots are disabled on this server. You need to set WEBSHOT_ENABLED == true to enable webshots.');
                }
            } else {
                Logger::error("webshot is NOT set so we're going to try to fetch a regular image.");
                $this->serveExternalImage();
            }
        } else {
            Logger::info('Got request for internal image. Starting serveInternalImage()');
            $this->serveInternalImage();
        }

        return true;
    }
    protected function handleErrors()
    {
        if ($this->haveErrors()) {
            $config = Config::getInstance();
            if ($config->appConfigs['not_found_image'] && $this->is404()) {
                if ($this->serveImg($config->appConfigs['not_found_image'])) {
                    exit(0);
                } else {
                    $this->error('Additionally, the 404 image that is configured could not be found or there was an error serving it.');
                }
            }
            if ($config->appConfigs['error_image']) {
                if ($this->serveImg($config->appConfigs['error_image'])) {
                    exit(0);
                } else {
                    $this->error('Additionally, the error image that is configured could not be found or there was an error serving it.');
                }
            }
            $this->serveErrors();
            exit(0);
        }

        return false;
    }

    /**
     * Try to get browser cache.
     *
     * @return bool
     */
    protected function tryBrowserCache()
    {
        $config = Config::getInstance();
        if ($config->appConfigs['browser_cache_disable']) {
            Logger::debug('Browser caching is disabled');

            return false;
        }

        if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            Logger::info('Got a conditional get');
            $mtime = false;

            if (!is_file($this->cachefile)) {
                return false;
            }

            if ($this->localImageMTime) {
                $mtime = $this->localImageMTime;
                Logger::debug("Local real file's modification time is $mtime");
            } elseif (is_file($this->cachefile)) { //If it's not a local request then use the mtime of the cached file to determine the 304
                $mtime = @filemtime($this->cachefile);
                Logger::debug("Cached file's modification time is $mtime");
            }
            if (!$mtime) {
                return false;
            }

            $iftime = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
            Logger::debug("The conditional get's if-modified-since unixtime is $iftime");
            if ($iftime < 1) {
                Logger::debug('Got an invalid conditional get modified since time. Returning false.');

                return false;
            }
            if ($iftime < $mtime) { //Real file or cache file has been modified since last request, so force refetch.
                Logger::debug('File has been modified since last fetch.');

                return false;
            } else { //Otherwise serve a 304
                Logger::debug('File has not been modified since last get, so serving a 304.');
                header($_SERVER['SERVER_PROTOCOL'].' 304 Not Modified');
                Logger::info('Returning 304 not modified');

                return true;
            }
        }

        return false;
    }

    /**
     * Try to get generated cache in server.
     *
     * @return bool
     */
    protected function tryServerCache()
    {
        $config = Config::getInstance();
        Logger::info('Trying server cache');
        if (file_exists($this->cachefile)) {
            Logger::info("Cachefile {$this->cachefile} exists");
            if ($this->isURL) {
                Logger::info('This is an external request, so checking if the cachefile is empty which means the request failed previously.');
                if (filesize($this->cachefile) < 1) {
                    Logger::debug('Found an empty cachefile indicating a failed earlier request. Checking how old it is.');
                    //Fetching error occured previously
                    if (time() - @filemtime($this->cachefile) > $config->appConfigs['wait_between_fetch_errors']) {
                        Logger::debug('File is older than '.$config->appConfigs['wait_between_fetch_errors'].' seconds. Deleting and returning false so app can try and load file.');
                        @unlink($this->cachefile);

                        return false; //to indicate we didn't serve from cache and app should try and load
                    } else {
                        Logger::error('Empty cachefile is still fresh so returning message saying we had an error fetching this image from remote host.');
                        $this->set404();
                        $this->error('An error occured fetching image.');

                        return false;
                    }
                }
            } else {
                Logger::info("Trying to serve cachefile {$this->cachefile}");
            }

            if ($this->serveCacheFile()) {
                Logger::info("Succesfully served cachefile {$this->cachefile}");

                return true;
            } else {
                Logger::info("Failed to serve cachefile {$this->cachefile} - Deleting it from cache.");
                //Image serving failed. We can't retry at this point, but lets remove it from cache so the next request recreates it
                @unlink($this->cachefile);

                return true;
            }
        }
    }
    protected function error($err)
    {
        Logger::emergency($err);
        $this->errors[] = $err;
        throw new \Exception($err);

        return false;
    }
    protected function haveErrors()
    {
        if (sizeof($this->errors) > 0) {
            return true;
        }

        return false;
    }
    protected function serveErrors()
    {
        $config = Config::getInstance();
        header($_SERVER['SERVER_PROTOCOL'].' 400 Bad Request');
        if (!$config->appConfigs['display_error_messages']) {
            return;
        }
        $html = '<ul>';
        foreach ($this->errors as $err) {
            $html .= '<li>'.htmlentities($err).'</li>';
        }
        $html .= '</ul>';
        echo '<h1>A TimThumb error has occured</h1>The following error(s) occured:<br />'.$html.'<br />';
        if (isset($_SERVER['QUERY_STRING'])) {
            echo '<br />Query String : '.htmlentities($_SERVER['QUERY_STRING'], ENT_QUOTES);
        }
        echo '<br />Thumcno version : '.VERSION.'</pre>';
    }
    protected function serveInternalImage()
    {
        $config = Config::getInstance();
        Logger::info("Local image path is $this->localImage");
        if (!$this->localImage) {
            $this->sanityFail('localImage not set after verifying it earlier in the code.');

            return false;
        }

        $fileSize = filesize($this->localImage);
        if ($fileSize > $config->appConfigs['max_file_size']) {
            $this->error('The file you specified is greater than the maximum allowed file size.');

            return false;
        }

        if ($fileSize <= 0) {
            $this->error('The file you specified is <= 0 bytes.');

            return false;
        }

        Logger::debug('Calling processImageAndWriteToCache() for local image.');
        if ($this->processImageAndWriteToCache($this->localImage)) {
            $this->serveCacheFile();

            return true;
        } else {
            return false;
        }
    }

    /**
     * Clear cache between requests.
     * Clean before we do anything because we don't want the first visitor after FILE_CACHE_TIME_BETWEEN_CLEANS
     * expires to get a stale image.
     */
    protected function cleanCache()
    {
        $config = Config::getInstance();
        if ($config->appConfigs['file_cache_time_between_cleans'] < 0) {
            return;
        }
        Logger::debug('cleanCache() called');
        $lastCleanFile = $this->cacheDirectory.'/timthumb_cacheLastCleanTime.touch';

        //If this is a new timthumb installation we need to create the file
        if (!is_file($lastCleanFile)) {
            Logger::debug("File tracking last clean doesn't exist. Creating $lastCleanFile");
            if (!touch($lastCleanFile)) {
                $this->error('Could not create cache clean timestamp file.');
            }

            return;
        }
        if (@filemtime($lastCleanFile) < (time() - $config->appConfigs['file_cache_time_between_cleans'])) { //Cache was last cleaned more than 1 day ago
            Logger::debug('Cache was last cleaned more than '.$config->appConfigs['file_cache_time_between_cleans'].' seconds ago. Cleaning now.');
            // Very slight race condition here, but worst case we'll have 2 or 3 servers cleaning the cache simultaneously once a day.
            if (!touch($lastCleanFile)) {
                $this->error('Could not create cache clean timestamp file.');
            }
            $files = glob($this->cacheDirectory.'/*'.$config->appConfigs['file_cache_suffix']);
            if ($files) {
                $timeAgo = time() - $config->appConfigs['file_cache_max_file_age'];
                foreach ($files as $file) {
                    if (@filemtime($file) < $timeAgo) {
                        Logger::debug("Deleting cache file $file older than max age: ".$config->appConfigs['file_cache_max_file_age'].' seconds');
                        @unlink($file);
                    }
                }
            }

            return true;
        } else {
            Logger::debug('Cache was cleaned less than '.$config->appConfigs['file_cache_time_between_cleans'].' seconds ago so no cleaning needed.');
        }

        return false;
    }
    protected function processImageAndWriteToCache($localImage)
    {
        $config = Config::getInstance();
        $sData = getimagesize($localImage);
        $origType = $sData[2];
        $mimeType = $sData['mime'];

        Logger::info("Mime type of image is $mimeType");
        if (!preg_match('/^image\/(?:gif|jpg|jpeg|png)$/i', $mimeType)) {
            return $this->error('The image being resized is not a valid gif, jpg or png.');
        }

        if (!function_exists('imagecreatetruecolor')) {
            return $this->error('GD Library Error: imagecreatetruecolor does not exist - please contact your webhost and ask them to install the GD library');
        }

        if (function_exists('imagefilter') && defined('IMG_FILTER_NEGATE')) {
            $imageFilters = array(
                1 => array(IMG_FILTER_NEGATE, 0),
                2 => array(IMG_FILTER_GRAYSCALE, 0),
                3 => array(IMG_FILTER_BRIGHTNESS, 1),
                4 => array(IMG_FILTER_CONTRAST, 1),
                5 => array(IMG_FILTER_COLORIZE, 4),
                6 => array(IMG_FILTER_EDGEDETECT, 0),
                7 => array(IMG_FILTER_EMBOSS, 0),
                8 => array(IMG_FILTER_GAUSSIAN_BLUR, 0),
                9 => array(IMG_FILTER_SELECTIVE_BLUR, 0),
                10 => array(IMG_FILTER_MEAN_REMOVAL, 0),
                11 => array(IMG_FILTER_SMOOTH, 0),
            );
        }

        // get standard input properties
        $new_width = (int) abs($this->param('w', 0));
        $new_height = (int) abs($this->param('h', 0));
        $zoom_crop = (int) $this->param('zc', $config->appConfigs['default_zc']);
        $quality = (int) abs($this->param('q', $config->appConfigs['default_q']));
        $align = $this->cropTop ? 't' : $this->param('a', 'c');
        $filters = $this->param('f', $config->appConfigs['default_f']);
        $sharpen = (bool) $this->param('s', $config->appConfigs['default_s']);
        $canvas_color = $this->param('cc', $config->appConfigs['default_cc']);
        $canvas_trans = (bool) $this->param('ct', '1');

        // set default width and height if neither are set already
        if ($new_width == 0 && $new_height == 0) {
            $new_width = (int) $config->appConfigs['default_width'];
            $new_height = (int) $config->appConfigs['default_height'];
        }

        // ensure size limits can not be abused
        $new_width = min($new_width, $config->appConfigs['max_width']);
        $new_height = min($new_height, $config->appConfigs['max_height']);

        // set memory limit to be able to have enough space to resize larger images
        $this->setMemoryLimit();

        // open the existing image
        $image = $this->openImage($mimeType, $localImage);
        if ($image === false) {
            return $this->error('Unable to open image.');
        }

        // Get original width and height
        $width = imagesx($image);
        $height = imagesy($image);
        $origin_x = 0;
        $origin_y = 0;

        // generate new w/h if not provided
        if ($new_width && !$new_height) {
            $new_height = floor($height * ($new_width / $width));
        } elseif ($new_height && !$new_width) {
            $new_width = floor($width * ($new_height / $height));
        }

        // scale down and add borders
        if ($zoom_crop == 3) {
            $final_height = $height * ($new_width / $width);

            if ($final_height > $new_height) {
                $new_width = $width * ($new_height / $height);
            } else {
                $new_height = $final_height;
            }
        }

        // create a new true color image
        $canvas = imagecreatetruecolor($new_width, $new_height);
        imagealphablending($canvas, false);

        if (strlen($canvas_color) == 3) { //if is 3-char notation, edit string into 6-char notation
            $canvas_color = str_repeat(substr($canvas_color, 0, 1), 2).str_repeat(substr($canvas_color, 1, 1), 2).str_repeat(substr($canvas_color, 2, 1), 2);
        } elseif (strlen($canvas_color) != 6) {
            $canvas_color = $config->appConfigs['default_cc']; // on error return default canvas color
        }

        $canvas_color_R = hexdec(substr($canvas_color, 0, 2));
        $canvas_color_G = hexdec(substr($canvas_color, 2, 2));
        $canvas_color_B = hexdec(substr($canvas_color, 4, 2));

        // Create a new transparent color for image
        // If is a png and Thumcno::$params['png_is_transparent'] is false then remove the alpha transparency
        // (and if is set a canvas color show it in the background)
        if (preg_match('/^image\/png$/i', $mimeType) && !$config->appConfigs['png_is_transparent'] && $canvas_trans) {
            $color = imagecolorallocatealpha($canvas, $canvas_color_R, $canvas_color_G, $canvas_color_B, 127);
        } else {
            $color = imagecolorallocatealpha($canvas, $canvas_color_R, $canvas_color_G, $canvas_color_B, 0);
        }

        // Completely fill the background of the new image with allocated color.
        imagefill($canvas, 0, 0, $color);

        // scale down and add borders
        if ($zoom_crop == 2) {
            $final_height = $height * ($new_width / $width);

            if ($final_height > $new_height) {
                $origin_x = $new_width / 2;
                $new_width = $width * ($new_height / $height);
                $origin_x = round($origin_x - ($new_width / 2));
            } else {
                $origin_y = $new_height / 2;
                $new_height = $final_height;
                $origin_y = round($origin_y - ($new_height / 2));
            }
        }

        // Restore transparency blending
        imagesavealpha($canvas, true);

        if ($zoom_crop > 0) {
            $src_x = $src_y = 0;
            $src_w = $width;
            $src_h = $height;

            $cmp_x = $width / $new_width;
            $cmp_y = $height / $new_height;

            // calculate x or y coordinate and width or height of source
            if ($cmp_x > $cmp_y) {
                $src_w = round($width / $cmp_x * $cmp_y);
                $src_x = round(($width - ($width / $cmp_x * $cmp_y)) / 2);
            } elseif ($cmp_y > $cmp_x) {
                $src_h = round($height / $cmp_y * $cmp_x);
                $src_y = round(($height - ($height / $cmp_y * $cmp_x)) / 2);
            }

            // positional cropping!
            if ($align) {
                if (strpos($align, 't') !== false) {
                    $src_y = 0;
                }
                if (strpos($align, 'b') !== false) {
                    $src_y = $height - $src_h;
                }
                if (strpos($align, 'l') !== false) {
                    $src_x = 0;
                }
                if (strpos($align, 'r') !== false) {
                    $src_x = $width - $src_w;
                }
            }

            imagecopyresampled($canvas, $image, $origin_x, $origin_y, $src_x, $src_y, $new_width, $new_height, $src_w, $src_h);
        } else {

            // copy and resize part of an image with resampling
            imagecopyresampled($canvas, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        }

        if ($filters != '' && function_exists('imagefilter') && defined('IMG_FILTER_NEGATE')) {
            // apply filters to image
            $filterList = explode('|', $filters);
            foreach ($filterList as $fl) {
                $filterSettings = explode(',', $fl);
                if (isset($imageFilters[$filterSettings[0]])) {
                    for ($i = 0; $i < 4; ++$i) {
                        if (!isset($filterSettings[$i])) {
                            $filterSettings[$i] = null;
                        } else {
                            $filterSettings[$i] = (int) $filterSettings[$i];
                        }
                    }

                    switch ($imageFilters[$filterSettings[0]][1]) {

                        case 1:

                            imagefilter($canvas, $imageFilters[$filterSettings[0]][0], $filterSettings[1]);
                            break;

                        case 2:

                            imagefilter($canvas, $imageFilters[$filterSettings[0]][0], $filterSettings[1], $filterSettings[2]);
                            break;

                        case 3:

                            imagefilter($canvas, $imageFilters[$filterSettings[0]][0], $filterSettings[1], $filterSettings[2], $filterSettings[3]);
                            break;

                        case 4:

                            imagefilter($canvas, $imageFilters[$filterSettings[0]][0], $filterSettings[1], $filterSettings[2], $filterSettings[3], $filterSettings[4]);
                            break;

                        default:

                            imagefilter($canvas, $imageFilters[$filterSettings[0]][0]);
                            break;

                    }
                }
            }
        }

        // sharpen image
        if ($sharpen && function_exists('imageconvolution')) {
            $sharpenMatrix = array(
                array(-1, -1, -1),
                array(-1, 16, -1),
                array(-1, -1, -1),
            );

            $divisor = 8;
            $offset = 0;

            imageconvolution($canvas, $sharpenMatrix, $divisor, $offset);
        }
        //Straight from Wordpress core code. Reduces filesize by up to 70% for PNG's
        if ((IMAGETYPE_PNG == $origType || IMAGETYPE_GIF == $origType) && function_exists('imageistruecolor') && !imageistruecolor($image) && imagecolortransparent($image) > 0) {
            imagetruecolortopalette($canvas, false, imagecolorstotal($image));
        }

        $imgType = '';
        $tempfile = tempnam($this->cacheDirectory, 'timthumb_tmpimg_');
        if (preg_match('/^image\/(?:jpg|jpeg)$/i', $mimeType)) {
            $imgType = 'jpg';
            imagejpeg($canvas, $tempfile, $quality);
        } elseif (preg_match('/^image\/png$/i', $mimeType)) {
            $imgType = 'png';
            imagepng($canvas, $tempfile, floor($quality * 0.09));
        } elseif (preg_match('/^image\/gif$/i', $mimeType)) {
            $imgType = 'gif';
            imagegif($canvas, $tempfile);
        } else {
            return $this->sanityFail('Could not match mime type after verifying it previously.');
        }

        if ($imgType == 'png' && OPTIPNG_ENABLED && OPTIPNG_PATH && @is_file(OPTIPNG_PATH)) {
            $exec = OPTIPNG_PATH;
            Logger::debug("optipng'ing $tempfile");
            $presize = filesize($tempfile);
            $out = `$exec -o1 $tempfile`; //you can use up to -o7 but it really slows things down
            clearstatcache();
            $aftersize = filesize($tempfile);
            $sizeDrop = $presize - $aftersize;
            if ($sizeDrop > 0) {
                Logger::debug("optipng reduced size by $sizeDrop");
            } elseif ($sizeDrop < 0) {
                Logger::debug("optipng increased size! Difference was: $sizeDrop");
            } else {
                Logger::debug('optipng did not change image size.');
            }
        } elseif ($imgType == 'png' && PNGCRUSH_ENABLED && PNGCRUSH_PATH && @is_file(PNGCRUSH_PATH)) {
            $exec = PNGCRUSH_PATH;
            $tempfile2 = tempnam($this->cacheDirectory, 'timthumb_tmpimg_');
            Logger::debug("pngcrush'ing $tempfile to $tempfile2");
            $out = `$exec $tempfile $tempfile2`;
            $todel = '';
            if (is_file($tempfile2)) {
                $sizeDrop = filesize($tempfile) - filesize($tempfile2);
                if ($sizeDrop > 0) {
                    Logger::debug("pngcrush was succesful and gave a $sizeDrop byte size reduction");
                    $todel = $tempfile;
                    $tempfile = $tempfile2;
                } else {
                    Logger::debug("pngcrush did not reduce file size. Difference was $sizeDrop bytes.");
                    $todel = $tempfile2;
                }
            } else {
                Logger::debug("pngcrush failed with output: $out");
                $todel = $tempfile2;
            }
            @unlink($todel);
        }

        Logger::debug('Rewriting image with security header.');
        $tempfile4 = tempnam($this->cacheDirectory, 'timthumb_tmpimg_');
        $context = stream_context_create();
        $fp = fopen($tempfile, 'r', 0, $context);
        file_put_contents($tempfile4, $this->filePrependSecurityBlock.$imgType.' ?'.'>'); //6 extra bytes, first 3 being image type
        file_put_contents($tempfile4, $fp, FILE_APPEND);
        fclose($fp);
        @unlink($tempfile);
        Logger::debug('Locking and replacing cache file.');
        $lockFile = $this->cachefile.'.lock';
        $fh = fopen($lockFile, 'w');
        if (!$fh) {
            return $this->error('Could not open the lockfile for writing an image.');
        }
        if (flock($fh, LOCK_EX)) {
            @unlink($this->cachefile); //rename generally overwrites, but doing this in case of platform specific quirks. File might not exist yet.
            rename($tempfile4, $this->cachefile);
            flock($fh, LOCK_UN);
            fclose($fh);
            @unlink($lockFile);
        } else {
            fclose($fh);
            @unlink($lockFile);
            @unlink($tempfile4);

            return $this->error('Could not get a lock for writing.');
        }
        Logger::debug('Done image replace with security header. Cleaning up and running cleanCache()');
        imagedestroy($canvas);
        imagedestroy($image);

        return true;
    }
    protected function calcDocRoot()
    {
        $docRoot = @$_SERVER['DOCUMENT_ROOT'];
        if (defined('LOCAL_FILE_BASE_DIRECTORY')) {
            $docRoot = LOCAL_FILE_BASE_DIRECTORY;
        }
        if (!isset($docRoot)) {
            Logger::debug('DOCUMENT_ROOT is not set. This is probably windows. Starting search 1.');
            if (isset($_SERVER['SCRIPT_FILENAME'])) {
                $docRoot = str_replace('\\', '/', substr($_SERVER['SCRIPT_FILENAME'], 0, 0 - strlen($_SERVER['PHP_SELF'])));
                Logger::debug("Generated docRoot using SCRIPT_FILENAME and PHP_SELF as: $docRoot");
            }
        }
        if (!isset($docRoot)) {
            Logger::debug('DOCUMENT_ROOT still is not set. Starting search 2.');
            if (isset($_SERVER['PATH_TRANSLATED'])) {
                $docRoot = str_replace('\\', '/', substr(str_replace('\\\\', '\\', $_SERVER['PATH_TRANSLATED']), 0, 0 - strlen($_SERVER['PHP_SELF'])));
                Logger::debug("Generated docRoot using PATH_TRANSLATED and PHP_SELF as: $docRoot");
            }
        }
        if ($docRoot && $_SERVER['DOCUMENT_ROOT'] != '/') {
            $docRoot = preg_replace('/\/$/', '', $docRoot);
        }
        Logger::debug('Doc root is: '.$docRoot);
        $this->docRoot = $docRoot;
    }
    protected function getLocalImagePath($src)
    {
        $src = ltrim($src, '/'); //strip off the leading '/'
        if (!$this->docRoot) {
            Logger::error('We have no document root set, so as a last resort, lets check if the image is in the current dir and serve that.');
            //We don't support serving images outside the current dir if we don't have a doc root for security reasons.
            $file = preg_replace('/^.*?([^\/\\\\]+)$/', '$1', $src); //strip off any path info and just leave the filename.
            if (is_file($file)) {
                return $this->realpath($file);
            }

            return $this->error("Could not find your website document root and the file specified doesn't exist in timthumbs directory. We don't support serving files outside timthumb's directory without a document root for security reasons.");
        } elseif (!is_dir($this->docRoot)) {
            $this->error("Server path does not exist. Ensure variable \$_SERVER['DOCUMENT_ROOT'] is set correctly");
        }

        //Do not go past this point without docRoot set

        //Try src under docRoot
        if (file_exists($this->docRoot.'/'.$src)) {
            Logger::info('Found file as '.$this->docRoot.'/'.$src);
            $real = $this->realpath($this->docRoot.'/'.$src);
            if (stripos($real, $this->docRoot) === 0) {
                return $real;
            } else {
                Logger::info('Security block: The file specified occurs outside the document root.');
                //allow search to continue
            }
        }
        //Check absolute paths and then verify the real path is under doc root
        $absolute = $this->realpath('/'.$src);
        if ($absolute && file_exists($absolute)) { //realpath does file_exists check, so can probably skip the exists check here
            Logger::info("Found absolute path: $absolute");
            if (!$this->docRoot) {
                $this->sanityFail('docRoot not set when checking absolute path.');
            }
            if (stripos($absolute, $this->docRoot) === 0) {
                return $absolute;
            } else {
                Logger::error('Security block: The file specified occurs outside the document root.');
                //and continue search
            }
        }

        $base = $this->docRoot;

        // account for Windows directory structure
        if (strstr($_SERVER['SCRIPT_FILENAME'], ':')) {
            $sub_directories = explode('\\', str_replace($this->docRoot, '', $_SERVER['SCRIPT_FILENAME']));
        } else {
            $sub_directories = explode('/', str_replace($this->docRoot, '', $_SERVER['SCRIPT_FILENAME']));
        }

        foreach ($sub_directories as $sub) {
            $base .= $sub.'/';
            Logger::info('Trying file as: '.$base.$src);
            if (file_exists($base.$src)) {
                Logger::info('Found file as: '.$base.$src);
                $real = $this->realpath($base.$src);
                if (stripos($real, $this->realpath($this->docRoot)) === 0) {
                    return $real;
                } else {
                    Logger::warning('Security block: The file specified occurs outside the document root.');
                    //And continue search
                }
            }
        }

        return false;
    }
    protected function realpath($path)
    {
        //try to remove any relative paths
        $remove_relatives = '/\w+\/\.\.\//';
        while (preg_match($remove_relatives, $path)) {
            $path = preg_replace($remove_relatives, '', $path);
        }
        //if any remain use PHP realpath to strip them out, otherwise return $path
        //if using realpath, any symlinks will also be resolved
        return preg_match('#^\.\./|/\.\./#', $path) ? realpath($path) : $path;
    }
    protected function toDelete($name)
    {
        Logger::debug("Scheduling file $name to delete on destruct.");
        $this->toDeletes[] = $name;
    }
    protected function serveWebshot()
    {
        Logger::notice('Starting serveWebshot');
        $instr = 'Please follow the instructions at http://code.google.com/p/timthumb/ to set your server up for taking website screenshots.';
        if (!is_file(WEBSHOT_CUTYCAPT)) {
            return $this->error("CutyCapt is not installed. $instr");
        }
        if (!is_file(WEBSHOT_XVFB)) {
            return $this->Error("Xvfb is not installed. $instr");
        }
        $cuty = WEBSHOT_CUTYCAPT;
        $xv = WEBSHOT_XVFB;
        $screenX = WEBSHOT_SCREEN_X;
        $screenY = WEBSHOT_SCREEN_Y;
        $colDepth = WEBSHOT_COLOR_DEPTH;
        $format = WEBSHOT_IMAGE_FORMAT;
        $timeout = WEBSHOT_TIMEOUT * 1000;
        $ua = WEBSHOT_USER_AGENT;
        $jsOn = WEBSHOT_JAVASCRIPT_ON ? 'on' : 'off';
        $javaOn = WEBSHOT_JAVA_ON ? 'on' : 'off';
        $pluginsOn = WEBSHOT_PLUGINS_ON ? 'on' : 'off';
        $proxy = WEBSHOT_PROXY ? ' --http-proxy='.WEBSHOT_PROXY : '';
        $tempfile = tempnam($this->cacheDirectory, 'timthumb_webshot');
        $url = $this->src;
        if (!preg_match('/^https?:\/\/[a-zA-Z0-9\.\-]+/i', $url)) {
            return $this->error('Invalid URL supplied.');
        }
        $url = preg_replace('/[^A-Za-z0-9\-\.\_:\/\?\&\+\;\=]+/', '', $url); //RFC 3986 plus ()$ chars to prevent exploit below. Plus the following are also removed: @*!~#[]',
        // 2014 update by Mark Maunder: This exploit: http://cxsecurity.com/issue/WLB-2014060134
        // uses the $(command) shell execution syntax to execute arbitrary shell commands as the web server user.
        // So we're now filtering out the characters: '$', '(' and ')' in the above regex to avoid this.
        // We are also filtering out chars rarely used in URLs but legal accoring to the URL RFC which might be exploitable. These include: @*!~#[]',
        // We're doing this because we're passing this URL to the shell and need to make very sure it's not going to execute arbitrary commands.
        if (WEBSHOT_XVFB_RUNNING) {
            putenv('DISPLAY=:100.0');
            $command = "$cuty $proxy --max-wait=$timeout --user-agent=\"$ua\" --javascript=$jsOn --java=$javaOn --plugins=$pluginsOn --js-can-open-windows=off --url=\"$url\" --out-format=$format --out=$tempfile";
        } else {
            $command = "$xv --server-args=\"-screen 0, {$screenX}x{$screenY}x{$colDepth}\" $cuty $proxy --max-wait=$timeout --user-agent=\"$ua\" --javascript=$jsOn --java=$javaOn --plugins=$pluginsOn --js-can-open-windows=off --url=\"$url\" --out-format=$format --out=$tempfile";
        }
        Logger::notice("Executing command: $command");
        $out = `$command`;
        Logger::info("Received output: $out");
        if (!is_file($tempfile)) {
            $this->set404();

            return $this->error('The command to create a thumbnail failed.');
        }
        $this->cropTop = true;
        if ($this->processImageAndWriteToCache($tempfile)) {
            Logger::info('Image processed succesfully. Serving from cache');

            return $this->serveCacheFile();
        } else {
            return false;
        }
    }
    protected function serveExternalImage()
    {
        if (!preg_match('/^https?:\/\/[a-zA-Z0-9\-\.]+/i', $this->src)) {
            $this->error('Invalid URL supplied.');

            return false;
        }
        $tempfile = tempnam($this->cacheDirectory, 'timthumb');
        Logger::info("Fetching external image into temporary file $tempfile");
        $this->toDelete($tempfile);
        //fetch file here
        if (!$this->getURL($this->src, $tempfile)) {
            @unlink($this->cachefile);
            touch($this->cachefile);
            Logger::error('Error fetching URL: '.$this->lastURLError);
            $this->error('Error reading the URL you specified from remote host.'.$this->lastURLError);

            return false;
        }

        $mimeType = $this->getMimeType($tempfile);
        if (!preg_match("/^image\/(?:jpg|jpeg|gif|png)$/i", $mimeType)) {
            Logger::info("Remote file has invalid mime type: $mimeType");
            @unlink($this->cachefile);
            touch($this->cachefile);
            $this->error("The remote file is not a valid image. Mimetype = '".$mimeType."'".$tempfile);

            return false;
        }
        if ($this->processImageAndWriteToCache($tempfile)) {
            Logger::notice('Image processed succesfully. Serving from cache');

            return $this->serveCacheFile();
        } else {
            return false;
        }
    }
    public static function curlWrite($h, $d)
    {
        fwrite(self::$curlFH, $d);
        self::$curlDataWritten += strlen($d);
        if (self::$curlDataWritten > Config::getInstance()->appConfigs['max_file_size']) {
            return 0;
        } else {
            return strlen($d);
        }
    }
    protected function serveCacheFile()
    {
        Logger::notice("Serving {$this->cachefile}");
        if (!is_file($this->cachefile)) {
            $this->error("serveCacheFile called in thumcno but we couldn't find the cached file.");

            return false;
        }

        $fp = fopen($this->cachefile, 'rb');
        if (!$fp) {
            return $this->error('Could not open cachefile.');
        }

        fseek($fp, strlen($this->filePrependSecurityBlock), SEEK_SET);
        $imgType = fread($fp, 3);
        fseek($fp, 3, SEEK_CUR);
        if (ftell($fp) != strlen($this->filePrependSecurityBlock) + 6) {
            @unlink($this->cachefile);

            return $this->error('The cached image file seems to be corrupt.');
        }

        $imageDataSize = filesize($this->cachefile) - (strlen($this->filePrependSecurityBlock) + 6);
        $this->sendImageHeaders($imgType, $imageDataSize);
        $bytesSent = @fpassthru($fp);
        fclose($fp);
        if ($bytesSent > 0) {
            return true;
        }

        $content = file_get_contents($this->cachefile);
        if ($content != false) {
            $content = substr($content, strlen($this->filePrependSecurityBlock) + 6);
            echo $content;
            Logger::info('Served using file_get_contents and echo');

            return true;
        } else {
            $this->error('Cache file could not be loaded.');

            return false;
        }
    }
    protected function sendImageHeaders($mimeType, $dataSize)
    {
        $config = Config::getInstance();
        if (!preg_match('/^image\//i', $mimeType)) {
            $mimeType = 'image/'.$mimeType;
        }
        if (strtolower($mimeType) == 'image/jpg') {
            $mimeType = 'image/jpeg';
        }
        $gmdate_expires = gmdate('D, d M Y H:i:s', strtotime('now +10 days')).' GMT';
        $gmdate_modified = gmdate('D, d M Y H:i:s').' GMT';
        // send content headers then display image
        header('Content-Type: '.$mimeType);
        header('Accept-Ranges: none'); //Changed this because we don't accept range requests
        header('Last-Modified: '.$gmdate_modified);
        header('Content-Length: '.$dataSize);
        if ($config->appConfigs['browser_cache_disable']) {
            Logger::debug('Browser cache is disabled so setting non-caching headers.');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: '.gmdate('D, d M Y H:i:s', time()));
        } else {
            Logger::debug('Browser caching is enabled');
            header('Cache-Control: max-age='.$config->appConfigs['browser_cache_max_age'].', must-revalidate');
            header('Expires: '.$gmdate_expires);
        }

        return true;
    }
    protected function param($property, $default = '')
    {
        $config = Config::getInstance();
        if (isset($config->urlParams[$property])) {
            return $config->urlParams[$property];
        } else {
            return $default;
        }
    }

    protected function openImage($mimeType, $src)
    {
        switch ($mimeType) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($src);
                break;

            case 'image/png':
                $image = imagecreatefrompng($src);
                imagealphablending($image, true);
                imagesavealpha($image, true);
                break;

            case 'image/gif':
                $image = imagecreatefromgif($src);
                break;

            default:
                $this->error('Unrecognised mimeType');
        }

        return $image;
    }
    protected function getIP()
    {
        $rem = @$_SERVER['REMOTE_ADDR'];
        $ff = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $ci = @$_SERVER['HTTP_CLIENT_IP'];
        if (preg_match('/^(?:192\.168|172\.16|10\.|127\.)/', $rem)) {
            if ($ff) {
                return $ff;
            }
            if ($ci) {
                return $ci;
            }

            return $rem;
        } else {
            if ($rem) {
                return $rem;
            }
            if ($ff) {
                return $ff;
            }
            if ($ci) {
                return $ci;
            }

            return 'UNKNOWN';
        }
    }

    protected function sanityFail($msg)
    {
        return $this->error("There is a problem in the thumcno code. Message: Please report this error at <a href='https://github.com/tacnoman/thumcno'>thumcno's bug tracking page</a>: $msg");
    }

    protected function getMimeType($file)
    {
        $info = getimagesize($file);
        if (is_array($info) && $info['mime']) {
            return $info['mime'];
        }

        return '';
    }

    protected function setMemoryLimit()
    {
        $config = Config::getInstance();
        $inimem = ini_get('memory_limit');
        $inibytes = self::returnBytes($inimem);
        $ourbytes = self::returnBytes($config->appConfigs['memory_limit']);
        if ($inibytes < $ourbytes) {
            ini_set('memory_limit', $config->appConfigs['memory_limit']);
            Logger::warning("Increased memory from $inimem to ".$config->appConfigs['memory_limit']);
        } else {
            Logger::warning('Not adjusting memory size because the current setting is '.$inimem.' and our size of '.$config->appConfigs['memory_limit'].' is smaller.');
        }
    }

    protected static function returnBytes($size_str)
    {
        switch (substr($size_str, -1)) {
            case 'M': case 'm': return (int) $size_str * 1048576;
            case 'K': case 'k': return (int) $size_str * 1024;
            case 'G': case 'g': return (int) $size_str * 1073741824;
            default: return $size_str;
        }
    }

    protected function getURL($url, $tempfile)
    {
        $config = Config::getInstance();
        $this->lastURLError = false;
        $url = preg_replace('/ /', '%20', $url);
        if (function_exists('curl_init')) {
            Logger::debug('Curl is installed so using it to fetch URL.');
            self::$curlFH = fopen($tempfile, 'w');
            if (!self::$curlFH) {
                $this->error("Could not open $tempfile for writing.");

                return false;
            }
            self::$curlDataWritten = 0;
            Logger::debug("Fetching url with curl: $url");
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_TIMEOUT, $config->appConfigs['curl_timeout']);
            curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/534.30 (KHTML, like Gecko) Chrome/12.0.742.122 Safari/534.30');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_WRITEFUNCTION, 'ThumcnoServer::curlWrite');
            @curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            @curl_setopt($curl, CURLOPT_MAXREDIRS, 10);

            $curlResult = curl_exec($curl);
            fclose(self::$curlFH);
            $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($httpStatus == 404) {
                $this->set404();
            }
            if ($httpStatus == 302) {
                $this->error('External Image is Redirecting. Try alternate image url');

                return false;
            }
            if ($curlResult) {
                curl_close($curl);

                return true;
            } else {
                $this->lastURLError = curl_error($curl);
                curl_close($curl);

                return false;
            }
        } else {
            $img = @file_get_contents($url);
            if ($img === false) {
                $err = error_get_last();
                if (is_array($err) && $err['message']) {
                    $this->lastURLError = $err['message'];
                } else {
                    $this->lastURLError = $err;
                }
                if (preg_match('/404/', $this->lastURLError)) {
                    $this->set404();
                }

                return false;
            }
            if (!file_put_contents($tempfile, $img)) {
                $this->error("Could not write to $tempfile.");

                return false;
            }

            return true;
        }
    }
    protected function serveImg($file)
    {
        $s = getimagesize($file);
        if (!($s && $s['mime'])) {
            return false;
        }
        header('Content-Type: '.$s['mime']);
        header('Content-Length: '.filesize($file));
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        $bytes = @readfile($file);
        if ($bytes > 0) {
            return true;
        }
        $content = @file_get_contents($file);
        if ($content != false) {
            echo $content;

            return true;
        }

        return false;
    }
    protected function set404()
    {
        $this->is404 = true;
    }
    protected function is404()
    {
        return $this->is404;
    }
}
