<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Product\Business\Product;

use ArrayObject;
use Exception;
use Generated\Shared\Transfer\ProductAbstractTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Generated\Shared\Transfer\StockProductTransfer;
use Generated\Shared\Transfer\ZedProductPriceTransfer;
use Spryker\Zed\Product\Business\Attribute\AttributeManagerInterface;
use Spryker\Zed\Product\Business\Exception\MissingProductException;
use Spryker\Zed\Product\Business\Transfer\ProductTransferGenerator;
use Spryker\Zed\Product\Dependency\Facade\ProductToLocaleInterface;
use Spryker\Zed\Product\Dependency\Facade\ProductToPriceInterface;
use Spryker\Zed\Product\Dependency\Facade\ProductToTouchInterface;
use Spryker\Zed\Product\Dependency\Facade\ProductToUrlInterface;
use Spryker\Zed\Product\Persistence\ProductQueryContainerInterface;

class ProductConcreteManager implements ProductConcreteManagerInterface
{

    /**
     * @var \Spryker\Zed\Product\Business\Attribute\AttributeManagerInterface
     */
    protected $attributeManager;

    /**
     * @var \Spryker\Zed\Product\Persistence\ProductQueryContainerInterface
     */
    protected $productQueryContainer;

    /**
     * @var \Spryker\Zed\Product\Dependency\Facade\ProductToTouchInterface
     */
    protected $touchFacade;

    /**
     * @var \Spryker\Zed\Product\Dependency\Facade\ProductToUrlInterface
     */
    protected $urlFacade;

    /**
     * @var \Spryker\Zed\Product\Dependency\Facade\ProductToLocaleInterface
     */
    protected $localeFacade;

    /**
     * @var \Spryker\Zed\Product\Dependency\Facade\ProductToPriceInterface
     */
    protected $priceFacade;

    /**
     * @var \Spryker\Zed\Product\Business\Product\ProductAbstractAssertionInterface
     */
    protected $productAbstractAssertion;

    /**
     * @var \Spryker\Zed\Product\Business\Product\ProductConcreteAssertionInterface
     */
    protected $productConcreteAssertion;

    public function __construct(
        AttributeManagerInterface $attributeManager,
        ProductQueryContainerInterface $productQueryContainer,
        ProductToTouchInterface $touchFacade,
        ProductToUrlInterface $urlFacade,
        ProductToLocaleInterface $localeFacade,
        ProductToPriceInterface $priceFacade,
        ProductAbstractAssertionInterface $productAbstractAssertion,
        ProductConcreteAssertionInterface $productConcreteAssertion
    ) {
        $this->attributeManager = $attributeManager;
        $this->productQueryContainer = $productQueryContainer;
        $this->touchFacade = $touchFacade;
        $this->urlFacade = $urlFacade;
        $this->localeFacade = $localeFacade;
        $this->priceFacade = $priceFacade;
        $this->productAbstractAssertion = $productAbstractAssertion;
        $this->productConcreteAssertion = $productConcreteAssertion;
    }

    /**
     * @param string $sku
     *
     * @return bool
     */
    public function hasProductConcrete($sku)
    {
        return $this->productQueryContainer
            ->queryProductConcreteBySku($sku)
            ->count() > 0;
    }

    /**
     * @param \Generated\Shared\Transfer\ProductConcreteTransfer $productConcreteTransfer
     *
     * @throws \Exception
     *
     * @return int
     */
    public function createProductConcrete(ProductConcreteTransfer $productConcreteTransfer)
    {
        $this->productQueryContainer->getConnection()->beginTransaction();

        try {
            $sku = $productConcreteTransfer->getSku();
            $this->productConcreteAssertion->assertSkuUnique($sku);

            $productConcreteEntity = $this->persistEntity($productConcreteTransfer);

            $idProductConcrete = $productConcreteEntity->getPrimaryKey();
            $productConcreteTransfer->setIdProductConcrete($idProductConcrete);

            $this->attributeManager->persistProductConcreteLocalizedAttributes($productConcreteTransfer);
            $this->persistPrice($productConcreteTransfer);

            $this->persistImageSets($productConcreteTransfer); //TODO: PLUGIN
            $this->persistStock($productConcreteTransfer); //TODO: PLUGIN

            $this->productQueryContainer->getConnection()->commit();
            return $idProductConcrete;

        } catch (Exception $e) {
            $this->productQueryContainer->getConnection()->rollBack();
            throw $e;
        }
    }

