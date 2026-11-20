<?php

namespace Webcode\Wishlist\Plugin\Controller;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Message\MessageInterface;
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
     * Error messages in the session before the action ran.
     *
     * The wishlist actions report a failure only as an error message (the product
     * cannot be added, the item is gone...), so an error that appears while the
     * action runs is what "not done" looks like.
     *
     * @var int
     */
    private int $errorsBefore = 0;

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
     * Counts the error messages before the action runs.
     *
     * @param WishlistAction $subject
     *
     * @return void
     */
    public function beforeExecute(WishlistAction $subject): void
    {
        $this->errorsBefore = $this->countErrors();
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

        // An error added by the action means it was not done (e.g. the product
        // cannot be added): answering "success" there made the button say "Added".
        if ($this->countErrors() > $this->errorsBefore) {
            $last = $this->messageManager->getMessages(false)->getLastAddedMessage();

            return $response->representJson((string) $this->json->serialize([
                'success' => false,
                'error' => 'not_saved',
                'message' => $last && $last->getType() === MessageInterface::TYPE_ERROR
                    ? (string) $last->getText()
                    : '',
            ]));
        }

        $data = ['success' => true];
        if (($block = $this->getWishlistBlock()) && $block instanceof BlockInterface) {
            $data['wishlist'] = $block->toHtml();
        }

        // flush all messages that were added by action
//        $this->messageManager->getMessages(true);

        return $response->representJson((string) $this->json->serialize($data));
    }

    /**
     * Error messages currently in the session.
     *
     * @return int
     */
    private function countErrors(): int
    {
        return (int) $this->messageManager->getMessages(false)->getCountByType(MessageInterface::TYPE_ERROR);
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
