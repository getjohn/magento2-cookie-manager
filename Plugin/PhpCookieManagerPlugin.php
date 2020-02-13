<?php
namespace GetJohn\CookieManager\Plugin;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Phrase;
use Psr\Log\LoggerInterface;
use Magento\Framework\Stdlib\Cookie\PhpCookieManager;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use Magento\Framework\Stdlib\Cookie\CookieMetadata;
use Magento\Store\Model\ScopeInterface; 

class PhpCookieManagerPlugin
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig = null;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager = null;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger = null;


    /**
     * @param LoggerInterface $logger
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        LoggerInterface $logger = null,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_logger = $logger ?: ObjectManager::getInstance()->get(LoggerInterface::class);
        $this->_scopeConfig = $scopeConfig;
    	$this->_storeManager = $storeManager;
    }

    /**
     * Set a value in a cookie with the given $name $value pairing.
     *
     * @param string $name
     * @param string $value
     * @param array $metadataArray
     * @return void
     * @throws FailureToSendException If cookie couldn't be sent to the browser.
     * @throws CookieSizeLimitReachedException Thrown when the cookie is too big to store any additional data.
     * @throws InputException If the cookie name is empty or contains invalid characters.
     */
    protected function aroundSetCookie(\Magento\Framework\Stdlib\Cookie\PhpCookieManager $mgr, callable $proceed, $name, $value, array $metadataArray)
    {
        $proceed($name, $value, $metadataArray); // throws exception if anything goes wrong

        $expire = $this->computeExpirationTime($metadataArray);

        $options = [
            'expires' => $expire,
            'path' => $this->extractValue(CookieMetadata::KEY_PATH, $metadataArray, ''),
            'domain' => $this->extractValue(CookieMetadata::KEY_DOMAIN, $metadataArray, ''),
            'samesite' => $this->getSameSite(),
        ];

        $secure = $this->extractValue(CookieMetadata::KEY_SECURE, $metadataArray, false);
        if($secure !== false)
        {
            $options['secure'] = $secure;
        }

        $httponly = $this->extractValue(CookieMetadata::KEY_HTTP_ONLY, $metadataArray, false);
        if($httponly !== false)
        {
            $options['httponly'] = $httponly;
        }

        $phpSetcookieSuccess = setcookie(
            $name,
            $value,
            $options
        );
$this->_logger->debug('plugin reset cookie '.$name.'='.$value.'; samesite='.$this->getSameSite());

        if (!$phpSetcookieSuccess) {
            $params['name'] = $name;
            if ($value == '') {
                throw new FailureToSendException(
                    new Phrase('The cookie with "%name" cookieName couldn\'t be deleted.', $params)
                );
            } else {
                throw new FailureToSendException(
                    new Phrase('The cookie with "%name" cookieName couldn\'t be sent. Please try again later.', $params)
                );
            }
        }
    }

    protected function getSameSite()
    {
        $area = 'frontend';
        $storeId = 0;
        try {
            $area = $this->_state->getAreaCode();
            $store = $this->_storeManager->getStore();
            if($store)
            {
                $storeId = $store->getStoreId();
            }
        }
        catch(\Exception $e)
        {
        }
        return $this->_scopeConfig->getValue('web/cookie/cookie_samesite', ScopeInterface::SCOPE_STORES, $storeId);
    }

    /**
     * (from PhpCookieManager)
     * Determines the expiration time of a cookie.
     *
     * @param array $metadataArray
     * @return int in seconds since the Unix epoch.
     */
    private function computeExpirationTime(array $metadataArray)
    {
        if (isset($metadataArray[PhpCookieManager::KEY_EXPIRE_TIME])
            && $metadataArray[PhpCookieManager::KEY_EXPIRE_TIME] < time()
        ) {
            $expireTime = $metadataArray[PhpCookieManager::KEY_EXPIRE_TIME];
        } else {
            if (isset($metadataArray[CookieMetadata::KEY_DURATION])) {
                $expireTime = $metadataArray[CookieMetadata::KEY_DURATION] + time();
            } else {
                $expireTime = PhpCookieManager::EXPIRE_AT_END_OF_SESSION_TIME;
            }
        }

        return $expireTime;
    }

    /**
     * (from PhpCookieManager)
     * Determines the value to be used as a $parameter.
     * If $metadataArray[$parameter] is not set, returns the $defaultValue.
     *
     * @param string $parameter
     * @param array $metadataArray
     * @param string|boolean|int|null $defaultValue
     * @return string|boolean|int|null
     */
    private function extractValue($parameter, array $metadataArray, $defaultValue)
    {
        if (array_key_exists($parameter, $metadataArray)) {
            return $metadataArray[$parameter];
        } else {
            return $defaultValue;
        }
    }

}
