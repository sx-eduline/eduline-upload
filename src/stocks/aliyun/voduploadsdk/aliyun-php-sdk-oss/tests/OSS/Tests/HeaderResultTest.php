<?php

namespace OSS\Tests;

use OSS\Result\HeaderResult;
use OSS\Http\ResponseCore;
use PHPUnit_Framework_TestCase;

/**
 * Class HeaderResultTest
 *
 * @package OSS\Tests
 */
class HeaderResultTest extends PHPUnit_Framework_TestCase
{
    public function testGetHeader()
    {
        $response = new ResponseCore(['key' => 'value'], "", 200);
        $result   = new HeaderResult($response);
        $this->assertTrue($result->isOK());
        $this->assertTrue(is_array($result->getData()));
        $data = $result->getData();
        $this->assertEquals($data['key'], 'value');
    }
}
