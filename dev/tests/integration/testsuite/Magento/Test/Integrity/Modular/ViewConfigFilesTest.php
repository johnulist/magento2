<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Test\Integrity\Modular;

use Magento\Framework\App\Filesystem\DirectoryList;

class ViewConfigFilesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $file
     * @dataProvider viewConfigFileDataProvider
     */
    public function testViewConfigFile($file)
    {
        $reader = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(
            'Magento\Framework\View\Xsd\Reader'
        );
        $mergeXsd = $reader->read();
        $domConfig = new \Magento\Framework\Config\Dom($file);
        $result = $domConfig->validate(
            $mergeXsd,
            $errors
        );
        $message = "Invalid XML-file: {$file}\n";
        foreach ($errors as $error) {
            $message .= "{$error->message} Line: {$error->line}\n";
        }
        $this->assertTrue($result, $message);
    }

    /**
     * @return array
     */
    public function viewConfigFileDataProvider()
    {
        $result = [];
        $files = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(
            'Magento\Framework\Module\Dir\Reader'
        )->getConfigurationFiles(
            'view.xml'
        );
        foreach ($files as $file) {
            $result[] = [$file];
        }
        return $result;
    }
}
