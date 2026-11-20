<?php
/*
 * @package      Webcode_Wishlist
 *
 * @author       Kostadin Bashev (bashev@webcode.bg)
 * @copyright    Copyright © 2026 Webcode Ltd. (https://webcode.bg/)
 * @license      https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace Webcode\Wishlist\Test\Unit\Plugin\Controller;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Request\Http as Request;
use Magento\Framework\App\Response\Http as Response;
use Magento\Framework\Message\Collection;
use Magento\Framework\Message\Error;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Message\Success;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Wishlist\Controller\AbstractIndex;
use PHPUnit\Framework\TestCase;
use Webcode\Wishlist\Plugin\Controller\Index;

/**
 * The AJAX answer says "success" only when the action did not add an error message.
 */
class IndexTest extends TestCase
{
    /**
     * @var Collection Session messages the plugin reads
     */
    private Collection $messages;

    /**
     * @var string|null JSON the plugin answered with
     */
    private ?string $json = null;

    /**
     * @return array{0: Index, 1: AbstractIndex}
     */
    private function plugin(): array
    {
        $this->messages = new Collection();
        $messageManager = $this->createMock(ManagerInterface::class);
        $messageManager->method('getMessages')->willReturnCallback(fn () => $this->messages);

        $layout = $this->createMock(LayoutInterface::class);
        $layout->method('getBlock')->willReturn(false);
        $page = $this->createMock(Page::class);
        $page->method('getLayout')->willReturn($layout);
        $pageFactory = $this->createMock(PageFactory::class);
        $pageFactory->method('create')->willReturn($page);

        $request = $this->createMock(Request::class);
        $request->method('isXmlHttpRequest')->willReturn(true);
        $response = $this->createMock(Response::class);
        $response->method('representJson')->willReturnCallback(function (string $json) use ($response) {
            $this->json = $json;
            return $response;
        });

        $subject = $this->createMock(AbstractIndex::class);
        $subject->method('getRequest')->willReturn($request);
        $subject->method('getResponse')->willReturn($response);

        $plugin = new Index(
            $this->createMock(ProductRepositoryInterface::class),
            $this->createMock(CustomerSession::class),
            $messageManager,
            $pageFactory,
            new Json()
        );

        return [$plugin, $subject];
    }

    public function testDoneActionAnswersSuccess(): void
    {
        [$plugin, $subject] = $this->plugin();

        $plugin->beforeExecute($subject);
        $this->messages->addMessage(new Success('Impulse Duffle has been added to your Wish List.'));
        $plugin->afterExecute($subject, null);

        self::assertSame(['success' => true], json_decode((string) $this->json, true));
    }

    public function testRefusedActionAnswersNotSavedWithItsMessage(): void
    {
        [$plugin, $subject] = $this->plugin();

        $plugin->beforeExecute($subject);
        $this->messages->addMessage(new Error('We can\'t specify a product.'));
        $plugin->afterExecute($subject, null);

        self::assertSame(
            ['success' => false, 'error' => 'not_saved', 'message' => 'We can\'t specify a product.'],
            json_decode((string) $this->json, true)
        );
    }

    /**
     * An error left in the session by an earlier request is not this action's failure.
     */
    public function testOlderErrorDoesNotMakeTheActionFail(): void
    {
        [$plugin, $subject] = $this->plugin();
        $this->messages->addMessage(new Error('An older error.'));

        $plugin->beforeExecute($subject);
        $plugin->afterExecute($subject, null);

        self::assertSame(['success' => true], json_decode((string) $this->json, true));
    }
}
