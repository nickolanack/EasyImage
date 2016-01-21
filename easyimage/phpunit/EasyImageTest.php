<?php

class SharesTest extends PHPUnit_Framework_TestCase {

    /**
     * @runInSeparateProcess
     */
    public function testReadImages() {

    	include_once dirname(__DIR__).'/easyimage.php';
    	EasyImage::Open(__DIR__.'/[G]_[ImAgE]_WBq_ptd_45j.bmp');

    }
}