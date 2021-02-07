<?php

namespace SuperGiggle;

use PHPUnit\Framework\TestCase;

class UtilTest extends TestCase
{


    /**
     * Define if the object can be constructed
     *
     * @test
     *
     * @return void
     */
    public function objectCanBeConstructed()
    {
        $util = new Util();
        $this->assertInstanceOf(Util::class, $util);
    }


    /**
     * Test if directory is ok to windows platform
     *
     * @test
     *
     * @return void
     */
    public function phpCsBinaryOnWindows()
    {
        $os = $this->createMock(Os::class);
        $os->method('isWindows')
           ->willReturn(true);

        $util = new Util();
        $util->setOs($os);

        $this->assertStringContainsString('/../vendor/bin/phpcs.bat', $util->getPhpCsBinary());
    }
    

    /**
     * Test if directory is ok to linux platform
     *
     * @test
     *
     * @return void
     */
    public function phpCsBinaryOnLinux()
    {
        $os = $this->createMock(Os::class);

        $util = new Util();
        $util->setOs($os);

        $this->assertStringContainsString('/../vendor/bin/phpcs', $util->getPhpCsBinary());
    }


}
