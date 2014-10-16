<?php

/**
 * @file
 * Contains \Drupal\search_api\Plugin\SearchApi\Processor\IgnoreCharacter.
 */

namespace Drupal\search_api\Plugin\SearchApi\Processor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Processor\FieldsProcessorPluginBase;

/**
 * @SearchApiProcessor(
 *   id = "ignore_character",
 *   label = @Translation("Ignore characters"),
 *   description = @Translation("Configure types of characters which should be ignored for searches.")
 * )
 */
class IgnoreCharacter extends FieldsProcessorPluginBase {

  /**
   * The escaped regular expression for ignorable characters.
   *
   * @var string
   */
  protected $ignorable;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    // @todo As elsewhere, the "character_sets" setting should only contain the
    //   enabled classes, in a numeric array.
    // @todo Also, nesting this setting makes no sense.
    return array(
      'ignorable' => "['¿¡!?,.:;]",
      'strip' => array(
        'character_sets' => array(
          'Pc' => 'Pc',
          'Pd' => 'Pd',
          'Pe' => 'Pe',
          'Pf' => 'Pf',
          'Pi' => 'Pi',
          'Po' => 'Po',
          'Ps' => 'Ps',
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['ignorable'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Strip by regular expression'),
      '#description' => $this->t('Specify characters which should be removed from fulltext fields and search strings, as a <a href="@url">PCRE regular expression</a>.', array('@url' => _url('http://php.net/manual/en/reference.pcre.pattern.syntax.php'))),
      '#default_value' => $this->configuration['ignorable'],
    );

    $character_sets = $this->getCharacterSets();
    $form['strip'] = array(
      '#type' => 'details',
      '#title' => $this->t('Strip by character property'),
      '#description' => $this->t('Specify <a href="@url">Unicode character properties</a> of characters to be ignored.', array('@url' => _url('http://www.fileformat.info/info/unicode/category/index.htm'))),
      '#open' => FALSE,
    );
    $form['strip']['character_sets'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Ignored character properties'),
      '#options' => $character_sets,
      '#default_value' => $this->configuration['strip']['character_sets'],
      '#multiple' => TRUE,
    );

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    $ignorable = str_replace('/', '\/', $form_state->getValues()['ignorable']);
    if (@preg_match('/(' . $ignorable . ')+/u', '') === FALSE) {
      $el = $form['ignorable'];
      $form_state->setError($el, $el['#title'] . ': ' . $this->t('The entered text is no valid regular expression.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function process(&$value) {
    if ($this->configuration['ignorable']) {
      if (!isset($this->ignorable)) {
        $this->ignorable = str_replace('/', '\/', $this->configuration['ignorable']);
      }
      $value = preg_replace('/' . $this->ignorable . '+/u', '', $value);
    }

    // Loop over the character sets and strip the characters from the text.
    foreach ($this->configuration['strip']['character_sets'] as $character_set) {
      $regex = $this->getFormatRegularExpression($character_set);
      if (!empty($regex)) {
        $value = preg_replace('/[' . $regex . ']+/u', '', $value);
      }
    }
  }

  /**
   * Retrieves an options list for available Unicode character properties.
   *
   * @return string[]
   *   An options list with all available Unicode character properties.
   */
  protected function getCharacterSets() {
    return array(
      'Pc' => $this->t('Punctuation, Connector Characters'),
      'Pd' => $this->t('Punctuation, Dash Characters'),
      'Pe' => $this->t('Punctuation, Close Characters'),
      'Pf' => $this->t('Punctuation, Final quote Characters'),
      'Pi' => $this->t('Punctuation, Initial quote Characters'),
      'Po' => $this->t('Punctuation, Other Characters'),
      'Ps' => $this->t('Punctuation, Open Characters'),

      'Cc' => $this->t('Other, Control Characters'),
      'Cf' => $this->t('Other, Format Characters'),
      'Co' => $this->t('Other, Private Use Characters'),

      'Mc' => $this->t('Mark, Spacing Combining Characters'),
      'Me' => $this->t('Mark, Enclosing Characters'),
      'Mn' => $this->t('Mark, Nonspacing Characters'),

      'Sc' => $this->t('Symbol, Currency Characters'),
      'Sk' => $this->t('Symbol, Modifier Characters'),
      'Sm' => $this->t('Symbol, Math Characters'),
      'So' => $this->t('Symbol, Other Characters'),

      'Zl' => $this->t('Separator, Line Characters'),
      'Zp' => $this->t('Separator, Paragraph Characters'),
      'Zs' => $this->t('Separator, Space Characters'),
    );
  }

  /**
   * @param $character_set
   * @return bool|string
   */
  protected function getFormatRegularExpression($character_set) {
    $class = 'Drupal\search_api\Plugin\SearchApi\Processor\Resources\\' . $character_set;
    if (class_exists($class) && in_array('Drupal\search_api\Plugin\SearchApi\Processor\Resources\UnicodeCharacterPropertyInterface', class_implements($class))) {
      return $class::getRegularExpression();
    }
    return FALSE;
  }

}