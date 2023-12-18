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

use bitExpert\PHPStan\Magento\Autoload\DataProvider\ExtensionAttributeDataProvider;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class DependencyInjectionDataProviderUnitTest extends TestCase
{
    /**
     * @var \org\bovigo\vfs\vfsStreamDirectory
     */
    private $root;

    protected function setUp(): void
    {
        $this->root = vfsStream::setup('test');
    }

    /**
     * @test
     */
    public function returnsArrayWhenPreferenceForInterfaceExist(): void
    {
        vfsStream::create([
            'etc' => [
                'di.xml' => <<<'XML'
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:ObjectManager/etc/config.xsd">
    <preference for="Magento\Sales\Api\Data\OrderInterface" type="Magento\Sales\Model\Order"/>
</config>
XML
            ]
        ], $this->root);

        $dataprovider = new DependencyInjectionDataProvider($this->root->url());
        $preferences = $dataprovider->getPreferenceForInterface('Magento\Sales\Api\Data\OrderInterface');

        static::assertCount(1, $preferences);
        static::assertSame('Magento\Sales\Model\Order', $preferences[0]);
    }

    /**
     * @test
     */
    public function returnsEmptyArrayWhenNoPreferencesForInterfaceExist(): void
    {
        vfsStream::create([
            'etc' => [
                'di.xml' => <<<'XML'
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:ObjectManager/etc/config.xsd">
    <preference for="Magento\Sales\Api\Data\OrderInterface" type="Magento\Sales\Model\Order"/>
</config>
XML
            ]
        ], $this->root);

        $dataprovider = new DependencyInjectionDataProvider($this->root->url());
        $preferences = $dataprovider->getPreferenceForInterface('Some\Random\Api\Data\SampleInterface');

        static::assertCount(0, $preferences);
    }

    /**
     * @test
     */
    public function loadsAndMergesPreferencesFromDifferentSourceFiles(): void
    {
        vfsStream::create([
            'etc' => [
                'di.xml' => <<<'XML'
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:ObjectManager/etc/config.xsd">
    <preference for="Magento\Sales\Api\Data\OrderInterface" type="Magento\Sales\Model\Order"/>
</config>
XML
            ],
            'vendor' => [
                'vendor1' => [
                    'package1' => [
                        'etc' => [
                            'di.xml' => <<<'XML'
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:ObjectManager/etc/config.xsd">
    <preference for="Magento\Sales\Api\Data\OrderInterface" type="Another\Model\Order"/>
</config>
XML
                        ]
                    ]
                ]
            ]
        ], $this->root);

        $dataprovider = new DependencyInjectionDataProvider($this->root->url());
        $preferences = $dataprovider->getPreferenceForInterface('Magento\Sales\Api\Data\OrderInterface');

        static::assertCount(2, $preferences);
        static::assertSame('Magento\Sales\Model\Order', $preferences[0]);
        static::assertSame('Another\Model\Order', $preferences[1]);
    }

    /**
     * @test
     */
    public function ignoresFilesInNonEtcDir(): void
    {
        vfsStream::create([
            'test' => [
                'My' => [
                    'Namespace' => [
                        'di.xml' => <<<'XML'
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:ObjectManager/etc/config.xsd">
    <preference for="Magento\Sales\Api\Data\OrderInterface" type="Magento\Sales\Model\Order"/>
</config>
XML
                    ]
                ]
            ]
        ], $this->root);

        $dataprovider = new DependencyInjectionDataProvider($this->root->url());
        $preferences = $dataprovider->getPreferenceForInterface('Magento\Sales\Api\Data\OrderInterface');

        static::assertCount(0, $preferences);
    }
}
