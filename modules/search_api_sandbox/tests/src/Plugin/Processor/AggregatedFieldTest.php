<?php
/**
 * @file
 * Contains \Drupal\Tests\search_api\Plugin\Processor\AggregatedFieldTest.
 */

namespace Drupal\Tests\search_api\Plugin\Processor;

use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\search_api\Plugin\SearchApi\Processor\AggregatedField;
use Drupal\search_api\Tests\Processor\TestItemsTrait;
use Drupal\search_api\Utility\Utility;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the "URL field" processor.
 *
 * @group search_api
 *
 * @see \Drupal\search_api\Plugin\SearchApi\Processor\AggregatedField
 */
class AggregatedFieldTest extends UnitTestCase {

  use TestItemsTrait;

  /**
   * Stores the processor to be tested.
   *
   * @var \Drupal\search_api\Plugin\SearchApi\Processor\AggregatedField
   */
  protected $processor;

  /**
   * A mocked search index for the tests.
   *
   * @var \Drupal\search_api\Index\IndexInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $index;

  /**
   * Creates a new processor object for use in the tests.
   */
  protected function setUp() {
    parent::setUp();

    $this->index = $this->getMock('Drupal\search_api\Index\IndexInterface');
    $this->processor = new AggregatedField(array('index' => $this->index), 'aggregated_field', array());
  }

  /**
   * Tests creation of an aggregated field of type "union".
   */
  public function testUnionAggregation() {
    $field_id = 'search_api_aggregation_1';
    $configuration = array(
      'fields' => array(
        $field_id => array(
          'label' => 'Test field',
          'type' => 'union',
          'fields' => array(
            'entity:test1|foo',
            'entity:test1|foo:bar',
            'entity:test2|foobaz:bla',
          ),
        ),
      ),
    );
    $this->processor->setConfiguration($configuration);

    $fields = array(
      'entity:test1|foo' => array(
        'type' => 'string',
        'values' => array('foo', 'bar'),
      ),
      'entity:test1|foo:bar' => array(
        'type' => 'string',
        'values' => array('baz'),
      ),
      'entity:test2|foobaz:bla' => array(
        'type' => 'string',
        'values' => array('foobar'),
      ),
      $field_id => array(
        'type' => 'string',
      ),
    );
    $items = $this->createItems($this->index, 2, $fields, NULL, array('entity:test1', 'entity:test2'));

    $this->processor->preprocessIndexItems($items);

    $expected = array('foo', 'bar', 'baz');
    $this->assertEquals($expected, $items[$this->item_ids[0]]->getField($field_id)->getValues(), 'Correct "union" aggregation for item 1.');

    $expected = array('foobar');
    $this->assertEquals($expected, $items[$this->item_ids[1]]->getField($field_id)->getValues(), 'Correct "union" aggregation for item 1.');
  }

  /**
   * Tests creation of an aggregated field of type "concat".
   */
  public function testConcatAggregation() {
    $field_id = 'search_api_aggregation_1';
    $configuration = array(
      'fields' => array(
        $field_id => array(
          'label' => 'Test field',
          'type' => 'concat',
          'fields' => array(
            'entity:test1|foo',
            'entity:test1|foo:bar',
            'entity:test2|foobaz:bla',
          ),
        ),
      ),
    );
    $this->processor->setConfiguration($configuration);

    $fields = array(
      'entity:test1|foo' => array(
        'type' => 'string',
        'values' => array('foo', 'bar'),
      ),
      'entity:test1|foo:bar' => array(
        'type' => 'string',
        'values' => array('baz'),
      ),
      'entity:test2|foobaz:bla' => array(
        'type' => 'string',
        'values' => array('foobar'),
      ),
      $field_id => array(
        'type' => 'string',
      ),
    );
    $items = $this->createItems($this->index, 2, $fields, NULL, array('entity:test1', 'entity:test2'));

    $this->processor->preprocessIndexItems($items);

    $expected = array("foo\n\nbar\n\nbaz");
    $this->assertEquals($expected, $items[$this->item_ids[0]]->getField($field_id)->getValues(), 'Correct "concat" aggregation for item 1.');

    $expected = array('foobar');
    $this->assertEquals($expected, $items[$this->item_ids[1]]->getField($field_id)->getValues(), 'Correct "concat" aggregation for item 1.');
  }

