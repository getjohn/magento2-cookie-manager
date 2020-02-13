<?php
namespace GetJohn\CookieManager\Model;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\HTTP\Header as HttpHeader;
use Psr\Log\LoggerInterface;
use Magento\Framework\Stdlib\Cookie\PhpCookieManager;
use Magento\Store\Model\ScopeInterface; 

class CookieManager extends PhpCookieManager
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
     * @param CookieScopeInterface $scope
     * @param CookieReaderInterface $reader
     * @param LoggerInterface $logger
     * @param HttpHeader $httpHeader
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        CookieScopeInterface $scope,
        CookieReaderInterface $reader,
        LoggerInterface $logger = null,
        HttpHeader $httpHeader = null,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($scope, $reader, $logger, $httpHeader);

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
    protected function setCookie($name, $value, array $metadataArray)
    {
        $expire = $this->computeExpirationTime($metadataArray);

        $this->checkAbilityToSendCookie($name, $value);

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
$this->_logger->debug('set cookie '.$name.'='.$value);

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
}