    /**
     * @param \Generated\Shared\Transfer\ProductConcreteTransfer $productConcreteTransfer
     *
     * @throws \Exception
     *
     * @return int
     */
    public function saveProductConcrete(ProductConcreteTransfer $productConcreteTransfer)
    {
        $this->productQueryContainer->getConnection()->beginTransaction();

        try {
            $sku = $productConcreteTransfer
                ->requireSku()
                ->getSku();

            $idProduct = (int)$productConcreteTransfer
                ->requireIdProductConcrete()
                ->getIdProductConcrete();

            $idProductAbstract = (int)$productConcreteTransfer
                ->requireFkProductAbstract()
                ->getFkProductAbstract();

            $this->productAbstractAssertion->assertProductExists($idProductAbstract);
            $this->productConcreteAssertion->assertProductExists($idProduct);
            $this->productConcreteAssertion->assertSkuIsUniqueWhenUpdatingProduct($idProduct, $sku);

            $productConcreteEntity = $this->persistEntity($productConcreteTransfer);

            $idProductConcrete = $productConcreteEntity->getPrimaryKey();
            $productConcreteTransfer->setIdProductConcrete($idProductConcrete);

            $this->attributeManager->persistProductConcreteLocalizedAttributes($productConcreteTransfer);
            $this->persistPrice($productConcreteTransfer);

            $this->persistImageSets($productConcreteTransfer);  //TODO: PLUGIN
            $this->persistStock($productConcreteTransfer);  //TODO: PLUGIN

            $this->productQueryContainer->getConnection()->commit();

            return $idProductConcrete;

        } catch (Exception $e) {
            $this->productQueryContainer->getConnection()->rollBack();
            throw $e;
        }
    }

    /**
     * @param int $idProduct
     *
     * @return \Generated\Shared\Transfer\ProductConcreteTransfer|null
     */
    public function getProductConcreteById($idProduct)
    {
        $product = $this->productQueryContainer
            ->queryProduct()
            ->filterByIdProduct($idProduct)
            ->findOne();

        if (!$product) {
            return null;
        }

        $transferGenerator = new ProductTransferGenerator();
        $productTransfer = $transferGenerator->convertProduct($product);
        $productTransfer = $this->loadProductData($productTransfer);

        return $productTransfer;
    }

    /**
     * @param string $sku
     *
     * @return int|null
     */
    public function getProductConcreteIdBySku($sku)
    {
        $productEntity = $this->productQueryContainer
            ->queryProduct()
            ->filterBySku($sku)
            ->findOne();

        if (!$productEntity) {
            return null;
        }

        return $productEntity->getIdProduct();
    }

    /**
     * @param string $concreteSku
     *
     * @throws \Spryker\Zed\Product\Business\Exception\MissingProductException
     *
     * @return \Generated\Shared\Transfer\ProductConcreteTransfer
     */
    public function getProductConcrete($concreteSku)
    {
        $idProduct = (int)$this->getProductConcreteIdBySku($concreteSku);
        $productConcreteTransfer = $this->getProductConcreteById($idProduct);

        if (!$productConcreteTransfer) {
            throw new MissingProductException(
                sprintf(
                    'Tried to retrieve a product concrete with sku %s, but it does not exist.',
                    $concreteSku
                )
            );
        }

        return $productConcreteTransfer;
    }

    /**
     * @param int $idProductAbstract
     *
     * @return \Generated\Shared\Transfer\ProductConcreteTransfer[]
     */
    public function getConcreteProductsByAbstractProductId($idProductAbstract)
    {
        $entityCollection = $this->productQueryContainer
            ->queryProduct()
            ->filterByFkProductAbstract($idProductAbstract)
            ->joinSpyProductAbstract()
            ->find();

        $transferGenerator = new ProductTransferGenerator();
        $transferCollection = $transferGenerator->convertProductCollection($entityCollection);

        for ($a = 0; $a < count($transferCollection); $a++) {
            $transferCollection[$a] = $this->loadProductData($transferCollection[$a]);
        }

        return $transferCollection;
    }