  /**
   * Tests creation of an aggregated field of type "sum".
   */
  public function testSumAggregation() {
    $field_id = 'search_api_aggregation_1';
    $configuration = array(
      'fields' => array(
        $field_id => array(
          'label' => 'Test field',
          'type' => 'sum',
          'fields' => array(
            'entity:test1|foo',
            'entity:test1|foo:bar',
            'entity:test2|foobaz:bla',
          ),
        ),
      ),
    );
    $this->processor->setConfiguration($configuration);

    $fields = array(
      'entity:test1|foo' => array(
        'type' => 'integer',
        'values' => array(2, 4),
      ),
      'entity:test1|foo:bar' => array(
        'type' => 'integer',
        'values' => array(16),
      ),
      'entity:test2|foobaz:bla' => array(
        'type' => 'integer',
        'values' => array(7),
      ),
      $field_id => array(
        'type' => 'integer',
      ),
    );
    $items = $this->createItems($this->index, 2, $fields, NULL, array('entity:test1', 'entity:test2'));

    $this->processor->preprocessIndexItems($items);

    $expected = array(22);
    $this->assertEquals($expected, $items[$this->item_ids[0]]->getField($field_id)->getValues(), 'Correct "sum" aggregation for item 1.');

    $expected = array(7);
    $this->assertEquals($expected, $items[$this->item_ids[1]]->getField($field_id)->getValues(), 'Correct "sum" aggregation for item 1.');
  }

  /**
   * Tests creation of an aggregated field of type "count".
   */
  public function testCountAggregation() {
    $field_id = 'search_api_aggregation_1';
    $configuration = array(
      'fields' => array(
        $field_id => array(
          'label' => 'Test field',
          'type' => 'count',
          'fields' => array(
            'entity:test1|foo',
            'entity:test1|foo:bar',
            'entity:test2|foobaz:bla',
          ),
        ),
      ),
    );
    $this->processor->setConfiguration($configuration);

    $fields = array(
      'entity:test1|foo' => array(
        'type' => 'string',
        'values' => array('foo', 'bar'),
      ),
      'entity:test1|foo:bar' => array(
        'type' => 'string',
        'values' => array('baz'),
      ),
      'entity:test2|foobaz:bla' => array(
        'type' => 'string',
        'values' => array('foobar'),
      ),
      $field_id => array(
        'type' => 'string',
      ),
    );
    $items = $this->createItems($this->index, 2, $fields, NULL, array('entity:test1', 'entity:test2'));

    $this->processor->preprocessIndexItems($items);

    $expected = array(3);
    $this->assertEquals($expected, $items[$this->item_ids[0]]->getField($field_id)->getValues(), 'Correct "count" aggregation for item 1.');

    $expected = array(1);
    $this->assertEquals($expected, $items[$this->item_ids[1]]->getField($field_id)->getValues(), 'Correct "count" aggregation for item 1.');
  }

  /**
   * Tests creation of an aggregated field of type "max".
   */
  public function testMaxAggregation() {
    $field_id = 'search_api_aggregation_1';
    $configuration = array(
      'fields' => array(
        $field_id => array(
          'label' => 'Test field',
          'type' => 'max',
          'fields' => array(
            'entity:test1|foo',
            'entity:test1|foo:bar',
            'entity:test2|foobaz:bla',
          ),
        ),
      ),
    );
    $this->processor->setConfiguration($configuration);

    $fields = array(
      'entity:test1|foo' => array(
        'type' => 'integer',
        'values' => array(2, 4),
      ),
      'entity:test1|foo:bar' => array(
        'type' => 'integer',
        'values' => array(16),
      ),
      'entity:test2|foobaz:bla' => array(
        'type' => 'integer',
        'values' => array(7),
      ),
      $field_id => array(
        'type' => 'integer',
      ),
    );
    $items = $this->createItems($this->index, 2, $fields, NULL, array('entity:test1', 'entity:test2'));

    $this->processor->preprocessIndexItems($items);

    $expected = array(16);
    $this->assertEquals($expected, $items[$this->item_ids[0]]->getField($field_id)->getValues(), 'Correct "max" aggregation for item 1.');

    $expected = array(7);
    $this->assertEquals($expected, $items[$this->item_ids[1]]->getField($field_id)->getValues(), 'Correct "max" aggregation for item 1.');
  }

