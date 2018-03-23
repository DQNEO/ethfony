<?php

use PHPUnit\Framework\TestCase;

class Ethna_KernelTest extends TestCase
{
    public function testNew()
    {
        $obj = new Ethna_Kernel();
        $this->assertNotEmpty($obj);
    }
}

