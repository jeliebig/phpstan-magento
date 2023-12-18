<?php
/*
 * This file is part of the phpstan-magento package.
 *
 * (c) bitExpert AG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace bitExpert\PHPStan\Magento\Reflection\DataProvider;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class DependencyInjectionDataProvider
{
    /**
     * @var string
     */
    private $magentoRoot;
    /**
     * @var DOMDocument[]|null
     */
    private $xmlDocs;

    /**
     * ExtensionAttributeDataProvider constructor.
     *
     * @param string $magentoRoot
     */
    public function __construct(string $magentoRoot)
    {
        $this->magentoRoot = $magentoRoot;
    }

    /**
     * Returns
     * @param string $sourceInterface
     * @return array<string>
     */
    public function getPreferenceForInterface(string $sourceInterface): array
    {
        $return = [];

        foreach ($this->getDependencyInjectionXmlDocs() as $doc) {
            $xpath = new DOMXPath($doc);
            $prefs = $xpath->query(
                sprintf('//preference[@for="%s"]', $sourceInterface),
                $doc->documentElement
            );

            if ($prefs === false) {
                continue;
            }

            foreach ($prefs as $pref) {
                /** @var DOMElement $pref */
                $return[] = $pref->getAttribute('type');
            }
        }

        return $return;
    }


    /**
     * Create a generator which creates DOM documents for every dependency injection XML file found.
     *
     * @return DOMDocument[]
     */
    protected function getDependencyInjectionXmlDocs(): array
    {
        if (is_array($this->xmlDocs)) {
            return $this->xmlDocs;
        }

        $finder = Finder::create()
            ->files()
            ->in($this->magentoRoot)
            ->name('di.xml')
            ->filter(static function (SplFileInfo $file) {
                // ignore any files not located in an etc directory to exclude e.g. test data
                return $file->isFile() && (bool) preg_match('#etc/di.xml$#', $file->getPathname());
            });

        $this->xmlDocs = [];
        foreach ($finder as $item) {
            /** @var SplFileInfo $item */
            $doc = new DOMDocument();
            $doc->loadXML($item->getContents());
            $this->xmlDocs[] = $doc;
        }

        return $this->xmlDocs;
    }
}