  /**
   * Tests creation of an aggregated field of type "min".
   */
  public function testMinAggregation() {
    $field_id = 'search_api_aggregation_1';
    $configuration = array(
      'fields' => array(
        $field_id => array(
          'label' => 'Test field',
          'type' => 'min',
          'fields' => array(
            'entity:test1|foo',
            'entity:test1|foo:bar',
            'entity:test2|foobaz:bla',
          ),
        ),
      ),
    );
    $this->processor->setConfiguration($configuration);

    $fields = array(
      'entity:test1|foo' => array(
        'type' => 'integer',
        'values' => array(2, 4),
      ),
      'entity:test1|foo:bar' => array(
        'type' => 'integer',
        'values' => array(16),
      ),
      'entity:test2|foobaz:bla' => array(
        'type' => 'integer',
        'values' => array(7),
      ),
      $field_id => array(
        'type' => 'integer',
      ),
    );
    $items = $this->createItems($this->index, 2, $fields, NULL, array('entity:test1', 'entity:test2'));

    $this->processor->preprocessIndexItems($items);

    $expected = array(2);
    $this->assertEquals($expected, $items[$this->item_ids[0]]->getField($field_id)->getValues(), 'Correct "min" aggregation for item 1.');

    $expected = array(7);
    $this->assertEquals($expected, $items[$this->item_ids[1]]->getField($field_id)->getValues(), 'Correct "min" aggregation for item 1.');
  }

  /**
   * Tests creation of an aggregated field of type "first".
   */
  public function testFirstAggregation() {
    $field_id = 'search_api_aggregation_1';
    $configuration = array(
      'fields' => array(
        $field_id => array(
          'label' => 'Test field',
          'type' => 'first',
          'fields' => array(
            'entity:test1|foo',
            'entity:test1|foo:bar',
            'entity:test2|foobaz:bla',
          ),
        ),
      ),
    );
    $this->processor->setConfiguration($configuration);

    $fields = array(
      'entity:test1|foo' => array(
        'type' => 'string',
        'values' => array('foo', 'bar'),
      ),
      'entity:test1|foo:bar' => array(
        'type' => 'string',
        'values' => array('baz'),
      ),
      'entity:test2|foobaz:bla' => array(
        'type' => 'string',
        'values' => array('foobar'),
      ),
      $field_id => array(
        'type' => 'string',
      ),
    );
    $items = $this->createItems($this->index, 2, $fields, NULL, array('entity:test1', 'entity:test2'));

    $this->processor->preprocessIndexItems($items);

    $expected = array('foo');
    $this->assertEquals($expected, $items[$this->item_ids[0]]->getField($field_id)->getValues(), 'Correct "first" aggregation for item 1.');

    $expected = array('foobar');
    $this->assertEquals($expected, $items[$this->item_ids[1]]->getField($field_id)->getValues(), 'Correct "first" aggregation for item 1.');
  }

  /**
   * Tests whether unindexed aggregated fields are correctly skipped.
   */
  public function testUnindexedAggregatedField() {
    $configuration = array(
      'fields' => array(
        'search_api_aggregation_1' => array(
          'label' => 'Test field',
          'type' => 'union',
          'fields' => array(
            'entity:test1|foo',
            'entity:test1|foo:bar',
          ),
        ),
      ),
    );
    $this->processor->setConfiguration($configuration);

    $fields = array(
      'entity:test1|foo' => array(
        'type' => 'string',
        'values' => array('foo', 'bar'),
      ),
      'entity:test1|foo:bar' => array(
        'type' => 'string',
        'values' => array('baz'),
      ),
    );
    $items = $this->createItems($this->index, 2, $fields);

    $this->processor->preprocessIndexItems($items);

    $this->assertEquals(NULL, $items[$this->item_ids[0]]->getField('search_api_aggregation_1'), 'Unindexed aggregated field was not added for item 1.');
    $this->assertEquals(NULL, $items[$this->item_ids[1]]->getField('search_api_aggregation_1'), 'Unindexed aggregated field was not added for item 2.');
  }

