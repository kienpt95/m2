<?php
/**
 * Created by PhpStorm.
 * User: kien
 * Date: 9/27/17
 * Time: 12:09 PM
 */

namespace SM\Customer\Api;

/**
 * Interface for retrieval information about customer attributes metadata.
 * @api
 */
interface CustomerMetadataInterface extends \Magento\Customer\Api\MetadataInterface
{
    const ATTRIBUTE_SET_ID_CUSTOMER = 1;

    const ENTITY_TYPE_CUSTOMER = 'customer';

    const DATA_INTERFACE_NAME = 'SM\Customer\Api\Data\CustomerInterface';
}