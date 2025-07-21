<?php

declare(strict_types=1);

namespace BuiltNorth\WPSchema\Tests\Graph;

use PHPUnit\Framework\TestCase;
use BuiltNorth\WPSchema\Graph\SchemaPiece;

/**
 * Test the SchemaPiece class
 */
class SchemaPieceTest extends TestCase
{
    public function testBasicCreation(): void
    {
        $piece = new SchemaPiece('#test', 'Article');
        
        $this->assertEquals('#test', $piece->get_id());
        $this->assertEquals('Article', $piece->get_type());
    }

    public function testSetAndGet(): void
    {
        $piece = new SchemaPiece('#test', 'Article');
        
        $piece->set('headline', 'Test Article');
        $piece->set('author', ['@id' => '#author']);
        
        $this->assertEquals('Test Article', $piece->get('headline'));
        $this->assertEquals(['@id' => '#author'], $piece->get('author'));
        $this->assertNull($piece->get('nonexistent'));
    }

    public function testFluentInterface(): void
    {
        $piece = new SchemaPiece('#test', 'Article');
        
        $result = $piece
            ->set('headline', 'Test Article')
            ->set('datePublished', '2024-01-01')
            ->set('author', ['@id' => '#author']);
        
        $this->assertSame($piece, $result, 'Fluent interface should return the same object');
    }

    public function testSetWithEmptyValues(): void
    {
        $piece = new SchemaPiece('#test', 'Article');
        
        $piece->set('headline', 'Test Article');
        $piece->set('description', '');
        $piece->set('author', null);
        $piece->set('tags', []);
        
        $this->assertEquals('Test Article', $piece->get('headline'));
        $this->assertEquals('', $piece->get('description'));
        $this->assertNull($piece->get('author'));
        $this->assertEquals([], $piece->get('tags'));
    }

    public function testAddReference(): void
    {
        $piece = new SchemaPiece('#test', 'Article');
        
        $piece->add_reference('author', '#author-1');
        $piece->add_reference('publisher', '#organization');
        
        $this->assertEquals(['@id' => '#author-1'], $piece->get('author'));
        $this->assertEquals(['@id' => '#organization'], $piece->get('publisher'));
    }

    public function testToArray(): void
    {
        $piece = new SchemaPiece('#article', 'Article');
        $piece
            ->set('headline', 'Test Article')
            ->set('datePublished', '2024-01-01')
            ->add_reference('author', '#author');
        
        $expected = [
            '@type' => 'Article',
            '@id' => '#article',
            'headline' => 'Test Article',
            'datePublished' => '2024-01-01',
            'author' => ['@id' => '#author']
        ];
        
        $this->assertEquals($expected, $piece->to_array());
    }

    public function testFromArray(): void
    {
        $piece = new SchemaPiece('#test', 'Article');
        
        $data = [
            'headline' => 'Breaking News',
            'datePublished' => '2024-01-01'
        ];
        
        $piece->from_array($data);
        
        // Type and ID should not change when using from_array
        $this->assertEquals('Article', $piece->get_type());
        $this->assertEquals('#test', $piece->get_id());
        $this->assertEquals('Breaking News', $piece->get('headline'));
        $this->assertEquals('2024-01-01', $piece->get('datePublished'));
    }

    public function testHas(): void
    {
        $piece = new SchemaPiece('#test', 'Article');
        $piece->set('headline', 'Test');
        
        $this->assertTrue($piece->has('headline'));
        $this->assertFalse($piece->has('description'));
    }

    public function testRemove(): void
    {
        $piece = new SchemaPiece('#test', 'Article');
        $piece->set('headline', 'Test');
        $piece->set('description', 'Description');
        
        $piece->remove('description');
        
        $this->assertTrue($piece->has('headline'));
        $this->assertFalse($piece->has('description'));
    }

    public function testMerge(): void
    {
        $piece = new SchemaPiece('#test', 'Article');
        $piece->set('headline', 'Original');
        $piece->set('author', '#author1');
        
        $newData = [
            'headline' => 'Updated',
            'datePublished' => '2024-01-01'
        ];
        
        $piece->merge($newData);
        
        $this->assertEquals('Updated', $piece->get('headline'));
        $this->assertEquals('#author1', $piece->get('author'));
        $this->assertEquals('2024-01-01', $piece->get('datePublished'));
    }
}