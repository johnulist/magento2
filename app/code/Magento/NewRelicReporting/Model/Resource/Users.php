<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NewRelicReporting\Model\Resource;

class Users extends \Magento\Framework\Model\Resource\Db\AbstractDb
{
    /**
     * Initialize users resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('reporting_users', 'entity_id');
    }
}
