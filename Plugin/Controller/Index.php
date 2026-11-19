<?php

namespace Webcode\Wishlist\Plugin\Controller;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Wishlist\Controller\AbstractIndex as WishlistAction;

/**
 * Wishlist add action plugin class.
 */
class Index
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $pageFactory;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private Json $json;

    /**
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\View\Result\PageFactory $pageFactory
     * @param \Magento\Framework\Serialize\Serializer\Json $json
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        CustomerSession $customerSession,
        ManagerInterface $messageManager,
        PageFactory $pageFactory,
        Json $json
    ) {
        $this->productRepository = $productRepository;
        $this->customerSession = $customerSession;
        $this->messageManager = $messageManager;
        $this->pageFactory = $pageFactory;
        $this->json = $json;
    }

    /**
     * Before dispatch event handler
     *
     * @param WishlistAction $subject
     * @param RequestInterface $request
     *
     * @return void
     */
    public function beforeDispatch(
        WishlistAction $subject,
        RequestInterface $request
    ) {
        if ($request->isXmlHttpRequest() && !$this->customerSession->isLoggedIn()) {
            // cancel redirect that was sent by wishlist beforeDispatch plugin
            /** @var \Magento\Framework\App\Response\Http $response */
            $response = $subject->getResponse();
            if ($response->isRedirect()) {
                $response->clearHeader('Location')->setStatusCode(200);
            }

            $response->representJson(
                $this->json->serialize(
                    [
                        'success' => false,
                        'error' => 'not_logged_in'
                    ]
                )
            );
        }
    }

    /**
     * After wishlist execute action plugin.
     *
     * @param WishlistAction $subject
     * @param mixed $result
     *
     * @return mixed
     */
    public function afterExecute(WishlistAction $subject, $result)
    {
        /** @var \Magento\Framework\App\Request\Http $request */
        $request = $subject->getRequest();
        if (!$request->isXmlHttpRequest()) {
            return $result;
        }

        /** @var \Magento\Framework\App\Response\Http $response */
        $response = $subject->getResponse();
        $page = $this->pageFactory->create();
        $page->addHandle('wishlist_index_index');

        $data = ['success' => true];
        if (($block = $this->getWishlistBlock()) && $block instanceof BlockInterface) {
            $data['wishlist'] = $block->toHtml();
        }

        // flush all messages that were added by action
//        $this->messageManager->getMessages(true);

        return $response->representJson((string) $this->json->serialize($data));
    }

    /**
     * Get wishlist block
     *
     * @return \Magento\Framework\View\Element\BlockInterface|bool
     */
    protected function getWishlistBlock()
    {
        $page = $this->pageFactory->create();
        $page->addHandle('wishlist_index_index');

        return $page->getLayout()->getBlock('customer.wishlist');
    }

}
