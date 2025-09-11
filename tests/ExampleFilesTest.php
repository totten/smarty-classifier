<?php

namespace Civi\SmartyClassifier;

use PHPUnit\Framework\TestCase;

/**
 *
 */
class ExampleFilesTest extends TestCase {

  public function getExamples(): array {
    $prj = dirname(__DIR__);
    $files = glob("$prj/examples/*.tpl");
    foreach ($files as $file) {
      foreach (Reports::getReportList() as $suffix => $generator) {
        $cases[basename($file, '.tpl') . $suffix] = [$file, $suffix];
      }
    }

    return $cases;
  }

  /**
   * @param string $tplFile
   * @param string $suffix
   * @dataProvider getExamples
   */
  public function testExample(string $tplFile, string $suffix): void {
    $parsed = Services::createTopParser()->parse(file_get_contents($tplFile));
    $generator = Reports::getReportList()[$suffix];

    $reportFile = preg_replace(';\.tpl$;', $suffix, $tplFile);
    $expectReport = file_get_contents($reportFile);

    $fh = fopen('php://temp', 'r+');
    call_user_func($generator, $fh, $parsed);
    rewind($fh);
    $actualReport = stream_get_contents($fh);
    fclose($fh);

    $this->assertEquals($expectReport, $actualReport);
  }

}
