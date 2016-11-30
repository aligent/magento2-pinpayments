<?php

namespace Aligent\Pinpay\Test\Unit\Model;

class Fixture extends \PHPUnit_Framework_TestCase
{

    protected $reflectionClass;


    public function getFixture($fileName)
    {
        $filename = $this->getTestClassDirectory() . "$fileName.json";
        $content = file_get_contents($filename);

        if ($content === false) {
            return [];
        }

        return json_decode($content, true);
    }

    protected function getReflectionClass()
    {
        if ($this->reflectionClass === null) {
            $this->reflectionClass = new \ReflectionClass($this);
        }
        return $this->reflectionClass;
    }

    /**
     * @return string A subdirectory containing files specific to the current test class
     */
    protected function getTestClassDirectory()
    {
        $reflect = $this->getReflectionClass();
        return $this->getTestDirectory() . $reflect->getShortName() . DIRECTORY_SEPARATOR;
    }

    protected function getTestDirectory()
    {
        $reflect = $this->getReflectionClass();
        return dirname($reflect->getFileName()) . DIRECTORY_SEPARATOR;
    }

}