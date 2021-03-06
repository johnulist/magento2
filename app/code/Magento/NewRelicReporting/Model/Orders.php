<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NewRelicReporting\Model;

class Orders extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Initialize orders model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Magento\NewRelicReporting\Model\Resource\Orders');
    }
}
