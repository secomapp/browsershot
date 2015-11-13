<?php

namespace Spatie\Browsershot;

use Exception;
use Intervention\Image\ImageManager;

/**
 * Class Browsershot
 * @package Spatie\Browsershot
 */

class Browsershot {

    /**
     * @var int
     */
    private $width;
    /**
     * @var int
     */
    private $height;
    /**
     * @var int
     */
    private $quality; 
    /**
     * @var
     */
    private $URL;
    /**
     * @var string
     */
    private $binPath;


    /**
     * @param string $binPath The path to the phantomjs binary
     * @param int $width
     * @param mixed $height
     */
    public function __construct($binPath = '', $width = 640, $height = 480, $quality = 90)
    {
        if ($binPath == '') {
            $binPath = realpath(dirname(__FILE__) . '/../../../bin/phantomjs');
        }

        $this->binPath = $binPath;
        $this->width = $width;
        $this->height = $height;
        $this->quality = $quality;

        return $this;
    }

    /**
     * @param string $binPath The path to the phantomjs binary
     * @return $this
     */
    public function setBinPath($binPath)
    {
        $this->binPath = $binPath;
        return $this;
    }

    /**
     * @param int $width The required with of the screenshot
     * @return $this
     * @throws \Exception
     */
    public function setWidth($width)
    {
        if (! is_numeric($width)) {
            throw new Exception('Width must be numeric');
        }

        $this->width = $width;
        return $this;
    }

    /**
     * @param int $height The required height of the screenshot
     * @return $this
     * @throws \Exception
     */
    public function setHeight($height)
    {
        if (! is_numeric($height)) {
            throw new Exception('Height must be numeric');
        }

        $this->height = $height;
        return $this;
    }

    /**
     * Set the image quality.
     * 
     * @param int $quality
     * @return $this
     * @throws \Excpetion
     */
    public function setQuality($quality)
    {
        if (! is_numeric($quality) || $quality < 1 || $quality > 100) {
            throw new Exception('Quality must be a numeric value between 1 - 100');
        }
        $this->quality = $quality;
        return $this;
    }
    
    /**
     * Set to height so the whole page will be rendered.
     *
     * @return $this
     */
    public function setHeightToRenderWholePage()
    {
        $this->height = 0;

        return $this;
    }

    /**
     * @param string $url The website of which a screenshot should be make
     * @return $this
     * @throws \Exception
     */
    public function setURL($url)
    {
        if (! strlen($url) > 0 ) {
            throw new Exception('No url specified');
        }

        $this->URL = $url;
        return $this;
    }

    /**
     *
     * Convert the webpage to an image
     *
     * @param string $targetFile The path of the file where the screenshot should be saved
     * @return bool
     * @throws \Exception
     */
    public function save($targetFile, $rWidth=null, $rHeight=null, $callback = null)
    {
        if ($targetFile == '') {
            throw new Exception('targetfile not set');
        }

        if (! in_array(strtolower(pathinfo($targetFile, PATHINFO_EXTENSION)), ['jpeg', 'jpg', 'png'])) {
            throw new Exception('targetfile extension not valid');
        }

        if ($this->URL == '') {
            throw new Exception('url not set');
        }

        if (filter_var($this->URL, FILTER_VALIDATE_URL) === FALSE) {
            throw new Exception('url is invalid');
        }

        if (! file_exists($this->binPath)) {
            throw new Exception('binary does not exist');
        }


        $tempJsFileHandle = tmpfile();

        $fileContent= "
            var page = require('webpage').create();
            page.settings.javascriptEnabled = true;
            page.viewportSize = { width: " . $this->width . ($this->height == 0 ? '' : ", height: " . $this->height ) . " };
            page.open('" . $this->URL . "', function() {
               window.setTimeout(function(){
                page.render('" . $targetFile . "');
                phantom.exit();
            }, 5000); // give phantomjs 5 seconds to process all javascript
        });";

        fwrite($tempJsFileHandle, $fileContent);
        $tempFileName = stream_get_meta_data($tempJsFileHandle)['uri'];
        $cmd = escapeshellcmd("{$this->binPath} --ssl-protocol=any --ignore-ssl-errors=true " . $tempFileName);

        shell_exec($cmd);

        fclose($tempJsFileHandle);

        if (! file_exists($targetFile) OR filesize($targetFile) < 1024)
        {
            throw new Exception('could not create screenshot');
        }

        if ($this->height > 0) {
            $imageManager = new ImageManager();
            if($rWidth !==null || $rHeight!==null){
                $imageManager
                    ->make($targetFile)
                    ->crop($this->width,$this->height,0,0)
                    ->resize($rWidth, $rHeight, $callback)
                    ->save($targetFile, $this->quality);
            }else{
                $imageManager
                    ->make($targetFile)
                    ->crop($this->width,$this->height,0,0)
                    ->save($targetFile, $this->quality);
            }
        }

        return true;
    }


}
