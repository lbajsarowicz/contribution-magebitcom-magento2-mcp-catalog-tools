<?php
/**
 * @author    Magebit <info@magebit.com>
 * @copyright Copyright (c) Magebit, Ltd. (https://magebit.com)
 * @license   MIT
 */
declare(strict_types=1);

namespace Magebit\McpCatalogTools\Test\Unit\Tool\Catalog\Category;

use Magebit\McpCatalogTools\Model\StoreScope;
use Magebit\McpCatalogTools\Tool\Catalog\Category\CategoryCreate;
use Magebit\McpCatalogTools\Tool\Catalog\Category\CategoryFieldApplier;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Api\Data\CategoryInterfaceFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class CategoryCreateTest extends TestCase
{
    public function testCreatesAtGlobalScope(): void
    {
        $calls = [];

        $category = $this->createStub(CategoryInterface::class);
        $category->method('getId')->willReturn(12);
        $category->method('getName')->willReturn('New');
        $category->method('getParentId')->willReturn(2);
        $category->method('getLevel')->willReturn(2);

        $factory = $this->createStub(CategoryInterfaceFactory::class);
        $factory->method('create')->willReturn($category);

        $repository = $this->createMock(CategoryRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('save')
            ->with($category)
            ->willReturnCallback(function (CategoryInterface $saved) use (&$calls) {
                $calls[] = 'save';
                return $saved;
            });

        $currentStore = $this->createStub(StoreInterface::class);
        $currentStore->method('getId')->willReturn(1);
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($currentStore);
        $storeManager->method('setCurrentStore')
            ->willReturnCallback(function ($store) use (&$calls): void {
                $calls[] = 'switch:' . (string) $store;
            });

        $tool = new CategoryCreate(
            $repository,
            $factory,
            $this->createStub(CategoryFieldApplier::class),
            new StoreScope($storeManager)
        );

        $tool->execute(['name' => 'New', 'parent_id' => 2]);

        $this->assertSame(['switch:0', 'save', 'switch:1'], $calls);
    }
}
