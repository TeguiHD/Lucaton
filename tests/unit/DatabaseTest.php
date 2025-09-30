<?php

use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{
    public function testDatabaseSingletonPattern(): void
    {
        // Verificar que getInstance siempre devuelve la misma instancia
        $instance1 = Database::getInstance();
        $instance2 = Database::getInstance();
        
        $this->assertSame($instance1, $instance2);
        $this->assertInstanceOf(Database::class, $instance1);
    }

    public function testDatabaseClassExists(): void
    {
        $this->assertTrue(class_exists('Database'));
    }

    public function testGetInstanceReturnsDatabase(): void
    {
        $database = Database::getInstance();
        $this->assertInstanceOf(Database::class, $database);
    }

    public function testDatabaseHasRequiredMethods(): void
    {
        $this->assertTrue(method_exists(Database::class, 'getInstance'));
        
        // Verificar que otros métodos públicos existen si los hay
        $reflection = new ReflectionClass(Database::class);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        
        $methodNames = array_map(function($method) {
            return $method->getName();
        }, $methods);
        
        $this->assertContains('getInstance', $methodNames);
    }
}