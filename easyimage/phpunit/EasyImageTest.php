<?php

class SharesTest extends PHPUnit_Framework_TestCase {

    /**
     * @runInSeparateProcess
     */
    public function testReadImages() {

    	include_once dirname(__DIR__).'/easyimage.php';
    	$i=EasyImage::Open(__DIR__.'/[G]_[ImAgE]_WBq_ptd_45j.bmp');
    	$this->assertEquals(array(
    		'w'=>945
    		'h'=>594
    		), EasyImage::GetSize($i));

    }
}