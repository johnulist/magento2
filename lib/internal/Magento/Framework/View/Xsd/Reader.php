<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\View\Xsd;

use Magento\Framework\Filesystem;
use Magento\Framework\Config\FileIteratorFactory;
use Magento\Framework\App\Filesystem\DirectoryList;

class Reader implements \Magento\Framework\Config\ReaderInterface
{
    /**
     * @var string
     */
    protected $defaultScope;

    /**
     * @var string
     */
    protected $fileName;

    /**
     * @var \Magento\Framework\Filesystem\Directory\ReadInterface
     */
    protected $directoryRead;

    /**
     * @var \Magento\Framework\Config\FileIteratorFactory
     */
    protected $iteratorFactory;

    /**
     * @var string
     */
    protected $searchPattern;

    /**
     * @var string
     */
    protected $searchFilesPattern;

    /**
     * @param Filesystem $filesystem
     * @param FileIteratorFactory $iteratorFactory
     * @param string $fileName
     * @param string $defaultScope
     * @param string $searchPattern
     * @param string $searchFilesPattern
     */
    public function __construct(
        Filesystem $filesystem,
        FileIteratorFactory $iteratorFactory,
        $fileName,
        $defaultScope,
        $searchPattern,
        $searchFilesPattern
    ) {
        $this->directoryRead = $filesystem->getDirectoryRead(DirectoryList::MODULES);
        $this->iteratorFactory = $iteratorFactory;
        $this->fileName = $fileName;
        $this->defaultScope = $defaultScope;
        $this->searchPattern = $searchPattern;
        $this->searchFilesPattern = $searchFilesPattern;
    }

    /**
     * Get list of xsd files
     *
     * @param string $filename
     * @return \Magento\Framework\Config\FileIterator
     */
    public function getListXsdFiles($filename)
    {
        $iterator = $this->iteratorFactory->create(
            $this->directoryRead,
            $this->directoryRead->search($this->searchPattern . $filename)
        );
        return $iterator;
    }

    /**
     * Read xsd files from list
     *
     * @param null $scope
     * @return array|\Magento\Framework\Config\FileIterator
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function read($scope = null)
    {
        $fileList = $this->getListXsdFiles($this->fileName);
        if (!count($fileList)) {
            return [];
        }
        $mergeXsd = $this->readXsdFiles($fileList);

        return $mergeXsd;
    }

    /**
     * Get merged xsd file
     *
     * @param array $fileList
     * @param string $baseXsd
     * @return null|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function readXsdFiles($fileList, $baseXsd = null)
    {
        $baseXsd = new \DOMDocument();
        $baseXsd->load(__DIR__ . $this->searchFilesPattern . $this->fileName);
        $configMerge = null;
        foreach ($fileList as $key => $content) {
            try {
                if (!empty($content)) {
                    if ($configMerge) {
                        $configMerge = $this->mergeXsd($configMerge, $content);
                    } else {
                        $configMerge = $this->mergeXsd($baseXsd->saveXML(), $content);
                    }
                }
            } catch (\Magento\Framework\Config\Dom\ValidationException $e) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    new \Magento\Framework\Phrase("Invalid XSD in file %1:\n%2", [$key, $e->getMessage()])
                );
            }
        }

        return $configMerge;
    }

    /**
     * Merge xsd files
     *
     * @param string $parent
     * @param string $child
     * @return string
     */
    protected function mergeXsd($parent, $child)
    {
        $domParent = $this->createDomInstance($parent);
        $domChild = $this->createDomInstance($child);
        $domChild = $domChild->documentElement;

        $domParentElement = $domParent->getElementsByTagName('complexType');
        $parentDomElements = $this->findDomElement($domParentElement, 'name');
        foreach ($parentDomElements->childNodes as $findElement) {
            if ($findElement instanceof \DOMElement) {
                $domParentNode = $findElement;
                break;
            }
        }
        $domChildElement = $domChild->getElementsByTagName('extension');
        $childDomElements = $this->findDomElement($domChildElement, 'base');
        $domParent = $this->addHeadChildIntoParent($childDomElements, $domParent, $domParentNode);
        $delete = $domChild->getElementsByTagName('redefine')->item(0);
        $domChild->removeChild($delete);
        $domParent = $this->addBodyChildIntoParent($domChild, $domParent);

        return $domParent->saveXML();
    }

    /**
     * Create DOM instance
     *
     * @param string $source
     * @return \DOMDocument
     */
    protected function createDomInstance($source)
    {
        $domInstance = new \DOMDocument('1.0', 'UTF-8');
        $domInstance->formatOutput = true;
        $domInstance->loadXML($source);
        $domInstance->preserveWhiteSpace = true;

        return $domInstance;
    }

    /**
     * Find searched element in DOM
     *
     * @param \DOMNodeList $domParentElement
     * @param string $attribute
     * @return mixed
     */
    protected function findDomElement(\DOMNodeList $domParentElement, $attribute)
    {
        foreach ($domParentElement as $child) {
            if ($child->getAttribute($attribute) === 'mediaType'
                && $child instanceof \DOMElement
                && $child->hasChildNodes()
            ) {
                return $child;
            }
        }
    }

    /**
     * Add into parent head elements from child
     *
     * @param \DOMElement $childDomElements
     * @param \DOMDocument $domParent
     * @param \DOMElement $domParentNode
     * @return \DOMDocument
     */
    protected function addHeadChildIntoParent(
        \DOMElement $childDomElements,
        \DOMDocument $domParent,
        \DOMElement $domParentNode
    ) {
        foreach ($childDomElements->childNodes as $sequence) {
            if ($sequence instanceof \DOMElement && $sequence->hasChildNodes()) {
                foreach ($sequence->childNodes as $findElement) {
                    if ($findElement instanceof \DOMElement) {
                        $importedNodes = $domParent->importNode($findElement, true);
                        $domParentNode->appendChild($importedNodes);
                    }
                }
            }
        }

        return $domParent;
    }

    /**
     * Add into parent body elements from child
     *
     * @param \DOMElement $domChild
     * @param \DOMDocument $domParent
     * @return \DOMDocument
     */
    protected function addBodyChildIntoParent(\DOMElement $domChild, \DOMDocument $domParent)
    {
        foreach ($domChild->childNodes as $node) {
            $importNode = $domParent->importNode($node, true);
            $domParent->documentElement->appendChild($importNode);
        }

        return $domParent;
    }
}
