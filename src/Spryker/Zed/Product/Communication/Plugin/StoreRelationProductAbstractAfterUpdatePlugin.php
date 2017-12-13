<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Product\Communication\Plugin;

use Generated\Shared\Transfer\ProductAbstractTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\Product\Dependency\Plugin\ProductAbstractPluginUpdateInterface;

/**
 * @method \Spryker\Zed\Product\Business\ProductFacadeInterface getFacade()
 * @method \Spryker\Zed\Product\Communication\ProductCommunicationFactory getFactory()
 */
class StoreRelationProductAbstractAfterUpdatePlugin extends AbstractPlugin implements ProductAbstractPluginUpdateInterface
{
    /**
     * {@inheritdoc}
     *
     * @param \Generated\Shared\Transfer\ProductAbstractTransfer $productAbstractTransfer
     *
     * @return \Generated\Shared\Transfer\ProductAbstractTransfer
     */
    public function update(ProductAbstractTransfer $productAbstractTransfer)
    {
        $this->getFacade()->saveProductAbstractStoreRelation($productAbstractTransfer->getStoreRelation());

        return $productAbstractTransfer;
    }
}
