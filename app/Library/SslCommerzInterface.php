<?php

namespace App\Library;

/**
 * Interface SslCommerzInterface
 * @package App\Library
 */
interface SslCommerzInterface
{
    /**
     * @param array $data
     * @return mixed
     */
    public function makePayment(array $data): mixed;

    /**
     * @param $trxID
     * @param $amount
     * @param $currency
     * @param $requestData
     * @return mixed
     */
    public function orderValidate($trxID, $amount, $currency, $requestData): mixed;

    /**
     * @param $data
     * @return array
     */
    public function setParams($data): array;

    /**
     * @param array $data
     * @return mixed
     */
    public function setRequiredInfo(array $data): mixed;

    /**
     * @param array $data
     * @return mixed
     */
    public function setCustomerInfo(array $data): mixed;

    /**
     * @param array $data
     * @return mixed
     */
    public function setShipmentInfo(array $data): mixed;

    /**
     * @param array $data
     * @return mixed
     */
    public function setProductInfo(array $data): mixed;

    /**
     * @param array $data
     * @return mixed
     */
    public function setAdditionalInfo(array $data): mixed;

    /**
     * @param $data
     * @param array $header
     * @param false $setLocalhost
     * @return mixed
     */
    public function callToApi($data, $header = [], $setLocalhost = false): mixed;
}
