<?php

/**
 * This file is part of the Spryker Demoshop.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Yves\Product\Builder;

use Generated\Shared\Transfer\StorageProductTransfer;

class StorageProductBuilder implements StorageProductBuilderInterface
{

    /**
     * @var \Spryker\Yves\Product\Builder\AttributeVariantBuilderInterface
     */
    protected $attributeVariantBuilder;

    /**
     * @param \Spryker\Yves\Product\Builder\AttributeVariantBuilderInterface $attributeVariantBuilder
     */
    public function __construct(AttributeVariantBuilderInterface $attributeVariantBuilder)
    {
        $this->attributeVariantBuilder = $attributeVariantBuilder;
    }

    /**
     * @param array $productData
     * @param array $selectedAttributes
     *
     * @return \Generated\Shared\Transfer\StorageProductTransfer
     */
    public function buildProduct(array $productData, array $selectedAttributes = [])
    {
        $storageProductTransfer = $this->mapAbstractStorageProduct($productData);
        $storageProductTransfer->setSelectedAttributes($selectedAttributes);

        $storageProductTransfer = $this->attributeVariantBuilder->setSuperAttributes($storageProductTransfer);
        if (count($selectedAttributes) > 0) {
            $storageProductTransfer = $this->attributeVariantBuilder->setSelectedVariants(
                $selectedAttributes,
                $storageProductTransfer
            );
        }

        return $storageProductTransfer;
    }

    /**
     * @param array $productData
     *
     * @return \Generated\Shared\Transfer\StorageProductTransfer
     */
    protected function mapAbstractStorageProduct(array $productData)
    {
        $storageProductTransfer = new StorageProductTransfer();
        $storageProductTransfer->fromArray($productData, true);

        return $storageProductTransfer;
    }

}
