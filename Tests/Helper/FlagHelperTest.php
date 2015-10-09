<?php

namespace AndreasGlaser\DCEventBundle\Tests\Helper;

use AndreasGlaser\DCEventBundle\Helper\FlagHelper;

/**
 * Class FlagHelperTestTest
 *
 * @package AndreasGlaser\DCEventBundle\Tests\Helper
 * @author  Andreas Glaser
 */
class FlagHelperTestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @author Andreas Glaser
     */
    public function testValidInput()
    {
        $flagHelper = FlagHelper::factory();
        $testObject = new \stdClass();
        $this->assertTrue($flagHelper->flagSet('my-flag-1', $testObject));
        $this->assertFalse($flagHelper->flagSet('my-flag-1', $testObject));
        $this->assertTrue($flagHelper->flagExists('my-flag-1', $testObject));
        $this->assertTrue($flagHelper->flagExistsAndRemove('my-flag-1', $testObject));
        $this->assertFalse($flagHelper->flagExists('my-flag-1', $testObject));
        $this->assertTrue($flagHelper->flagSet('my-flag-2', $testObject));
        $this->assertTrue($flagHelper->flagRemove('my-flag-2', $testObject));
        $this->assertFalse($flagHelper->flagExists('my-flag-xyz', $testObject));
        $this->assertFalse($flagHelper->flagExistsAndRemove('my-flag-xyz', $testObject));
    }

    /**
     * @author Andreas Glaser
     */
    public function testInvalidInput()
    {
        $this->setExpectedException('RuntimeException');

        $flagHelper = FlagHelper::factory();
        $testObject = 'invalid';
        $flagHelper->flagSet('my-flag-1', $testObject);
        $flagHelper->flagExists('my-flag-1', $testObject);
        $flagHelper->flagRemove('my-flag-1', $testObject);
        $flagHelper->flagExistsAndRemove('my-flag-1', $testObject);
    }
}