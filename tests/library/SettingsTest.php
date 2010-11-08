<?php
class SettingsTest extends PHPUnit_Framework_TestCase {

    protected function tearDown() {
        Settings::reset();
    }

    public function testInvalidModeThrowsException() {
        try {
            Settings::setMode("invalid");
        } catch (CoreException $e) {
            $this->assertEquals("Mode is not supported", $e->getMessage());
            $this->assertEquals(CoreException::INVALID_MODE, $e->getCode());
            return;
        }

        $this->fail("Expected exception not raised");
    }

    public function testValidModes() {
        Settings::setMode("build");
        $this->assertEquals("build", Settings::getMode());
    }
}