    /**
     * @param string $sku
     *
     * @throws \Spryker\Zed\Product\Business\Exception\MissingProductException
     *
     * @return int
     */
    public function getProductAbstractIdByConcreteSku($sku)
    {
        $productConcrete = $this->productQueryContainer
            ->queryProductConcreteBySku($sku)
            ->findOne();

        if (!$productConcrete) {
            throw new MissingProductException(
                sprintf(
                    'Tried to retrieve a product concrete with sku %s, but it does not exist.',
                    $sku
                )
            );
        }

        return $productConcrete->getFkProductAbstract();
    }

    /**
     * @param \Generated\Shared\Transfer\ProductConcreteTransfer $productConcreteTransfer
     *
     * @return \Orm\Zed\Product\Persistence\SpyProduct
     */
    protected function persistEntity(ProductConcreteTransfer $productConcreteTransfer)
    {
        $sku = $productConcreteTransfer
            ->requireSku()
            ->getSku();

        $fkProductAbstract = $productConcreteTransfer
            ->requireFkProductAbstract()
            ->getFkProductAbstract();

        $encodedAttributes = $this->attributeManager->encodeAttributes(
            $productConcreteTransfer->getAttributes()
        );

        $productConcreteEntity = $this->productQueryContainer
            ->queryProduct()
            ->filterByIdProduct($productConcreteTransfer->getIdProductConcrete())
            ->findOneOrCreate();

        $productConcreteEntity
            ->setSku($sku)
            ->setFkProductAbstract($fkProductAbstract)
            ->setAttributes($encodedAttributes)
            ->setIsActive($productConcreteTransfer->getIsActive());

        $productConcreteEntity->save();

        return $productConcreteEntity;
    }

    /**
     * @param \Generated\Shared\Transfer\ProductConcreteTransfer $productConcreteTransfer
     *
     * @return void
     */
    protected function persistPrice(ProductConcreteTransfer $productConcreteTransfer)
    {
        $priceTransfer = $productConcreteTransfer->getPrice();
        if ($priceTransfer instanceof ZedProductPriceTransfer) {
            $priceTransfer->setIdProduct(
                $productConcreteTransfer
                    ->requireIdProductConcrete()
                    ->getIdProductConcrete()
            );
            $this->priceFacade->persistConcreteProductPrice($priceTransfer);
        }
    }

    /**
     * TODO: PLUGIN
     *
     * @param \Generated\Shared\Transfer\ProductConcreteTransfer $productConcreteTransfer
     *
     * @return void
     */
    protected function persistImageSets(ProductConcreteTransfer $productConcreteTransfer)
    {
        return;
        $imageSetTransferCollection = $productConcreteTransfer->getImageSets();
        if (empty($imageSetTransferCollection)) {
            return;
        }

        foreach ($imageSetTransferCollection as $imageSetTransfer) {
            $imageSetTransfer->setIdProductAbstract(
                $productConcreteTransfer
                    ->requireIdProductConcrete()
                    ->getIdProductConcrete()
            );
            $this->productImageFacade->persistProductImageSet($imageSetTransfer);
        }
    }

    /**
     * TODO: PLUGIN
     *
     * @param \Generated\Shared\Transfer\ProductConcreteTransfer $productConcreteTransfer
     *
     * @return void
     */
    protected function persistStock(ProductConcreteTransfer $productConcreteTransfer)
    {
        return;
        /* @var \Generated\Shared\Transfer\StockProductTransfer[] $stockCollection */
        $stockCollection = $productConcreteTransfer->getStock();
        foreach ($stockCollection as $stockTransfer) {
            if (!$this->stockFacade->hasStockProduct($stockTransfer->getSku(), $stockTransfer->getStockType())) {
                $this->stockFacade->createStockProduct($stockTransfer);
            } else {
                $this->stockFacade->updateStockProduct($stockTransfer);
            }
        }
    }

