<?php

namespace Civi\SmartyClassifier;

use PHPUnit\Framework\TestCase;

/**
 *
 */
class ExampleFilesTest extends TestCase {

  public function getExampleTemplates(): array {
    $prj = dirname(__DIR__);
    $files = glob("$prj/examples/*.tpl");
    foreach ($files as $file) {
      foreach (Reports::getReportList() as $name) {
        $cases[basename($file, '.tpl') . ".d/$name"] = [$file, $name];
      }
    }

    return $cases;
  }

  /**
   * @param string $tplFile
   * @param string $name
   * @dataProvider getExampleTemplates
   */
  public function testExampleTemplates(string $tplFile, string $name): void {
    $parsed = Services::createTopParser()->parse(file_get_contents($tplFile));
    $reportFile = preg_replace(';\.tpl$;', ".d/$name.txt", $tplFile);
    $expectReport = file_get_contents($reportFile);
    $actualReport = Reports::writeString($name, $parsed);
    $this->assertEquals($expectReport, $actualReport);
  }

  public function getExampleTags(): array {
    $prj = dirname(__DIR__);
    $files = glob("$prj/examples/*.d/tag-*.tpl");
    foreach ($files as $file) {
      $cases[basename($file, '.tpl')] = [$file, 'tree'];
    }
    return $cases;
  }

  /**
   * @param string $tplFile
   * @param string $name
   * @dataProvider getExampleTags
   */
  public function testExampleTags(string $tplFile, string $name): void {
    $parsed = Services::createTagParser()->parse(file_get_contents($tplFile));
    $name = 'tree';
    $reportFile = preg_replace(';\.tpl$;', ".$name", $tplFile);
    $expectReport = file_get_contents($reportFile);
    $actualReport = Reports::writeString($name, $parsed);
    $this->assertEquals($expectReport, $actualReport);
  }

}
