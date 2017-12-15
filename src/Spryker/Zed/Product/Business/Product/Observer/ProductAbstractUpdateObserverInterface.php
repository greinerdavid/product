<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Product\Business\Product\Observer;

use Generated\Shared\Transfer\ProductAbstractTransfer;

interface ProductAbstractUpdateObserverInterface
{
    /**
     * Specification:
     * - Executed on "before" and/or on "after" event when an abstract product is updated.
     * - Notifies registered observers in chain of responsibility.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\ProductAbstractTransfer $productAbstractTransfer
     *
     * @return \Generated\Shared\Transfer\ProductAbstractTransfer
     */
    public function update(ProductAbstractTransfer $productAbstractTransfer);
}
