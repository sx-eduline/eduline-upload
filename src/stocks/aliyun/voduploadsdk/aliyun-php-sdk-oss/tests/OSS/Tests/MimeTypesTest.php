<?php

namespace OSS\Tests;

use OSS\Core\MimeTypes;
use PHPUnit_Framework_TestCase;

class MimeTypesTest extends PHPUnit_Framework_TestCase
{
    public function testGetMimeType()
    {
        $this->assertEquals('application/xml', MimeTypes::getMimetype('file.xml'));
    }
}
