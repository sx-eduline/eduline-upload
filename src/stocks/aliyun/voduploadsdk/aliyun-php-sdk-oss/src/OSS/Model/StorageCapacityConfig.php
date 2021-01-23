<?php

namespace OSS\Model;

use SimpleXMLElement;

/**
 * Class StorageCapacityConfig
 *
 * @package OSS\Model
 * @link    http://docs.alibaba-inc.com/pages/viewpage.action?pageId=271614763
 */
class StorageCapacityConfig implements XmlConfig
{
    private $storageCapacity = 0;

    /**
     * StorageCapacityConfig constructor.
     *
     * @param int $storageCapacity
     */
    public function __construct($storageCapacity)
    {
        $this->storageCapacity = $storageCapacity;
    }

    /**
     * Not implemented
     */
    public function parseFromXml($strXml)
    {
        throw new OssException("Not implemented.");
    }

    /**
     * To string
     *
     * @return string
     */
    function __toString()
    {
        return $this->serializeToXml();
    }

    /**
     * Serialize StorageCapacityConfig into xml
     *
     * @return string
     */
    public function serializeToXml()
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><BucketUserQos></BucketUserQos>');
        $xml->addChild('StorageCapacity', strval($this->storageCapacity));
        return $xml->asXML();
    }

    /**
     * Get storage capacity
     *
     * @return int
     */
    public function getStorageCapacity()
    {
        return $this->storageCapacity;
    }

    /**
     * Set storage capacity
     *
     * @param int $storageCapacity
     */
    public function setStorageCapacity($storageCapacity)
    {
        $this->storageCapacity = $storageCapacity;
    }
}