    /**
     * @param \Generated\Shared\Transfer\ProductConcreteTransfer $productTransfer
     *
     * @return \Generated\Shared\Transfer\ProductConcreteTransfer
     */
    protected function loadProductData(ProductConcreteTransfer $productTransfer)
    {
        $this->loadLocalizedAttributes($productTransfer);
        $this->loadPrice($productTransfer);

        $this->loadStock($productTransfer); //TODO: PLUGIN
        $this->loadImageSet($productTransfer);  //TODO: PLUGIN

        return $productTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\ProductConcreteTransfer $productConcreteTransfer
     *
     * @return \Generated\Shared\Transfer\ProductConcreteTransfer
     */
    protected function loadPrice(ProductConcreteTransfer $productConcreteTransfer)
    {
        $priceTransfer = $this->priceFacade->getProductConcretePrice(
            $productConcreteTransfer->getIdProductConcrete()
        );

        if ($priceTransfer === null) {
            return $productConcreteTransfer;
        }

        $productConcreteTransfer->setPrice($priceTransfer);

        return $productConcreteTransfer;
    }

    /**
     * TODO: PLUGIN
     *
     * @param \Generated\Shared\Transfer\ProductConcreteTransfer $productConcreteTransfer
     *
     * @return \Generated\Shared\Transfer\ProductConcreteTransfer
     */
    protected function loadStock(ProductConcreteTransfer $productConcreteTransfer)
    {
        return;
        $stockCollection = $this->stockQueryContainer
            ->queryStockByProducts($productConcreteTransfer->getIdProductConcrete())
            ->innerJoinStock()
            ->find();

        if ($stockCollection === null) {
            return $productConcreteTransfer;
        }

        foreach ($stockCollection as $stockEntity) {
            $stockTransfer = (new StockProductTransfer())
                ->fromArray($stockEntity->toArray(), true)
                ->setStockType($stockEntity->getStock()->getName());

            $productConcreteTransfer->addStock($stockTransfer);
        }

        return $productConcreteTransfer;
    }

    /**
     * TODO: PLUGIN
     *
     * @param \Generated\Shared\Transfer\ProductConcreteTransfer $productTransfer
     *
     * @return \Generated\Shared\Transfer\ProductConcreteTransfer
     */
    protected function loadImageSet(ProductConcreteTransfer $productTransfer)
    {
        return;

        $imageSets = $this->productImageFacade
            ->getProductImagesSetCollectionByProductId($productTransfer->getIdProductConcrete());

        if ($imageSets === null) {
            return $productTransfer;
        }

        $productTransfer->setImageSets(
            new ArrayObject($imageSets)
        );

        return $productTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\ProductConcreteTransfer $productTransfer
     *
     * @return \Generated\Shared\Transfer\ProductConcreteTransfer
     */
    protected function loadLocalizedAttributes(ProductConcreteTransfer $productTransfer)
    {
        $productAttributeCollection = $this->productQueryContainer
            ->queryProductLocalizedAttributes($productTransfer->getIdProductConcrete())
            ->find();

        foreach ($productAttributeCollection as $attributeEntity) {
            $localeTransfer = $this->localeFacade->getLocaleById($attributeEntity->getFkLocale());

            $localizedAttributesTransfer = $this->attributeManager->createLocalizedAttributesTransfer(
                $attributeEntity->toArray(),
                $attributeEntity->getAttributes(),
                $localeTransfer
            );

            $productTransfer->addLocalizedAttributes($localizedAttributesTransfer);
        }

        return $productTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\ProductAbstractTransfer $productAbstractTransfer
     * @param \Generated\Shared\Transfer\ProductConcreteTransfer $productConcreteTransfer
     *
     * @return \Orm\Zed\Product\Persistence\SpyProduct
     */
    public function findProductEntityByAbstract(ProductAbstractTransfer $productAbstractTransfer, ProductConcreteTransfer $productConcreteTransfer)
    {
        return $this->productQueryContainer
            ->queryProduct()
            ->filterByFkProductAbstract($productAbstractTransfer->getIdProductAbstract())
            ->filterByIdProduct($productConcreteTransfer->getIdProductConcrete())
            ->findOne();
    }

}
