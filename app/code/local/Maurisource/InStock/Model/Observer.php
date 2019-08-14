<?php 
class Maurisource_InStock_Model_Observer {

    protected $_allowSetOutOfStock = true;

    public function catalog_product_save_after($observer) {

        $product = $observer->getProduct();
        $simpleProductId = $product->getEntityId();
        $simpleProductQty = $product->getStockData('qty');

        if($product->getTypeId() == 'simple'){

            //check if simple product has a parent
            $parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($simpleProductId);

            if(is_array($parentIds) && count($parentIds)> 0){
                // simple product has parent(s)
                foreach($parentIds as $parentId){

                    // now we check all the associated products
                    $this->_checkStockToOutOfStock($parentId);

                    if($this->_allowSetOutOfStock === true){

                        $this->_updateStockDataOutStock($parentId);

                    }else{

                        $this->_updateStockDataInStock($parentId);

                    }

                }

            }

            if($simpleProductQty > 0){

                $this->_updateSimpleProductStock($simpleProductId);

            }

        }elseif($product->getTypeId() == 'configurable' && $simpleProductQty > 0){

            $this->_updateSimpleProductStock($simpleProductId);

        }
    }



    private function _updateSimpleProductStock($simpleProductId){

        $this->_updateStockDataInStock($simpleProductId);

    }

    private function _updateStockDataInStock($product_id){

        $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product_id); // Load the stock for this product

        $stock->setData('is_in_stock', 1); // Set the Product to InStock

        $stock->save(); // Save
    }

    private function _updateStockDataOutStock($product_id){

        $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product_id); // Load the stock for this product

        $stock->setData('is_in_stock', 0); // Set the Product to InStock

        $stock->save(); // Save
    }

    private function _checkStockToOutOfStock($configProductId){

        // get child products of config. product
        $childProducts = Mage::getModel('catalog/product_type_configurable')->getChildrenIds($configProductId);

        foreach($childProducts[0] as $childProduct){

            $product = Mage::getModel('catalog/product')->load($childProduct);

            $stockQty = $product->getStockItem()->getQty();

            if($stockQty > 0){

                $this->_allowSetOutOfStock = false;

                return;

            }

        }
    }

}
?>