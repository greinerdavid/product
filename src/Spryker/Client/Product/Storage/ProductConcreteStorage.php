<?php
/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Product\Storage;

class ProductConcreteStorage
{

    /**
     * @var \Spryker\Client\Storage\StorageClientInterface
     */
    protected $storage;

    /**
     * @var \Spryker\Shared\Collector\Code\KeyBuilder\KeyBuilderInterface
     */
    protected $keyBuilder;

    /**
     * @var string
     */
    protected $locale;

    /**
     * @param \Spryker\Client\Storage\StorageClientInterface $storage
     * @param \Spryker\Shared\Collector\Code\KeyBuilder\KeyBuilderInterface $keyBuilder
     * @param string $localeName
     */
    public function __construct($storage, $keyBuilder, $localeName)
    {
        $this->storage = $storage;
        $this->keyBuilder = $keyBuilder;
        $this->locale = $localeName;
    }

    /**
     * @param int $idProductConcrete
     *
     * @return array
     */
    public function getProductConcreteById($idProductConcrete)
    {
        $key = $this->keyBuilder->generateKey($idProductConcrete, $this->locale);
        $product = $this->storage->get($key);

        return $product;
    }

}