  /**
   * Tests alterPropertyDefinitions.
   *
   * Checks for the correct DataDefinition added to the properties.
   */
  public function testAlterPropertyDefinitions() {
    $fields = array(
      'entity:test1|foo',
      'entity:test1|foo:bar',
      'entity:test2|foobaz:bla',
    );
    $index_fields = array();
    foreach ($fields as $field_id) {
      $field_object = Utility::createField($this->index, $field_id);
      list($prefix, $label) = str_replace(':', ' » ', Utility::splitCombinedId($field_id));
      $field_object->setLabelPrefix($prefix . ' » ');
      $field_object->setLabel($label);
      $index_fields[$field_id] = $field_object;
    }
    $this->index->expects($this->any())
      ->method('getFields')
      ->will($this->returnValue($index_fields));

    $configuration['fields'] = array(
      'search_api_aggregation_1' => array(
        'label' => 'Field 1',
        'type' => 'union',
        'fields' => array(
          'entity:test1|foo',
          'entity:test1|foo:bar',
          'entity:test2|foobaz:bla',
        ),
      ),
      'search_api_aggregation_2' => array(
        'label' => 'Field 2',
        'type' => 'max',
        'fields' => array(
          'entity:test1|foo:bar',
        ),
      ),
    );
    $this->processor->setConfiguration($configuration);

    /** @var \Drupal\Core\StringTranslation\TranslationInterface $translation */
    $translation = $this->getStringTranslationStub();
    $this->processor->setStringTranslation($translation);

    // Check for modified properties when no data source is given.
    $properties = array();
    $this->processor->alterPropertyDefinitions($properties, NULL);

    $property_added = array_key_exists('search_api_aggregation_1', $properties);
    $this->assertTrue($property_added, 'The "search_api_aggregation_1" property was added to the properties.');
    if ($property_added) {
      $this->assertInstanceOf('Drupal\Core\TypedData\DataDefinitionInterface', $properties['search_api_aggregation_1'], 'The "search_api_aggregation_1" property contains a valid data definition.');
      if ($properties['search_api_aggregation_1'] instanceof DataDefinitionInterface) {
        $this->assertEquals('string', $properties['search_api_aggregation_1']->getDataType(), 'Correct data type set in the data definition.');
        $this->assertEquals('Field 1', $properties['search_api_aggregation_1']->getLabel(), 'Correct label set in the data definition.');
        $description = $translation->translate('A @type aggregation of the following fields: @fields.', array('@type' => 'Union', '@fields' => 'entity » test1 » foo, entity » test1 » foo » bar, entity » test2 » foobaz » bla'));;
        $this->assertEquals($description, $properties['search_api_aggregation_1']->getDescription(), 'Correct description set in the data definition.');
      }
    }

    $property_added = array_key_exists('search_api_aggregation_2', $properties);
    $this->assertTrue($property_added, 'The "search_api_aggregation_2" property was added to the properties.');
    if ($property_added) {
      $this->assertInstanceOf('Drupal\Core\TypedData\DataDefinitionInterface', $properties['search_api_aggregation_2'], 'The "search_api_aggregation_2" property contains a valid data definition.');
      if ($properties['search_api_aggregation_2'] instanceof DataDefinitionInterface) {
        $this->assertEquals('integer', $properties['search_api_aggregation_2']->getDataType(), 'Correct data type set in the data definition.');
        $this->assertEquals('Field 2', $properties['search_api_aggregation_2']->getLabel(), 'Correct label set in the data definition.');
        $description = $translation->translate('A @type aggregation of the following fields: @fields.', array('@type' => 'Maximum', '@fields' => 'entity » test1 » foo » bar'));;
        $this->assertEquals($description, $properties['search_api_aggregation_2']->getDescription(), 'Correct description set in the data definition.');
      }
    }

    // Tests whether the properties of specific datasources stay untouched.
    $properties = array();
    /** @var \Drupal\search_api\Datasource\DatasourceInterface $datasource */
    $datasource = $this->getMock('Drupal\search_api\Datasource\DatasourceInterface');
    $this->processor->alterPropertyDefinitions($properties, $datasource);
    $this->assertEmpty($properties, 'Datasource-specific properties did not get changed.');
  }

}
