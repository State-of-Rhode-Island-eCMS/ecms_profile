<?php

declare(strict_types=1);

namespace Drupal\ecms_asset_injector;

use Sabberworm\CSS\Parser;
use Sabberworm\CSS\OutputFormat;
use Sabberworm\CSS\CSSList\Document;
use Sabberworm\CSS\CSSList\AtRuleBlockList;
use Sabberworm\CSS\RuleSet\DeclarationBlock;
use Sabberworm\CSS\RuleSet\RuleSet;
use Sabberworm\CSS\Rule\Rule;

/**
 * Processes CSS to scope color-related rules to light mode only.
 *
 * This ensures that client-entered CSS only affects light mode by prefixing
 * color-related selectors with html:not(.dark), leaving dark mode unaffected.
 */
class CssColorProcessor {

  /**
   * CSS properties that should be restricted to light mode only.
   */
  protected const COLOR_PROPERTIES = [
    'color',
    'background-color',
    'background',
    'border-color',
    'border-top-color',
    'border-right-color',
    'border-bottom-color',
    'border-left-color',
    'outline-color',
    'text-decoration-color',
    'box-shadow',
    'text-shadow',
  ];

  /**
   * Process CSS and scope color-related rules to light mode only.
   *
   * @param string $css
   *   The original CSS code.
   *
   * @return string
   *   The processed CSS with color rules scoped to html:not(.dark).
   */
  public function process(string $css): string {
    if (empty(trim($css))) {
      return $css;
    }

    try {
      $parser = new Parser($css);
      $document = $parser->parse();
    }
    catch (\Exception $e) {
      // If parsing fails, return original CSS unchanged.
      return $css;
    }

    $lightModeRules = [];
    $this->extractColorRules($document, $lightModeRules);

    // If no color rules were found, return original CSS.
    if (empty($lightModeRules)) {
      return $css;
    }

    // Render the modified document and append the light-mode scoped rules.
    $outputFormat = OutputFormat::createPretty();
    $processedCss = $document->render($outputFormat);
    $processedCss .= "\n\n" . $this->buildLightModeRules($lightModeRules);

    return $processedCss;
  }

  /**
   * Extract color-related rules from CSS and remove them from original.
   *
   * @param \Sabberworm\CSS\CSSList\Document $document
   *   The parsed CSS document.
   * @param array $lightModeRules
   *   Array to collect rules that should be in light-mode media query.
   */
  protected function extractColorRules(Document $document, array &$lightModeRules): void {
    foreach ($document->getContents() as $item) {
      if ($item instanceof DeclarationBlock) {
        $this->processDeclarationBlock($item, $lightModeRules);
      }
      elseif ($item instanceof AtRuleBlockList) {
        // Skip processing rules already inside media queries.
        // Users who intentionally use media queries know what they're doing.
        continue;
      }
    }
  }

  /**
   * Process a declaration block and extract color rules.
   *
   * @param \Sabberworm\CSS\RuleSet\DeclarationBlock $block
   *   The declaration block to process.
   * @param array $lightModeRules
   *   Array to collect rules for light-mode media query.
   */
  protected function processDeclarationBlock(DeclarationBlock $block, array &$lightModeRules): void {
    $colorRules = [];
    $rulesToRemove = [];

    foreach ($block->getRules() as $rule) {
      $property = strtolower($rule->getRule());

      if ($this->isColorProperty($property)) {
        $colorRules[] = clone $rule;
        $rulesToRemove[] = $rule;
      }
    }

    // If we found color rules, create a copy of the selector with only color rules.
    if (!empty($colorRules)) {
      // Remove color rules from original block.
      foreach ($rulesToRemove as $rule) {
        $block->removeRule($rule);
      }

      // Create a new declaration block with the same selectors.
      $newBlock = new DeclarationBlock();
      $newBlock->setSelectors($block->getSelectors());

      // Add the color rules to the new block.
      foreach ($colorRules as $rule) {
        $newBlock->addRule($rule);
      }

      $lightModeRules[] = $newBlock;
    }
  }

  /**
   * Check if a CSS property is color-related.
   *
   * @param string $property
   *   The CSS property name.
   *
   * @return bool
   *   TRUE if the property affects colors.
   */
  protected function isColorProperty(string $property): bool {
    return in_array($property, self::COLOR_PROPERTIES, TRUE);
  }

  /**
   * Build light-mode scoped rules with html:not(.dark) prefix.
   *
   * @param array $rules
   *   Array of DeclarationBlock objects.
   *
   * @return string
   *   The rendered CSS with prefixed selectors.
   */
  protected function buildLightModeRules(array $rules): string {
    $outputFormat = OutputFormat::createPretty();
    $rulesContent = '';

    foreach ($rules as $block) {
      // Prefix each selector with html:not(.dark).
      $selectors = $block->getSelectors();
      $prefixedSelectors = [];

      foreach ($selectors as $selector) {
        $selectorString = (string) $selector;
        $prefixedSelectors[] = 'html:not(.dark) ' . $selectorString;
      }

      $block->setSelectors($prefixedSelectors);
      $rulesContent .= $block->render($outputFormat) . "\n";
    }

    return trim($rulesContent);
  }

}
