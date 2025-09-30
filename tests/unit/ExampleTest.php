<?php

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function testBasicAssertion(): void
    {
        $this->assertTrue(true);
        $this->assertEquals(2, 1 + 1);
        $this->assertIsString('Hola mundo');
    }

    public function testArrayOperations(): void
    {
        $array = ['a', 'b', 'c'];
        
        $this->assertCount(3, $array);
        $this->assertContains('b', $array);
        $this->assertNotContains('d', $array);
    }

    public function testStringOperations(): void
    {
        $string = 'Lucatón - Plataforma de Crowdfunding';
        
        $this->assertStringContainsString('Lucatón', $string);
        $this->assertStringStartsWith('Lucatón', $string);
        $this->assertStringEndsWith('Crowdfunding', $string);
    }

    public function testMathOperations(): void
    {
        $result = 10 * 5;
        
        $this->assertEquals(50, $result);
        $this->assertGreaterThan(40, $result);
        $this->assertLessThan(60, $result);
    }
}