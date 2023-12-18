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

namespace bitExpert\PHPStan\Magento\Reflection\Framework;

use bitExpert\PHPStan\Magento\Reflection\AbstractMagicMethodReflectionExtension;
use bitExpert\PHPStan\Magento\Reflection\DataProvider\DependencyInjectionDataProvider;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;

class DataObjectMagicMethodReflectionExtension extends AbstractMagicMethodReflectionExtension
{
    /* @var ReflectionProvider $reflectionProvider */
    private $reflectionProvider;
    /* @var DependencyInjectionDataProvider $dataProvider */
    private $dataProvider;

    public function __construct(ReflectionProvider $reflectionProvider, DependencyInjectionDataProvider $dataProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->dataProvider = $dataProvider;
    }

    /**
     * @param ClassReflection $classReflection
     * @param string $methodName
     * @return bool
     */
    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        $parentClasses = $classReflection->getParentClassesNames();
        $parentClasses[] = $classReflection->getName();

        if ($classReflection->isInterface()) {
            foreach ($this->dataProvider->getPreferenceForInterface($classReflection->getName()) as $pref) {
                $parentClasses = array_merge(
                    $parentClasses,
                    $this->reflectionProvider->getClass($pref)->getParentClassesNames()
                );
            }
        }

        return in_array('Magento\Framework\DataObject', $parentClasses, true) &&
            in_array(substr($methodName, 0, 3), ['get', 'set', 'uns', 'has'], true);
    }
}
